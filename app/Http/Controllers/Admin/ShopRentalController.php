<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopRentalController extends Controller
{
    public function index(Request $r)
    {
        $rows = ShopRental::with(['shop.merchant:id,name'])
            ->when($r->filled('month'),      fn ($q) => $q->where('month', $r->string('month')))
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

        return redirect()
            ->route('admin.shop-rentals.index', ['month' => $month])
            ->with('success', "Created {$created} rental(s); skipped {$skipped} existing for {$month}.");
    }
}
