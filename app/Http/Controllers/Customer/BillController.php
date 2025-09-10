<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $r)
    {
        $houseNos = House::where('HouseOwneId', auth()->id())->pluck('houseNo');
        $bills = HouseRental::whereIn('houseNo', $houseNos)->orderByDesc('month')->paginate(20);
        return view('customer.bills.index', compact('bills'));
    }
}
