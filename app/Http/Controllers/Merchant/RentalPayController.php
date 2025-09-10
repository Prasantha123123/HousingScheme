<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;

class RentalPayController extends Controller
{
    public function transfer(Request $r, $id)
    {
        $r->validate([
            'reference'=>'required|string|max:100',
            'recipt'=>'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $row = ShopRental::findOrFail($id);
        $row->paymentMethod = 'online';
        $row->recipt = $r->file('recipt')->store('receipts','public');
        $row->paidAmount = $row->billAmount;
        $row->status = 'Pending';
        $row->save();
        return back()->with('success','Receipt uploaded. Awaiting approval.');
    }

    public function card(Request $r, $id)
    {
        $row = ShopRental::findOrFail($id);
        $row->paymentMethod = 'card';
        $row->paidAmount = $row->billAmount;
        $row->status = 'Pending';
        $row->save();
        return back()->with('success','Card payment initiated (pending approval).');
    }
}
