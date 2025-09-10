<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function index(Request $r)
    {
        $shopNumbers = Shop::where('MerchantId', auth()->id())->pluck('shopNumber');
        $rentals = ShopRental::whereIn('shopNumber', $shopNumbers)->orderByDesc('month')->paginate(20);
        return view('merchant.rentals.index', compact('rentals'));
    }
}
