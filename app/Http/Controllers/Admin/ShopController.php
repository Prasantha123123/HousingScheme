<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            ->paginate(15);

        return view('admin.shops.index', compact('rows'));
    }

    public function create()
    {
        $merchants = User::where('role', 'Merchant')
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.shops.create', compact('merchants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shopNumber'   => ['required','string','max:50', Rule::unique('Shops','shopNumber')],
            'MerchantId'   => [
                'required','integer',
                Rule::exists('users','id')->where(fn($q) => $q->where('role','Merchant')),
            ],
            'leaseEnd'     => ['nullable','date'],
            'rentalAmount' => ['required','numeric','min:0'],
        ]);

        Shop::create($data);

        return redirect()->route('admin.shops.index')->with('success', 'Shop created.');
    }

    public function edit(string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);

        $merchants = User::where('role', 'Merchant')
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.shops.edit', compact('shop','merchants'));
    }

    public function update(Request $request, string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);

        $data = $request->validate([
            // keep PK unchanged; allow updating owner, lease end, and amount
            'MerchantId'   => [
                'required','integer',
                Rule::exists('users','id')->where(fn($q) => $q->where('role','Merchant')),
            ],
            'leaseEnd'     => ['nullable','date'],
            'rentalAmount' => ['required','numeric','min:0'],
        ]);

        $shop->update($data);

        return redirect()->route('admin.shops.index')->with('success', 'Shop updated.');
    }

    public function destroy(string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);

        // Optionally block delete if rentals exist for this shop
        // if (\App\Models\ShopRental::where('shopNumber', $shopNumber)->exists()) {
        //     return back()->withErrors('Cannot delete: rentals exist for this shop.');
        // }

        $shop->delete();

        return redirect()->route('admin.shops.index')->with('success', 'Shop deleted.');
    }
}
