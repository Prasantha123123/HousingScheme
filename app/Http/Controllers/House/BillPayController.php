<?php

namespace App\Http\Controllers\House;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use App\Services\UnifiedBillingService;
use App\Services\Payments\UnifiedPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * House Bill Payment Controller for House Guard Authentication
 * 
 * Handles bill payments for houses that authenticate directly 
 * via the house guard (not through user accounts).
 */
class BillPayController extends Controller
{
    private UnifiedBillingService $billingService;
    private UnifiedPaymentService $paymentService;

    public function __construct(UnifiedBillingService $billingService, UnifiedPaymentService $paymentService)
    {
        $this->billingService = $billingService;
        $this->paymentService = $paymentService;
    }

    /** Bank transfer (receipt upload) – record payment on latest bill ONLY incl. carry */
    public function transfer(Request $request, int $id)
    {
        $house = auth('house')->user();
        
        if (!$house) {
            abort(401, 'House authentication required');
        }

        $data = $request->validate([
            'amount'    => ['required','numeric','min:0.01'],
            'reference' => ['required','string','max:100'],
            'recipt'    => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
            'note'      => ['nullable','string','max:500'],
        ]);

        $bill = HouseRental::findOrFail($id);
        
        // Verify this bill belongs to the authenticated house
        if ($bill->houseNo !== $house->houseNo) {
            abort(403, 'This bill does not belong to your house');
        }

        $receiptPath = $request->file('recipt')->store('receipts', 'public');

        return $this->payLatestWithCarry($bill, 'online', $receiptPath, (float)$data['amount']);
    }

    /** Card payment – record payment on latest bill ONLY incl. carry */
    public function card(Request $request, int $id)
    {
        $house = auth('house')->user();
        
        if (!$house) {
            abort(401, 'House authentication required');
        }

        $data = $request->validate([
            'amount' => ['required','numeric','min:0.01'],
        ]);

        $bill = HouseRental::findOrFail($id);
        
        // Verify this bill belongs to the authenticated house
        if ($bill->houseNo !== $house->houseNo) {
            abort(403, 'This bill does not belong to your house');
        }

        return $this->payLatestWithCarry($bill, 'card', null, (float)$data['amount']);
    }

    /**
     * Record a payment on the latest month that covers (part of) carry + current.
     * Earlier months stay unchanged; admin approval will reconcile statuses.
     * Row becomes InProgress until approved.
     */
    protected function payLatestWithCarry(HouseRental $latest, string $method, ?string $receiptPath, float $amount)
    {
        // You can only pay the latest outstanding bill for that house
        $latestOpen = HouseRental::where('houseNo', $latest->houseNo)
            ->where('status', '!=', 'Approved')
            ->orderByDesc('month')
            ->first();

        if (!$latestOpen || $latestOpen->id !== $latest->id) {
            return back()->withErrors(['error' => 'You can only pay the latest outstanding bill.']);
        }

        try {
            // Calculate maximum payable amount (prevents overpayment)
            $maxPayable = $this->billingService->getMaxPayableAmount('house', $latest->houseNo, $latest->month);
            $alreadyPaid = (float)$latest->paidAmount;
            $outstanding = max(0, $maxPayable - $alreadyPaid);
            
            // Cap payment to outstanding amount
            $toApply = min($amount, $outstanding);

            if ($toApply <= 0) {
                return back()->withErrors(['error' => 'Nothing outstanding to pay.']);
            }

            // Use new payment service to record customer payment
            $payment = $this->paymentService->make('house', $latest->id, [
                'amount' => $toApply,
                'method' => $method,
                'receipt' => $receiptPath,
                'isCustomerFlow' => true // Customer payment, requires admin approval
            ]);

            return back()->with('success', "Payment of $toApply recorded successfully. Awaiting admin approval.");
        } catch (\Exception $e) {
            Log::error('Payment failed for house ' . $latest->houseNo . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
        }
    }
}