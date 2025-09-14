<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use App\Models\Setting;
use App\Models\WaterReading;
use Illuminate\Http\Request;

class HouseBillController extends Controller
{
    public function index(Request $request)
    {
        $bills = HouseRental::query()
            ->when($request->filled('month'), fn($q) => $q->where('month', $request->string('month')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('houseNo'), fn($q) => $q->where('houseNo', $request->string('houseNo')))
            ->when($request->filled('method'), fn($q) => $q->where('paymentMethod', $request->string('method')))
            ->orderByDesc('timestamp')
            ->paginate(15)
            ->withQueryString();

        return view('admin.house_bills.index', compact('bills'));
    }

    /**
     * Generate bills for every house for the given month.
     * Uses WaterReadings if present.
     * billAmount = sewerage + service + usage * unitPrice  (THIS MONTH ONLY)
     */
    public function generate(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));   // 'YYYY-MM'
        $unitPrice = (float) Setting::get('water_unit_price', 0);
        $sewerage = (float) Setting::get('sewerage_charge', 0);
        $service = (float) Setting::get('service_charge', 0);

        foreach (House::all(['houseNo']) as $h) {
            if (HouseRental::where('houseNo', $h->houseNo)->where('month', $month)->exists()) {
                continue; // already generated for this house+month
            }

            // Reading for this month (preferred)
            $reading = WaterReading::where('houseNo', $h->houseNo)
                ->where('month', $month)
                ->first();

            // Previous reading as fallback for opening/current
            $prevReading = WaterReading::where('houseNo', $h->houseNo)
                ->where('month', '<', $month)
                ->orderByDesc('month')
                ->first();

            $opening = $reading?->openingReadingUnit
                ?? $prevReading?->readingUnit
                ?? 0;

            $current = $reading?->readingUnit ?? $opening;

            $usage = max(0, (int) $current - (int) $opening);

            $thisMonthCharge = $sewerage + $service + ($usage * $unitPrice);

            HouseRental::create([
                'houseNo' => $h->houseNo,
                'readingUnit' => $current,
                'month' => $month,
                'openingReadingUnit' => $opening,
                'billAmount' => $thisMonthCharge,  // only this month’s charge
                'paidAmount' => 0,
                'paymentMethod' => null,
                'recipt' => null,
                'status' => 'Pending',
                'timestamp' => now(),
            ]);

            // ✅ Mark the used reading as Approved (if present)
            if ($reading && $reading->status !== 'Approved') {
                $reading->status = 'Approved';
                $reading->save();
            }
        }

        return back()->with('success', "Bills generated for {$month}");
    }
}
