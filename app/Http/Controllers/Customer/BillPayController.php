<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;

class BillPayController extends Controller
{
    // Online banking upload (pending until admin approves)
    public function transfer(Request $r, $id)
    {
        $data = $r->validate([
            'reference'=>'required|string|max:100', // NOTE: not stored due to fixed schema
            'recipt'=>'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'note'=>'nullable|string|max:500',
        ]);
        $bill = HouseRental::findOrFail($id);
        $bill->paymentMethod = 'online';
        $bill->recipt = $r->file('recipt')->store('receipts','public');
        $bill->paidAmount = $bill->billAmount; // assume full payment
        $bill->status = 'Pending';
        $bill->save();

        return back()->with('success','Receipt uploaded. Awaiting approval.');
    }

    // Card (placeholder gateway) -> status pending for admin
    public function card(Request $r, $id)
    {
        $bill = HouseRental::findOrFail($id);
        $bill->paymentMethod = 'card';
        $bill->paidAmount = $bill->billAmount;
        $bill->status = 'Pending';
        $bill->save();

        return back()->with('success','Card payment initiated (pending approval).');
    }
}
