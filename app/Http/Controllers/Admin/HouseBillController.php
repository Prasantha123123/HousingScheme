<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseRental;
use App\Models\Setting;
use App\Models\WaterReading;
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class HouseBillController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function index(Request $request)
    {
        $bills = HouseRental::with('payments') // Load payment relationships
            ->when($request->filled('month'), fn($q) => $q->where('month', $request->string('month')))
            ->when($request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $toDate = $request->date('to_date');
                $q->whereBetween('timestamp', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            })
            ->when($request->filled('from_date') && !$request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $q->where('timestamp', '>=', $fromDate->startOfDay());
            })
            ->when(!$request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $toDate = $request->date('to_date');
                $q->where('timestamp', '<=', $toDate->endOfDay());
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('houseNo'), fn($q) => $q->where('houseNo', $request->string('houseNo')))
            ->when($request->filled('method'), fn($q) => $q->where('paymentMethod', $request->string('method')))
            ->orderByDesc('timestamp')
            ->orderBy('houseNo')
            ->paginate(15)
            ->withQueryString();

        // Calculate remaining balance for each bill
        $bills->getCollection()->transform(function ($bill) {
            // Calculate total due amount
            $totalDue = $this->billingService->getMaxPayableAmount('house', $bill->houseNo, $bill->month);
            // Calculate remaining balance (what can still be paid)
            $bill->maxPayable = max(0, $totalDue - (float)$bill->paidAmount);
            return $bill;
        });

        return view('admin.house_bills.index', compact('bills'));
    }

    /**
     * Show individual bill details
     */
    public function show($id)
    {
        $bill = HouseRental::with(['payments', 'house'])->findOrFail($id);

        // Calculate usage and other details
        $usage = max(0, ($bill->readingUnit - $bill->openingReadingUnit));
        $balance = max(0, (float)$bill->billAmount - (float)$bill->paidAmount);

        // Get settings for calculations
        $unitPrice = (float) Setting::get('water_unit_price', 0);
        $sewerage = (float) Setting::get('sewerage_charge', 0);
        $service = (float) Setting::get('service_charge', 0);

        // Calculate total due amount
        $totalDue = $this->billingService->getMaxPayableAmount('house', $bill->houseNo, $bill->month);
        $maxPayable = max(0, $totalDue - (float)$bill->paidAmount);

        return response()->json([
            'bill' => $bill,
            'usage' => $usage,
            'balance' => $balance,
            'unitPrice' => $unitPrice,
            'sewerage' => $sewerage,
            'service' => $service,
            'maxPayable' => $maxPayable,
        ]);
    }

    /**
     * Generate bills for every house for the given month.
     * billAmount = sewerage + service + usage * unitPrice  (THIS MONTH ONLY)
     */
    public function generate(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));   // 'YYYY-MM'
        $unitPrice = (float) Setting::get('water_unit_price', 0);
        $sewerage  = (float) Setting::get('sewerage_charge', 0);
        $service   = (float) Setting::get('service_charge', 0);

        $billsGenerated = 0;

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
                'houseNo'            => $h->houseNo,
                'readingUnit'        => $current,
                'month'              => $month,
                'openingReadingUnit' => $opening,
                'billAmount'         => $thisMonthCharge,  // only this month’s charge
                'paidAmount'         => 0,
                'paymentMethod'      => null,
                'recipt'             => null,
                'status'             => 'Pending',
                'timestamp'          => now(),
            ]);

            $billsGenerated++;

            if ($reading && $reading->status !== 'Approved') {
                $reading->status = 'Approved';
                $reading->save();
            }
        }

        $message = $billsGenerated > 0
            ? "Successfully generated {$billsGenerated} bills for {$month}. Showing all bills."
            : "No new bills generated for {$month}. All bills may already exist for this month.";

        return redirect()->route('admin.house-bills.index')
            ->with('success', $message);
    }

    /**
     * Download PDF of house bills with applied filters
     */
    public function downloadPdf(Request $request)
    {
        // Use the same filtering logic as the index method but without pagination
        $bills = HouseRental::query()
            ->when($request->filled('month'), fn($q) => $q->where('month', $request->string('month')))
            ->when($request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $toDate = $request->date('to_date');
                $q->whereBetween('timestamp', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            })
            ->when($request->filled('from_date') && !$request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $q->where('timestamp', '>=', $fromDate->startOfDay());
            })
            ->when(!$request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $toDate = $request->date('to_date');
                $q->where('timestamp', '<=', $toDate->endOfDay());
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('houseNo'), fn($q) => $q->where('houseNo', $request->string('houseNo')))
            ->when($request->filled('method'), fn($q) => $q->where('paymentMethod', $request->string('method')))
            ->orderByDesc('timestamp')
            ->get();

        // Get settings for calculations
        $unitPrice = (float) Setting::get('water_unit_price', 0);
        $sewerage  = (float) Setting::get('sewerage_charge', 0);
        $service   = (float) Setting::get('service_charge', 0);

        // Calculate totals
        $totalBillAmount = $bills->sum('billAmount');
        $totalPaidAmount = $bills->sum('paidAmount');
        $totalBalance = $bills->sum(function($bill) {
            return max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
        });

        // Generate filters text for PDF header
        $filters = [];
        if ($request->filled('month')) {
            $filters[] = 'Month: ' . $request->string('month');
        }
        if ($request->filled('from_date')) {
            $filters[] = 'From: ' . $request->string('from_date');
        }
        if ($request->filled('to_date')) {
            $filters[] = 'To: ' . $request->string('to_date');
        }
        if ($request->filled('status')) {
            $filters[] = 'Status: ' . $request->string('status');
        }
        if ($request->filled('houseNo')) {
            $filters[] = 'House: ' . $request->string('houseNo');
        }
        if ($request->filled('method')) {
            $filters[] = 'Method: ' . ucfirst($request->string('method'));
        }
        $filtersText = empty($filters) ? 'All Records' : implode(', ', $filters);

        $pdf = Pdf::loadView('admin.house_bills.pdf', compact(
            'bills', 'unitPrice', 'sewerage', 'service',
            'totalBillAmount', 'totalPaidAmount', 'totalBalance', 'filtersText'
        ))->setPaper('a4', 'landscape');

        $filename = 'house-bills-' . now()->format('Y-m-d-H-i-s') . '.pdf';

        return $pdf->download($filename);
    }
}
