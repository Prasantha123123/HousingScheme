<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use Illuminate\Http\Request;

class ShopRentalController extends Controller
{
    public function index(Request $r)
    {
        $rows = ShopRental::with(['shop.merchant:id,name'])   // eager load merchant
            ->when($r->month,      fn ($q, $m) => $q->where('month', $m))
            ->when($r->shopNumber, fn ($q, $n) => $q->where('shopNumber', $n))
            ->when($r->status,     fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('timestamp')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($rental) {
                // Add a computed name the blade can use directly
                $rental->merchant_name = optional(optional($rental->shop)->merchant)->name;
                return $rental;
            });

        return view('admin.shop_rentals.index', compact('rows'));
    }

    public function generate(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $shops = Shop::query()->get(['shopNumber', 'rentalAmount']);

        $count = 0;
        foreach ($shops as $shop) {
            ShopRental::updateOrCreate(
                ['shopNumber' => $shop->shopNumber, 'month' => $month],
                [
                    'billAmount'    => (float) $shop->rentalAmount,
                    'paidAmount'    => 0,
                    'paymentMethod' => null,
                    'recipt'        => null,
                    'status'        => 'Pending',
                    'timestamp'     => now(),
                ]
            );
            $count++;
        }

        return redirect()
            ->route('admin.shop-rentals.index', ['month' => $month])
            ->with('success', "Generated/updated {$count} shop rentals for {$month}.");
    }
}
