<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseController extends Controller
{
    public function index(Request $r)
    {
        $q = $r->get('q');
        $rows = House::query()
            ->when($q, fn($qry)=>$qry->where('houseNo','like',"%$q%"))
            ->orderBy('houseNo')
            ->paginate(15)
            ->through(function ($h) {
                $latest = HouseRental::where('houseNo', $h->houseNo)->orderByDesc('timestamp')->first();
                $h->owner_name = $h->HouseOwneId;
                $h->latest_bill_month = $latest->month ?? null;
                $h->latest_bill_amount = $latest->billAmount ?? null;
                $h->latest_status = $latest->status ?? 'Pending';
                return $h;
            });

        return view('admin.houses.index', compact('rows'));
    }

    public function show(Request $r, $houseNo)
    {
        $rentals = HouseRental::where('houseNo',$houseNo)->orderByDesc('month')->paginate(20);
        return view('admin.houses.show', compact('rentals','houseNo'));
    }
}
