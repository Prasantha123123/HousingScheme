<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
// NEW: bring in rentals so we can show latest status/bill like Houses
use App\Models\ShopRental;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = Shop::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where('shopNumber', 'like', "%{$q}%")
                  ->orWhere('MerchantId', 'like', "%{$q}%")
                  ->orWhereHas('merchant', fn($m) => $m->where('name', 'like', "%{$q}%"));
            })
            ->orderBy('shopNumber')
            ->paginate(15)
            // Decorate rows exactly like Houses ->through()
            ->through(function (Shop $s) {
                $latest = ShopRental::where('shopNumber', $s->shopNumber)
                    ->orderByDesc('timestamp')
                    ->first();

                // mirror house fields for view convenience
                $s->merchant_name       = optional($s->merchant)->name ?? 'Unassigned';
                $s->latest_bill_month   = optional($latest)->month;
                $s->latest_bill_amount  = optional($latest)->billAmount;
                $s->latest_status       = optional($latest)->status ?? 'Pending';

                return $s;
            });

        return view('admin.shops.index', compact('rows'));
    }

    public function create()
    {
        $merchants = User::where('role', 'Merchant')
            ->orderBy('name')
            ->get(['id','name','email']);

        return view('admin.shops.create', compact('merchants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shopNumber'   => ['required','string','max:50', Rule::unique('Shops','shopNumber')],
            'MerchantId'   => [
                'nullable','integer',
                Rule::exists('users','id')->where(fn($q) => $q->where('role','Merchant')),
            ],
            'leaseEnd'     => ['nullable','date'],
            'rentalAmount' => ['required','numeric','min:0'],
            'shop_password'=> ['required_without:MerchantId','nullable','string','min:6'],
        ], [
            'shop_password.required_without' => 'Set a shop password when no merchant is selected.',
        ]);

        Shop::create($data);

        return redirect()->route('admin.shops.index')->with('success', 'Shop created.');
    }

    public function edit(string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);

        $merchants = User::where('role', 'Merchant')
            ->orderBy('name')
            ->get(['id','name','email']);

        return view('admin.shops.edit', compact('shop','merchants'));
    }

    public function update(Request $request, string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);

        // Require password on update (as requested to match House)
        $data = $request->validate([
            'MerchantId'    => [
                'nullable','integer',
                Rule::exists('users','id')->where(fn($q) => $q->where('role','Merchant')),
            ],
            'leaseEnd'      => ['nullable','date'],
            'rentalAmount'  => ['required','numeric','min:0'],
            'shop_password' => ['required','string','min:6'],
        ], [
            'shop_password.required' => 'The password field is required when updating.',
        ]);

        $shop->update($data);

        return redirect()->route('admin.shops.index')->with('success', 'Shop updated.');
    }

    public function destroy(string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);
        $shop->delete();

        return redirect()->route('admin.shops.index')->with('success', 'Shop deleted.');
    }
}
