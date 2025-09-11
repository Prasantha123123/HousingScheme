<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use App\Models\Setting;
use Illuminate\Http\Request;

class HouseBillController extends Controller
{
    public function index(Request $request)
    {
        $bills = HouseRental::query()
            ->when($request->filled('month'),   fn($q) => $q->where('month',  $request->string('month')))
            ->when($request->filled('status'),  fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('houseNo'), fn($q) => $q->where('houseNo',$request->string('houseNo')))
            ->when($request->filled('method'),  fn($q) => $q->where('paymentMethod', $request->string('method')))
            ->orderByDesc('timestamp')
            ->paginate(15)
            ->withQueryString();

        return view('admin.house_bills.index', compact('bills'));
    }

    public function generate(Request $request)
    {
        $month     = $request->input('month', now()->format('Y-m'));
        $unitPrice = (float) Setting::get('water_unit_price', 0);
        $sewerage  = (float) Setting::get('sewerage_charge', 0);

        foreach (House::all(['houseNo']) as $h) {
            if (HouseRental::where('houseNo', $h->houseNo)->where('month', $month)->exists()) {
                continue;
            }

            $prevRow = HouseRental::where('houseNo', $h->houseNo)
                ->orderByDesc('month')
                ->first();

            $opening = $prevRow?->readingUnit ?? 0;
            $current = $opening;                      // to be updated later by meter reading flow
            $usage   = max(0, $current - $opening);
            $thisMonth = $sewerage + ($usage * $unitPrice);

            HouseRental::create([
                'houseNo'            => $h->houseNo,
                'readingUnit'        => $current,
                'month'              => $month,     // YYYY-MM
                'openingReadingUnit' => $opening,
                'billAmount'         => $thisMonth,
                'paidAmount'         => 0,
                'paymentMethod'      => null,
                'recipt'             => null,
                'status'             => 'Pending',
                'timestamp'          => now(),
            ]);
        }

        return back()->with('success', "Bills generated for {$month}");
    }
}
