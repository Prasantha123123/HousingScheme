<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HouseBillApproveController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }
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

                    // Calculate maximum payable amount (prevents overpayment)
                    $maxPayable = $this->billingService->getMaxPayableAmount('house', $row->houseNo, $row->month);
                    
                    $newPaymentAmount = 0;
                    
                    // If no amount recorded yet, auto-fill for any payment method
                    if ((float)$row->paidAmount <= 0) {
                        $targetAmount = min((float)$row->billAmount, $maxPayable);
                        $newPaymentAmount = max(0, $targetAmount - (float)$row->paidAmount);
                    } else {
                        // For existing partial payments, we don't add more in bulk mode
                        $skipped++;
                        continue;
                    }

                    if ($newPaymentAmount <= 0) {
                        $skipped++;
                        continue;
                    }

                    $row->paymentMethod = $data['paymentMethod'];

                    // Use unified billing service to process the NEW payment amount (no receipt for bulk)
                    $this->billingService->processHousePayment(
                        $row, 
                        $newPaymentAmount, 
                        $data['paymentMethod'], 
                        null,
                        now()
                    );
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

            // Calculate maximum payable amount (prevents overpayment)
            $maxPayable = $this->billingService->getMaxPayableAmount('house', $bill->houseNo, $bill->month);
            
            $newPaymentAmount = 0;

            // Use explicit amount if provided; else auto-fill based on payment method and current state
            if (array_key_exists('paidAmount', $data) && $data['paidAmount'] !== null) {
                // For subsequent partial payments, this is the NEW payment amount to add
                $newPaymentAmount = (float)$data['paidAmount'];
                
                // Check if adding this would exceed max payable
                $currentPaid = (float)$bill->paidAmount;
                $maxNewPayment = max(0, $maxPayable - $currentPaid);
                $newPaymentAmount = min($newPaymentAmount, $maxNewPayment);
                
            } elseif ((float)$bill->paidAmount > 0) {
                // Bill already has payment amount (from customer payment) - process the existing amount
                $newPaymentAmount = (float)$bill->paidAmount;
                // Reset paidAmount to 0 so the service can properly allocate it
                $bill->paidAmount = 0;
                $bill->save();
                
            } elseif ((float)$bill->paidAmount < (float)$bill->billAmount && $bill->status !== 'PartPayment') {
                // Auto-fill for first-time payments (any method), not subsequent partial payments
                // Cap to max payable amount to prevent overpayment
                $targetAmount = min((float)$bill->billAmount, $maxPayable);
                $newPaymentAmount = max(0, $targetAmount - (float)$bill->paidAmount);
            }

            if ($newPaymentAmount <= 0) {
                return back()->withErrors(['error' => 'No outstanding amount to pay or invalid payment amount.']);
            }

            $bill->paymentMethod = $data['paymentMethod'];

            // Use unified billing service to process the NEW payment amount
            $this->billingService->processHousePayment(
                $bill, 
                $newPaymentAmount, 
                $data['paymentMethod'], 
                $receiptPath,
                now()
            );
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
}
