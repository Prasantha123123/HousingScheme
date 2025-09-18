<?php

namespace App\Http\Controllers\House;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
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
            DB::transaction(function () use ($latest, $method, $receiptPath, $amount) {
                $currentPaidAmount = (float)$latest->paidAmount;
                $newPaidAmount = $currentPaidAmount + $amount;
                
                $latest->update([
                    'paidAmount'        => $newPaidAmount,
                    'paymentMethod'     => $method,
                    'recipt'            => $receiptPath,
                    'status'            => 'InProgress',
                    'customer_paid_at'  => now(),
                ]);
            });

            return back()->with('success', "Payment of $amount recorded successfully. Awaiting admin approval.");
        } catch (\Exception $e) {
            Log::error('Payment failed for house ' . $latest->houseNo . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
        }
    }
}