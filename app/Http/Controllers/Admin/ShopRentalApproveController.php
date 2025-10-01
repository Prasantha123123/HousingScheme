<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShopRentalApproveController extends Controller
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
                'ids.*'         => ['integer','exists:ShopRental,id'],
                'paymentMethod' => ['required', Rule::in(['cash','card','online'])],
            ]);

            $processed = 0;
            $skipped   = 0;

            DB::transaction(function () use ($data, &$processed, &$skipped) {
                // Lock all selected rows
                $rows = ShopRental::whereIn('id', $data['ids'])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Determine the latest NOT-approved row per shop
                $latestPerShop = [];
                foreach ($rows as $row) {
                    $latest = ShopRental::where('shopNumber', $row->shopNumber)
                        ->where('status', '!=', 'Approved')
                        ->orderBy('month', 'desc')
                        ->lockForUpdate()
                        ->first();

                    if ($latest) {
                        $latestPerShop[$row->shopNumber] = $latest->id;
                    }
                }

                foreach ($rows as $row) {
                    // Only process if this row IS the latest outstanding for that shop
                    if (($latestPerShop[$row->shopNumber] ?? null) !== $row->id) {
                        $skipped++;
                        continue;
                    }

                    // Calculate maximum payable amount (prevents overpayment)
                    $maxPayable = $this->billingService->getMaxPayableAmount('shop', $row->shopNumber, $row->month);
                    
                    $newPaymentAmount = 0;
                    
                    // If no amount recorded yet and bulk is CASH, cap to max payable
                    if ((float)$row->paidAmount <= 0 && $data['paymentMethod'] === 'cash') {
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
                    $this->billingService->processShopPayment(
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
            /** @var ShopRental $rental */
            $rental = ShopRental::lockForUpdate()->findOrFail($id);

            // Must be the latest outstanding rental for this shop
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

            // Calculate maximum payable amount (prevents overpayment)
            $maxPayable = $this->billingService->getMaxPayableAmount('shop', $rental->shopNumber, $rental->month);
            
            $newPaymentAmount = 0;

            // Use explicit amount if provided; else auto-fill based on payment method and current state
            if (array_key_exists('paidAmount', $data) && $data['paidAmount'] !== null) {
                // For subsequent partial payments, this is the NEW payment amount to add
                $newPaymentAmount = (float)$data['paidAmount'];
                
                // Check if adding this would exceed max payable
                $currentPaid = (float)$rental->paidAmount;
                $maxNewPayment = max(0, $maxPayable - $currentPaid);
                $newPaymentAmount = min($newPaymentAmount, $maxNewPayment);
                
            } elseif ((float)$rental->paidAmount > 0) {
                // Rental already has payment amount (from customer payment) - process the existing amount
                $newPaymentAmount = (float)$rental->paidAmount;
                // Reset paidAmount to 0 so the service can properly allocate it
                $rental->paidAmount = 0;
                $rental->save();
                
            } elseif ((float)$rental->paidAmount < (float)$rental->billAmount && $rental->status !== 'PartPayment') {
                // Auto-fill for first-time payments (any method), not subsequent partial payments
                // Cap to max payable amount to prevent overpayment
                $targetAmount = min((float)$rental->billAmount, $maxPayable);
                $newPaymentAmount = max(0, $targetAmount - (float)$rental->paidAmount);
            }

            if ($newPaymentAmount <= 0) {
                return back()->withErrors(['error' => 'No outstanding amount to pay or invalid payment amount.']);
            }

            $rental->paymentMethod = $data['paymentMethod'];

            // Use unified billing service to process the NEW payment amount
            $this->billingService->processShopPayment(
                $rental, 
                $newPaymentAmount, 
                $data['paymentMethod'], 
                $receiptPath,
                now()
            );
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
