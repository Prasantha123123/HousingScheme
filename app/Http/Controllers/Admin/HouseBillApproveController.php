<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;

class HouseBillApproveController extends Controller
{
    public function approve(Request $req, $id)
    {
        $data = $req->validate([
            'paidAmount' => ['nullable','numeric','min:0'],
            'paymentMethod' => ['required','in:cash,card,online'],
            'recipt' => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:5120'],
        ]);
        $r = HouseRental::findOrFail($id);
        $r->paidAmount = $data['paidAmount'] ?? $r->billAmount;
        $r->paymentMethod = $data['paymentMethod'];
        if ($req->hasFile('recipt')) {
            $r->recipt = $req->file('recipt')->store('receipts','public');
        }
        $r->status = 'Approved';
        $r->save();

        return back()->with('success','Approved');
    }

    public function reject(Request $req, $id)
    {
        $req->validate(['reason'=>['required','string','max:500']]);
        $r = HouseRental::findOrFail($id);
        $r->status = 'Rejected';
        $r->save();

        return back()->with('success','Rejected: '.$req->input('reason'));
    }
}
