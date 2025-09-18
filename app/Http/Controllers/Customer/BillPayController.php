<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillPayController extends Controller
{
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

        // Optional: cap to outstanding (carry + current - already paid). If you prefer to allow
        // overpayments (to become credit on approval), remove the min() line and just add $amount.
        $carry = HouseRental::where('houseNo', $latest->houseNo)
            ->where('month', '<', $latest->month)
            ->get()
            ->sum(fn (HouseRental $r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $totalDue     = (float)$latest->billAmount + $carry;
        $alreadyPaid  = (float)$latest->paidAmount;
        $outstanding  = max(0, $totalDue - $alreadyPaid);
        $toApply      = min($amount, $outstanding); // prevent accidental overpay; remove if you want to allow credit

        if ($toApply <= 0) {
            return back()->withErrors(['amount' => 'Nothing outstanding to pay.']);
        }

        DB::transaction(function () use ($latest, $method, $receiptPath, $toApply) {
            $latest->paymentMethod     = $method;
            if ($receiptPath) {
                $latest->recipt = $receiptPath;
            }
            // Add this payment to whatever was already paid (supports multiple part-payments)
            $latest->paidAmount       = (float)$latest->paidAmount + $toApply;
            $latest->status           = 'InProgress';   // waiting for admin approval
            $latest->customer_paid_at = now();
            $latest->save();
        });

        return back()->with('success', 'Payment recorded. Admin will approve it shortly.');
    }
}
