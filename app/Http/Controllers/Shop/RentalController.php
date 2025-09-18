<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;

/**
 * Shop Rentals Controller for Shop Guard Authentication
 * 
 * Handles rental display and management for shops that authenticate
 * directly via the shop guard (not through user accounts).
 */
class RentalController extends Controller
{
    /**
     * Display rentals for the authenticated shop.
     * 
     * Works with auth('shop') - the authenticated entity is a Shop model.
     */
    public function index(Request $request)
    {
        // Get the authenticated shop from the shop guard
        $shop = auth('shop')->user();
        
        if (!$shop) {
            abort(401, 'Shop authentication required');
        }

        // Get all rentals for this specific shop
        $rentals = ShopRental::where('shopNumber', $shop->shopNumber)
            ->orderByDesc('month')
            ->paginate(20);

        return view('shop.rentals', compact('rentals', 'shop'));
    }
}