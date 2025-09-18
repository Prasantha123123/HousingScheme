<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShopRentalApproveController extends Controller
{
    public function approve(Request $request, int $id)
    {
        // ---------- BULK ----------
        if ($request->boolean('bulk')) {
            $data = $request->validate([
                'ids'           => ['required','array','min:1'],
                'ids.*'         => ['integer','exists:ShopRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $method = $data['paymentMethod'];

            DB::transaction(function () use ($data, $method) {
                $rows = ShopRental::whereIn('id', $data['ids'])->lockForUpdate()->get();

                foreach ($rows as $r) {
                    // If approving as CASH in bulk, mark paid in full; otherwise keep existing paidAmount
                    $paid = ($method === 'cash') ? (float) $r->billAmount : (float) $r->paidAmount;

                    // Use the allocation logic for bulk approvals too
                    $this->allocateAndFinalize($r, $paid, $method, null);
                }
            });

            return back()->with('success', 'Selected rentals approved.');
        }

        // ---------- SINGLE ----------
        $data = $request->validate([
            'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            'paidAmount'    => ['nullable','numeric','min:0'],
            'recipt'        => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ]);

        DB::transaction(function () use ($request, $data, $id) {
            $r = ShopRental::lockForUpdate()->findOrFail($id);

            $receiptPath = null;
            if ($request->hasFile('recipt')) {
                $receiptPath = $request->file('recipt')->store('receipts', 'public');
            }

            // If CASH and no amount provided, default to full bill
            $paid = $data['paidAmount'] ?? null;
            if ($data['paymentMethod'] === 'cash' && $paid === null) {
                $paid = (float) $r->billAmount;
            }

            if ($paid === null) {
                $paid = (float) $r->paidAmount; // Keep existing paid amount
            }

            $this->allocateAndFinalize($r, $paid, $data['paymentMethod'], $receiptPath);
        });

        return back()->with('success', 'Rental approved.');
    }

    /**
     * Allocate payment across earlier months and finalize approval
     * This replicates the house bill approval logic exactly
     */
    private function allocateAndFinalize(ShopRental $rental, float $paidAmount, string $paymentMethod, ?string $receiptPath)
    {
        $eps = 0.01;
        $txnTotal = round(max(0, $paidAmount), 2); // what the customer paid now
        $pool = $txnTotal; // we'll spend this pool across arrears + current
        
        // Store the original customer payment amount
        $customerPaidAmount = $txnTotal;

        // Step 1: Clear earlier months first (oldest → newest)
        if ($pool > 0) {
            $previousRentals = ShopRental::where('shopNumber', $rental->shopNumber)
                ->where('month', '<', $rental->month)
                ->orderBy('month') // oldest first
                ->lockForUpdate()
                ->get();

            foreach ($previousRentals as $r) {
                // Skip hard rejections if desired:
                // if ($r->status === 'Rejected') continue;

                $need = round(max(0, (float)$r->billAmount - (float)$r->paidAmount), 2);
                if ($need <= 0) continue;

                $alloc = min($need, $pool);
                if ($alloc > 0) {
                    $r->paidAmount = round((float)$r->paidAmount + $alloc, 2);

                    if ($r->paidAmount + $eps >= (float)$r->billAmount) {
                        $r->paidAmount = (float)$r->billAmount;
                        $r->status = 'Approved';
                        $r->approved_at = $r->approved_at ?? now();
                    } else {
                        $r->status = 'PartPayment';
                    }

                    // Mirror payment method if missing
                    if (!$r->paymentMethod && $paymentMethod) {
                        $r->paymentMethod = $paymentMethod;
                    }

                    $r->save();

                    $pool = round($pool - $alloc, 2);
                    if ($pool <= 0) break;
                }
            }
        }

        // Step 2: Determine status based on how much of THIS rental's bill is covered by remaining pool
        $appliedToCurrent = min($pool, (float)$rental->billAmount);
        $rental->approved_at = now();
        
        // Keep the original customer payment amount (don't overwrite with allocated portion)
        $rental->paidAmount = $customerPaidAmount;
        $rental->paymentMethod = $paymentMethod;
        
        // Status is based on how much of this month's bill was covered after paying arrears
        $rental->status = $this->statusFor((float)$rental->billAmount, $appliedToCurrent);
        
        if ($receiptPath) {
            $rental->recipt = $receiptPath;
        }
        
        $rental->save();

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

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => ['required','string','max:1000'],
        ]);

        $r = ShopRental::findOrFail($id);
        $r->status = 'Rejected';
        $r->save();

        return back()->with('success', 'Rental rejected.');
    }
}
