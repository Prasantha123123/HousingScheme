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
            'reference' => ['required','string','max:100'],   // kept for UI; not stored
            'recipt'    => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
            'note'      => ['nullable','string','max:500'],
        ]);

        $latest = HouseRental::findOrFail($id);
        $receiptPath = $r->file('recipt')->store('receipts', 'public');

        return $this->payLatestWithCarry($latest, 'online', $receiptPath);
    }

    /** Card – record payment on latest bill ONLY incl. carry */
    public function card(Request $r, int $id)
    {
        $latest = HouseRental::findOrFail($id);
        return $this->payLatestWithCarry($latest, 'card', null);
    }

    /**
     * Record a payment on the latest month that covers all previous unpaid months.
     * Earlier months remain with paidAmount=0; only the latest row gets (carry + current).
     * Status on the latest row becomes InProgress (awaiting admin approval).
     */
    protected function payLatestWithCarry(HouseRental $latest, string $method, ?string $receiptPath)
    {
        // You can only settle the latest outstanding bill for that house
        $hasLaterUnpaid = HouseRental::where('houseNo', $latest->houseNo)
            ->where('month', '>', $latest->month)
            ->whereColumn('paidAmount', '<', 'billAmount')
            ->exists();

        if ($hasLaterUnpaid) {
            return back()->withErrors('You can only pay the latest outstanding bill.');
        }

        // Carry = sum of unpaid amounts BEFORE this month
        $carry = HouseRental::where('houseNo', $latest->houseNo)
            ->where('month', '<', $latest->month)
            ->get()
            ->sum(fn (HouseRental $r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $totalToPay = (float)$latest->billAmount + (float)$carry;

        DB::transaction(function () use ($latest, $method, $receiptPath, $totalToPay) {
            $latest->paymentMethod = $method;
            if ($receiptPath) {
                $latest->recipt = $receiptPath;
            }
            $latest->paidAmount = $totalToPay; // includes carry + current month
            $latest->status     = 'InProgress';  // awaiting admin approval
            $latest->save();
        });

        return back()->with('success', 'Payment recorded. Admin will approve it shortly.');
    }
}
