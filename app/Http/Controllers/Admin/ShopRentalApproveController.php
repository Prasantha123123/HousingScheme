<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;

class ShopRentalApproveController extends Controller
{
    public function approve(Request $req, $id)
    {
        $r = ShopRental::findOrFail($id);
        $r->status = 'Approved';
        if ($req->has('paidAmount')) $r->paidAmount = max(0,(float)$req->input('paidAmount'));
        $r->save();
        return back()->with('success','Approved');
    }

    public function reject(Request $req, $id)
    {
        $req->validate(['reason'=>['required','string','max:500']]);
        $r = ShopRental::findOrFail($id);
        $r->status = 'Rejected';
        $r->save();
        return back()->with('success','Rejected: '.$req->input('reason'));
    }
}
