<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                // (No unique rule here → one merchant can own multiple shops if you want)
            ],
            'leaseEnd'     => ['nullable','date'],
            'rentalAmount' => ['required','numeric','min:0'],
            'shop_password'=> ['required_without:MerchantId','nullable','string','min:6'], // required if no merchant
        ], [
            'shop_password.required_without' => 'Set a shop password when no merchant is selected.',
        ]);

        // Handle password creation - store as plain text for admin visibility
        // Note: shop_password is stored as plain text for admin reference

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

        $data = $request->validate([
            'MerchantId'   => [
                'nullable','integer',
                Rule::exists('users','id')->where(fn($q) => $q->where('role','Merchant')),
            ],
            'leaseEnd'     => ['nullable','date'],
            'rentalAmount' => ['required','numeric','min:0'],
            'shop_password'=> ['nullable','string','min:6'], // optional on edit
        ]);

        // Handle password update - store as plain text for admin visibility
        // Note: shop_password is stored as plain text for admin reference
        // If password field is empty, don't update it
        if (empty($data['shop_password'])) {
            unset($data['shop_password']); // don't overwrite with null
        }

        $shop->update($data);

        return redirect()->route('admin.shops.index')->with('success', 'Shop updated.');
    }

    public function destroy(string $shopNumber)
    {
        $shop = Shop::findOrFail($shopNumber);

        // Optional guard:
        // if (\App\Models\ShopRental::where('shopNumber', $shopNumber)->exists()) {
        //     return back()->withErrors('Cannot delete: rentals exist for this shop.');
        // }

        $shop->delete();

        return redirect()->route('admin.shops.index')->with('success', 'Shop deleted.');
    }
}
