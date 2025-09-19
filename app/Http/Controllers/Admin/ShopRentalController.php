<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ShopRentalController extends Controller
{
    public function index(Request $r)
    {
        $rows = ShopRental::with(['shop.merchant:id,name'])
            ->when($r->filled('month'),      fn ($q) => $q->where('month', $r->string('month')))
            ->when($r->filled('from_date') && $r->filled('to_date'), function($q) use ($r) {
                $fromDate = $r->date('from_date');
                $toDate = $r->date('to_date');
                $q->whereBetween('timestamp', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            })
            ->when($r->filled('from_date') && !$r->filled('to_date'), function($q) use ($r) {
                $fromDate = $r->date('from_date');
                $q->where('timestamp', '>=', $fromDate->startOfDay());
            })
            ->when(!$r->filled('from_date') && $r->filled('to_date'), function($q) use ($r) {
                $toDate = $r->date('to_date');
                $q->where('timestamp', '<=', $toDate->endOfDay());
            })
            ->when($r->filled('shopNumber'), fn ($q) => $q->where('shopNumber', $r->string('shopNumber')))
            ->when($r->filled('status'),     fn ($q) => $q->where('status', $r->string('status')))
            // NEW: filter by payment method (cash/card/online)
            ->when($r->filled('method'),     fn ($q) => $q->where('paymentMethod', $r->string('method')))
            ->orderByDesc('timestamp')
            ->paginate(15)
            ->withQueryString()
            ->through(function (ShopRental $rental) {
                // expose merchant name directly to the blade
                $rental->merchant_name = optional(optional($rental->shop)->merchant)->name;
                return $rental;
            });

        return view('admin.shop_rentals.index', compact('rows'));
    }

    /**
     * Generate shop-rental rows for a given month.
     * - Creates rows that don't exist yet.
     * - DOES NOT modify existing rows (so you never wipe paid/status data).
     */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable','regex:/^\d{4}-(0[1-9]|1[0-2])$/'], // YYYY-MM
        ]);

        $month = $data['month'] ?? now()->format('Y-m');

        $shops = Shop::query()->get(['shopNumber', 'rentalAmount']);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($shops, $month, &$created, &$skipped) {
            foreach ($shops as $shop) {
                $exists = ShopRental::where('shopNumber', $shop->shopNumber)
                    ->where('month', $month)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue; // never touch existing (could be InProgress/Approved/etc)
                }

                ShopRental::create([
                    'shopNumber'    => $shop->shopNumber,
                    'billAmount'    => (float) $shop->rentalAmount,
                    'month'         => $month,
                    'paidAmount'    => 0,
                    'paymentMethod' => null,
                    'recipt'        => null,
                    'status'        => 'Pending',
                    'timestamp'     => now(),
                ]);

                $created++;
            }
        });

        return redirect()->back()->with('success', "Generated {$created} rental entries for {$month}. Skipped {$skipped} existing.");
    }

    /**
     * Download PDF of shop rentals with applied filters
     */
    public function downloadPdf(Request $request)
    {
        // Use the same filtering logic as the index method but without pagination
        $rows = ShopRental::with(['shop.merchant:id,name'])
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->string('month')))
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
            ->when($request->filled('shopNumber'), fn ($q) => $q->where('shopNumber', $request->string('shopNumber')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('method'), fn ($q) => $q->where('paymentMethod', $request->string('method')))
            ->orderByDesc('timestamp')
            ->get()
            ->map(function (ShopRental $rental) {
                // expose merchant name directly to the blade
                $rental->merchant_name = optional(optional($rental->shop)->merchant)->name;
                return $rental;
            });

        // Calculate totals
        $totalBillAmount = $rows->sum('billAmount');
        $totalPaidAmount = $rows->sum('paidAmount');
        $totalBalance = $rows->sum(function($rental) {
            return max(0, (float)$rental->billAmount - (float)$rental->paidAmount);
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
        if ($request->filled('shopNumber')) {
            $filters[] = 'Shop: ' . $request->string('shopNumber');
        }
        if ($request->filled('method')) {
            $filters[] = 'Method: ' . ucfirst($request->string('method'));
        }
        $filtersText = empty($filters) ? 'All Records' : implode(', ', $filters);

        $pdf = Pdf::loadView('admin.shop_rentals.pdf', compact(
            'rows', 'totalBillAmount', 'totalPaidAmount', 'totalBalance', 'filtersText'
        ))->setPaper('a4', 'landscape');

        $filename = 'shop-rentals-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }
}
