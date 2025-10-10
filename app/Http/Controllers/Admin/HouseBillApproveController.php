<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use App\Services\UnifiedBillingService;
use App\Services\Payments\UnifiedPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HouseBillApproveController extends Controller
{
    private UnifiedBillingService $billingService;
    private UnifiedPaymentService $paymentService;

    public function __construct(UnifiedBillingService $billingService, UnifiedPaymentService $paymentService)
    {
        $this->billingService = $billingService;
        $this->paymentService = $paymentService;
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

                    // Use new payment service for admin-initiated payment
                    $payment = $this->paymentService->make('house', $row->id, [
                        'amount' => $newPaymentAmount,
                        'method' => $data['paymentMethod'],
                        'receipt' => null, // No receipt for bulk
                        'isCustomerFlow' => false // Admin payment, auto-approve
                    ]);
                    
                    // Immediately approve the payment
                    $this->paymentService->approve('house', $payment->id);
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

        $isApprovalOnly = false;

        DB::transaction(function () use ($request, $data, $id, &$isApprovalOnly) {
            /** @var HouseRental $bill */
            $bill = HouseRental::lockForUpdate()->findOrFail($id);

            // For cash payments, allow paying all unpaid bills for this house (across all months)
            if ($data['paymentMethod'] === 'cash') {
                // Get all unpaid bills for this house (all months)
                $unpaidBills = HouseRental::where('houseNo', $bill->houseNo)
                    ->where('status', '!=', 'Approved')
                    ->whereRaw('billAmount > paidAmount')
                    ->orderBy('month', 'asc') // Pay oldest first
                    ->lockForUpdate()
                    ->get();

                $totalProcessed = 0;
                $totalAmountPaid = 0;
                $paidAmount = array_key_exists('paidAmount', $data) && $data['paidAmount'] !== null 
                    ? (float)$data['paidAmount'] 
                    : $unpaidBills->sum(fn($b) => (float)$b->billAmount - (float)$b->paidAmount);

                $remainingCash = $paidAmount;

                foreach ($unpaidBills as $houseBill) {
                    if ($remainingCash <= 0) break;
                    
                    $billBalance = (float)$houseBill->billAmount - (float)$houseBill->paidAmount;
                    $paymentAmount = min($billBalance, $remainingCash);
                    
                    if ($paymentAmount > 0) {
                        // Use new payment service for admin-initiated payment
                        $payment = $this->paymentService->make('house', $houseBill->id, [
                            'amount' => $paymentAmount,
                            'method' => $data['paymentMethod'],
                            'receipt' => null,
                            'isCustomerFlow' => false // Admin payment, auto-approve
                        ]);
                        
                        // Immediately approve the payment
                        $this->paymentService->approve('house', $payment->id);
                        $totalProcessed++;
                        $totalAmountPaid += $paymentAmount;
                        $remainingCash -= $paymentAmount;
                    }
                }

                $message = "Successfully processed {$totalProcessed} bills for {$bill->houseNo} with Rs " . number_format($totalAmountPaid, 2) . " cash payment.";
                if ($remainingCash > 0) {
                    $message .= " (Excess: Rs " . number_format($remainingCash, 2) . ")";
                }
                
                return back()->with('success', $message);
            }

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
                // Admin entered a specific amount - this could be either:
                // 1. Total payment amount (if equals or exceeds billAmount)
                // 2. Additional payment amount (if less than remaining balance)
                $adminAmount = (float)$data['paidAmount'];
                $currentPaid = (float)$bill->paidAmount;
                $billAmount = (float)$bill->billAmount;
                $remainingBalance = $billAmount - $currentPaid;
                
                // Determine if admin entered total amount or additional amount
                if ($adminAmount >= $billAmount) {
                    // Admin entered amount >= bill amount (treat as full payment)
                    $newPaymentAmount = $remainingBalance;
                } elseif ($adminAmount > $currentPaid && $adminAmount < $billAmount) {
                    // Admin entered total target payment amount
                    $newPaymentAmount = $adminAmount - $currentPaid;
                } elseif ($adminAmount > 0 && $adminAmount <= $remainingBalance) {
                    // Admin entered additional payment amount  
                    $newPaymentAmount = $adminAmount;
                } elseif (abs($adminAmount - $currentPaid) < 0.01) {
                    // Admin entered same amount as current - just approve existing payment
                    $bill->paymentMethod = $data['paymentMethod'];
                    
                    // Find latest pending payment for this rental and approve it
                    $pendingPayment = $this->paymentService->latestPendingPaymentForRental('house', $bill->id);
                    if ($pendingPayment) {
                        $this->paymentService->approve('house', $pendingPayment->id);
                    } else {
                        // Fallback to old approval method if no payment record found
                        $this->billingService->approveCustomerPayment(
                            'house', 
                            $bill->id, 
                            $data['paymentMethod'], 
                            $receiptPath
                        );
                    }
                    return;
                } else {
                    abort(422, 'Invalid payment amount. Bill amount: Rs ' . number_format($billAmount, 2) . ', Already paid: Rs ' . number_format($currentPaid, 2) . ', Remaining: Rs ' . number_format($remainingBalance, 2));
                }
                
                // Validate new payment amount
                if ($newPaymentAmount <= 0) {
                    abort(422, 'No additional payment needed. Bill already has Rs ' . number_format($currentPaid, 2) . ' paid.');
                }
                
                if (($currentPaid + $newPaymentAmount) > $billAmount) {
                    abort(422, 'Payment would exceed bill amount. Maximum total payment: Rs ' . number_format($billAmount, 2));
                }
                
            } elseif ((float)$bill->paidAmount > 0) {
                // Bill already has payment amount (from customer payment) - just approve it
                $bill->paymentMethod = $data['paymentMethod'];
                
                // Find latest pending payment for this rental and approve it
                $pendingPayment = $this->paymentService->latestPendingPaymentForRental('house', $bill->id);
                if ($pendingPayment) {
                    $this->paymentService->approve('house', $pendingPayment->id);
                } else {
                    // Fallback to old approval method if no payment record found
                    $this->billingService->approveCustomerPayment(
                        'house', 
                        $bill->id, 
                        $data['paymentMethod'], 
                        $receiptPath
                    );
                }
                
                $isApprovalOnly = true;
                return;
                
            } elseif ((float)$bill->paidAmount < (float)$bill->billAmount && $bill->status !== 'PartPayment') {
                // Auto-fill for first-time payments (any method), not subsequent partial payments
                // Cap to max payable amount to prevent overpayment
                $targetAmount = min((float)$bill->billAmount, $maxPayable);
                $newPaymentAmount = max(0, $targetAmount - (float)$bill->paidAmount);
            }

            if ($newPaymentAmount <= 0) {
                abort(422, 'No outstanding amount to pay or invalid payment amount.');
            }

            $bill->paymentMethod = $data['paymentMethod'];

            // Respect original payment time when customer paid (preserve month attribution)
            $paymentDate = $bill->customer_paid_at ?: now();

            // Use new payment service for admin-initiated payment
            $payment = $this->paymentService->make('house', $bill->id, [
                'amount' => $newPaymentAmount,
                'method' => $data['paymentMethod'],
                'receipt' => $receiptPath,
                'isCustomerFlow' => false // Admin payment, auto-approve
            ]);
            
            // Immediately approve the payment
            $this->paymentService->approve('house', $payment->id);
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
