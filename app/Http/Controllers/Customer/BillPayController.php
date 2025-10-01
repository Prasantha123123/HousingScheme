<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillPayController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /** Bank transfer (receipt upload) – record payment on latest bill ONLY incl. carry */
    public function transfer(Request $r, int $id)
    {
        $data = $r->validate([
            'amount'    => ['required','numeric','min:0.01'],
            'reference' => ['required','string','max:100'],   // kept for UI; not stored
            'recipt'    => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
            'note'      => ['nullable','string','max:500'],
        ]);

        $latest = HouseRental::findOrFail($id);
        $receiptPath = $r->file('recipt')->store('receipts', 'public');

        return $this->payLatestWithCarry($latest, 'online', $receiptPath, (float)$data['amount']);
    }

    /** Card – record payment on latest bill ONLY incl. carry */
    public function card(Request $r, int $id)
    {
        $data = $r->validate([
            'amount' => ['required','numeric','min:0.01'],
        ]);

        $latest = HouseRental::findOrFail($id);
        return $this->payLatestWithCarry($latest, 'card', null, (float)$data['amount']);
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
            return back()->withErrors(['amount' => 'You can only pay the latest outstanding bill.']);
        }

        // Calculate maximum payable amount (prevents overpayment)
        $maxPayable = $this->billingService->getMaxPayableAmount('house', $latest->houseNo, $latest->month);
        $alreadyPaid = (float)$latest->paidAmount;
        $outstanding = max(0, $maxPayable - $alreadyPaid);
        
        // Cap payment to outstanding amount
        $toApply = min($amount, $outstanding);

        if ($toApply <= 0) {
            return back()->withErrors(['amount' => 'Nothing outstanding to pay.']);
        }

        try {
            // Use unified billing service to record customer payment
            $this->billingService->recordCustomerPayment(
                'house', 
                $latest->id, 
                $toApply, 
                $method, 
                $receiptPath, 
                now()
            );

            return back()->with('success', 'Payment recorded. Admin will approve it shortly.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
        }
    }
}
