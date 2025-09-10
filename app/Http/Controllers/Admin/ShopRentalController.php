<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;

class ShopRentalController extends Controller
{
    public function index(Request $r)
    {
        $rows = ShopRental::query()
            ->when($r->month, fn($q,$m)=>$q->where('month',$m))
            ->when($r->shopNumber, fn($q,$n)=>$q->where('shopNumber',$n))
            ->when($r->status, fn($q,$s)=>$q->where('status',$s))
            ->orderByDesc('timestamp')->paginate(15)->withQueryString();

        return view('admin.shop_rentals.index', compact('rows'));
    }
}
