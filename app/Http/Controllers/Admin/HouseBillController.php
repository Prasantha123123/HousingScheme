<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HouseBillController extends Controller
{
 public function index(Request $request)
{
    $bills = HouseRental::query()
        ->when($request->filled('month'),   fn($q)    => $q->where('month',  $request->input('month')))
        ->when($request->filled('status'),  fn($q)    => $q->where('status', $request->input('status')))
        ->when($request->filled('houseNo'), fn($q)    => $q->where('houseNo',$request->input('houseNo')))
        // IMPORTANT: don't use $request->method (conflicts with Request::$method)
        ->when($request->filled('method'),  fn($q)    => $q->where('paymentMethod', $request->input('method')))
        ->orderByDesc('timestamp')
        ->paginate(15)
        ->withQueryString();

    return view('admin.house_bills.index', compact('bills'));
}


    public function generate(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $unitPrice = (float) Setting::get('water_unit_price', 0);
        $sewerage  = (float) Setting::get('sewerage_charge', 0);

        $houses = House::all();
        foreach ($houses as $h) {
            $existing = HouseRental::where('houseNo',$h->houseNo)->where('month',$month)->first();
            if ($existing) continue;

            $prevMonth = Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
            $prevReading = HouseRental::where('houseNo',$h->houseNo)->where('month',$prevMonth)->first();

            $opening = $prevReading->readingUnit ?? 0;
            $current = $opening; // will be updated later by readings collector

            // Carry forward: sum of unpaid before this month
            $prevUnpaid = HouseRental::where('houseNo',$h->houseNo)
                ->where('month','<',$month)
                ->get()->sum(fn($r)=>max(0,($r->billAmount - $r->paidAmount)));

            $usage = max(0, $current - $opening);
            $billAmount = $sewerage + ($usage * $unitPrice) + $prevUnpaid;

            HouseRental::create([
                'houseNo' => $h->houseNo,
                'readingUnit' => $current,
                'month' => $month,
                'openingReadingUnit' => $opening,
                'billAmount' => $billAmount,
                'paidAmount' => 0,
                'paymentMethod' => null,
                'recipt' => null,
                'status' => 'Pending',
                'timestamp' => now(),
            ]);
        }

        return back()->with('success','Bills generated for '.$month);
    }
}
