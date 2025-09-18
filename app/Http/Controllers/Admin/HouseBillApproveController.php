<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HouseBillApproveController extends Controller
{
    public function approve(Request $request, int $id)
    {
        // ---------- BULK ----------
        if ($request->boolean('bulk')) {
            $data = $request->validate([
                'ids'           => ['required','array','min:1'],
                'ids.*'         => ['integer','exists:HouseRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $processed = 0;
            $skipped   = 0;

            DB::transaction(function () use ($data, &$processed, &$skipped) {
                // Lock all selected rows
                $rows = HouseRental::whereIn('id', $data['ids'])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Determine the latest NOT-approved row per house
                $latestPerHouse = [];
                foreach ($rows as $row) {
                    $latest = HouseRental::where('houseNo', $row->houseNo)
                        ->where('status', '!=', 'Approved')
                        ->orderBy('month', 'desc')
                        ->lockForUpdate()
                        ->first();

                    if ($latest) {
                        $latestPerHouse[$row->houseNo] = $latest->id;
                    }
                }

                foreach ($rows as $row) {
                    // Only process if this row IS the latest outstanding for that house
                    if (($latestPerHouse[$row->houseNo] ?? null) !== $row->id) {
                        $skipped++;
                        continue;
                    }

                    // If no amount recorded yet and bulk is CASH, assume "this month only"
                    if ((float)$row->paidAmount <= 0 && $data['paymentMethod'] === 'cash') {
                        $row->paidAmount = (float)$row->billAmount;
                    }

                    $row->paymentMethod = $data['paymentMethod'];

                    // Allocate & finalize (no receipt for bulk)
                    $this->allocateAndFinalize($row, null);
                    $processed++;
                }
            });

            $msg = "Bulk approve finished. Processed: {$processed}".($skipped ? " · Skipped (not latest): {$skipped}" : '');
            return back()->with('success', $msg);
        }

        // ---------- SINGLE ----------
        $data = $request->validate([
            'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            'paidAmount'    => ['nullable','numeric','min:0'],
            'recipt'        => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ]);

        DB::transaction(function () use ($request, $data, $id) {
            /** @var HouseRental $bill */
            $bill = HouseRental::lockForUpdate()->findOrFail($id);

            // Must be the latest outstanding bill for this house
            $latestOpen = HouseRental::where('houseNo', $bill->houseNo)
                ->where('status', '!=', 'Approved')
                ->orderBy('month', 'desc')
                ->lockForUpdate()
                ->first();

            if (!$latestOpen || $latestOpen->id !== $bill->id) {
                abort(422, 'Please approve the latest outstanding bill for this house first.');
            }

            $receiptPath = null;
            if ($request->hasFile('recipt')) {
                $receiptPath = $request->file('recipt')->store('receipts', 'public');
            }

            // Use explicit amount if provided; else for CASH assume "this month only" if nothing yet/short
            if (array_key_exists('paidAmount', $data) && $data['paidAmount'] !== null) {
                $bill->paidAmount = (float)$data['paidAmount'];
            } elseif ($data['paymentMethod'] === 'cash' && (float)$bill->paidAmount < (float)$bill->billAmount) {
                $bill->paidAmount = (float)$bill->billAmount;
            }

            $bill->paymentMethod = $data['paymentMethod'];

            // Allocate & finalize (pass receipt path)
            $this->allocateAndFinalize($bill, $receiptPath);
        });

        return back()->with('success', 'Bill approved.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => ['required','string','max:1000'],
        ]);

        $bill = HouseRental::findOrFail($id);
        $bill->status = 'Rejected';
        $bill->save();

        return back()->with('success', 'Bill rejected.');
    }

    /**
     * Allocate the payment recorded on the *latest* row:
     *   1) Apply to earlier months' deficits (oldest -> newest) until cleared
     *   2) Apply remaining to the current month (cap at its billAmount)
     * Then set statuses and approved_at on all affected rows.
     *
     * IMPORTANT:
     * - The current month keeps its full paidAmount (what customer actually paid)
     * - Earlier months get their paidAmount updated with allocated portions
     * - Status is determined by how much of THIS month's bill was covered
     */
    protected function allocateAndFinalize(HouseRental $current, ?string $receiptPath = null): void
    {
        $eps       = 0.01;
        $txnTotal  = round(max(0, (float)$current->paidAmount), 2); // what the customer paid now
        $pool      = $txnTotal;                                     // we'll spend this pool across arrears + current
        
        // Store the original customer payment amount
        $customerPaidAmount = $txnTotal;

        // 1) Clear earlier months first (oldest → newest)
        if ($pool > 0) {
            $earliers = HouseRental::where('houseNo', $current->houseNo)
                ->where('month', '<', $current->month)
                ->orderBy('month') // oldest first
                ->lockForUpdate()
                ->get();

            foreach ($earliers as $r) {
                // Skip hard rejections if desired:
                // if ($r->status === 'Rejected') continue;

                $need = round(max(0, (float)$r->billAmount - (float)$r->paidAmount), 2);
                if ($need <= 0) continue;

                $alloc = min($need, $pool);
                if ($alloc > 0) {
                    $r->paidAmount = round((float)$r->paidAmount + $alloc, 2);

                    if ($r->paidAmount + $eps >= (float)$r->billAmount) {
                        $r->paidAmount  = (float)$r->billAmount;
                        $r->status      = 'Approved';
                        $r->approved_at = $r->approved_at ?? now();
                    } else {
                        $r->status = 'PartPayment';
                    }

                    // Mirror method if missing
                    if (!$r->paymentMethod && $current->paymentMethod) {
                        $r->paymentMethod = $current->paymentMethod;
                    }

                    $r->save();

                    $pool = round($pool - $alloc, 2);
                    if ($pool <= 0) break;
                }
            }
        }

        // 2) Determine status based on how much of THIS month's bill is covered by remaining pool
        $appliedToCurrent = min($pool, (float)$current->billAmount);
        $current->approved_at = now();
        
        // Keep the original customer payment amount (don't overwrite with allocated portion)
        $current->paidAmount = $customerPaidAmount;
        
        // Status is based on how much of this month's bill was covered after paying arrears
        $current->status = $this->statusFor((float)$current->billAmount, $appliedToCurrent);

        // Save receipt if provided
        if ($receiptPath) {
            $current->recipt = $receiptPath;
        }

        $current->save();

        // NOTE: If ($pool - $appliedToCurrent) > 0, that's true overpayment/credit.
        // You can store it in a credits table and auto-apply next month if needed.
    }

    /** Decide status from bill vs paid portion. */
    protected function statusFor(float $bill, float $paidPortionForThisMonth): string
    {
        $eps = 0.01;
        if ($paidPortionForThisMonth <= $eps)        return 'Pending';
        if ($paidPortionForThisMonth + $eps < $bill) return 'PartPayment';
        if ($paidPortionForThisMonth >  $bill + $eps) return 'ExtraPayment';
        return 'Approved';
    }
}
