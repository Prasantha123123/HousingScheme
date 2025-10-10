<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use App\Services\UnifiedBillingService;
use App\Services\Payments\UnifiedPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShopRentalApproveController extends Controller
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
                'ids.*'         => ['integer','exists:ShopRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $processed = 0;
            $skipped   = 0;

            DB::transaction(function () use ($data, &$processed, &$skipped) {
                $rows = ShopRental::whereIn('id', $data['ids'])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // latest open row per shop
                $latestPerShop = [];
                foreach ($rows as $row) {
                    $latest = ShopRental::where('shopNumber', $row->shopNumber)
                        ->where('status', '!=', 'Approved')
                        ->orderBy('month', 'desc')
                        ->lockForUpdate()
                        ->first();
                    if ($latest) $latestPerShop[$row->shopNumber] = $latest->id;
                }

                foreach ($rows as $row) {
                    if (($latestPerShop[$row->shopNumber] ?? null) !== $row->id) {
                        $skipped++; continue;
                    }

                    $maxPayable = $this->billingService->getMaxPayableAmount('shop', $row->shopNumber, $row->month);
                    $newPaymentAmount = 0;

                    if ((float)$row->paidAmount <= 0 && $data['paymentMethod'] === 'cash') {
                        $targetAmount = min((float)$row->billAmount, $maxPayable);
                        $newPaymentAmount = max(0, $targetAmount - (float)$row->paidAmount);
                    } else {
                        $skipped++; continue;
                    }

                    if ($newPaymentAmount <= 0) { $skipped++; continue; }

                    $row->paymentMethod = $data['paymentMethod'];

                    // Use new payment service for admin-initiated payment
                    $payment = $this->paymentService->make('shop', $row->id, [
                        'amount' => $newPaymentAmount,
                        'method' => $data['paymentMethod'],
                        'receipt' => null, // No receipt for bulk
                        'isCustomerFlow' => false // Admin payment, auto-approve
                    ]);
                    
                    // Immediately approve the payment
                    $this->paymentService->approve('shop', $payment->id);

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
            /** @var ShopRental $rental */
            $rental = ShopRental::lockForUpdate()->findOrFail($id);

            $latestOpen = ShopRental::where('shopNumber', $rental->shopNumber)
                ->where('status', '!=', 'Approved')
                ->orderBy('month', 'desc')
                ->lockForUpdate()
                ->first();

            if (!$latestOpen || $latestOpen->id !== $rental->id) {
                abort(422, 'Please approve the latest outstanding rental for this shop first.');
            }

            $receiptPath = null;
            if ($request->hasFile('recipt')) {
                $receiptPath = $request->file('recipt')->store('receipts', 'public');
            }

            $maxPayable = $this->billingService->getMaxPayableAmount('shop', $rental->shopNumber, $rental->month);
            $newPaymentAmount = 0;

            if (array_key_exists('paidAmount', $data) && $data['paidAmount'] !== null) {
                // Admin entered a specific amount - this could be either:
                // 1. Total payment amount (if equals billAmount)
                // 2. Additional payment amount (if less than billAmount)
                $adminAmount = (float)$data['paidAmount'];
                $currentPaid = (float)$rental->paidAmount;
                $billAmount = (float)$rental->billAmount;
                
                // Determine if admin entered total amount or additional amount
                if ($adminAmount <= $billAmount && $adminAmount > $currentPaid) {
                    // Admin entered total target payment amount
                    $newPaymentAmount = $adminAmount - $currentPaid;
                } elseif ($adminAmount <= ($billAmount - $currentPaid)) {
                    // Admin entered additional payment amount  
                    $newPaymentAmount = $adminAmount;
                } elseif ($adminAmount == $currentPaid) {
                    // Admin entered same amount as current - just approve existing payment
                    $rental->paymentMethod = $data['paymentMethod'];
                    
                    // Find latest pending payment for this rental and approve it
                    $pendingPayment = $this->paymentService->latestPendingPaymentForRental('shop', $rental->id);
                    if ($pendingPayment) {
                        $this->paymentService->approve('shop', $pendingPayment->id);
                    } else {
                        // Fallback to old approval method if no payment record found
                        $this->billingService->approveCustomerPayment(
                            'shop', 
                            $rental->id, 
                            $data['paymentMethod'], 
                            $receiptPath
                        );
                    }
                    return;
                } else {
                    abort(422, 'Invalid payment amount. Maximum additional payment: Rs ' . number_format($billAmount - $currentPaid, 2));
                }
                
                // Validate new payment amount
                if ($newPaymentAmount <= 0) {
                    abort(422, 'No additional payment needed. Bill already has Rs ' . number_format($currentPaid, 2) . ' paid.');
                }
                
                if (($currentPaid + $newPaymentAmount) > $billAmount) {
                    abort(422, 'Payment would exceed bill amount. Maximum total payment: Rs ' . number_format($billAmount, 2));
                }

            } elseif ((float)$rental->paidAmount > 0) {
                // Bill already has payment amount (from customer payment) - just approve it
                $rental->paymentMethod = $data['paymentMethod'];
                
                // Find latest pending payment for this rental and approve it
                $pendingPayment = $this->paymentService->latestPendingPaymentForRental('shop', $rental->id);
                if ($pendingPayment) {
                    $this->paymentService->approve('shop', $pendingPayment->id);
                } else {
                    // Fallback to old approval method if no payment record found
                    $this->billingService->approveCustomerPayment(
                        'shop', 
                        $rental->id, 
                        $data['paymentMethod'], 
                        $receiptPath
                    );
                }
                return; // Exit early since approval is complete

            } elseif ((float)$rental->paidAmount < (float)$rental->billAmount && $rental->status !== 'PartPayment') {
                $targetAmount = min((float)$rental->billAmount, $maxPayable);
                $newPaymentAmount = max(0, $targetAmount - (float)$rental->paidAmount);
            }

            if ($newPaymentAmount <= 0) {
                return back()->withErrors(['error' => 'No outstanding amount to pay or invalid payment amount.']);
            }

            $rental->paymentMethod = $data['paymentMethod'];

            // Use new payment service for admin-initiated payment
            $payment = $this->paymentService->make('shop', $rental->id, [
                'amount' => $newPaymentAmount,
                'method' => $data['paymentMethod'],
                'receipt' => $receiptPath,
                'isCustomerFlow' => false // Admin payment, auto-approve
            ]);
            
            // Immediately approve the payment
            $this->paymentService->approve('shop', $payment->id);
        });

        return back()->with('success', 'Rental approved.');
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
