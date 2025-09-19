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
        
        // Calculate carry forward amounts for each rental (similar to house bills)
        $calc = [];
        
        $shopRentals = ShopRental::where('shopNumber', $shop->shopNumber)
            ->orderBy('month')
            ->get();
        
        $runningOut = 0;
        foreach ($shopRentals as $rental) {
            $current = (float) $rental->billAmount;
            $carry = $runningOut;
            $total = $carry + $current;
            
            $calc[$rental->id] = [
                'carry' => $carry,
                'current' => $current,
                'total' => $total
            ];
            
            $runningOut = max(0, $total - (float) $rental->paidAmount);
        }
        
        // Find latest pending bill
        $latestPending = ShopRental::where('shopNumber', $shop->shopNumber)
            ->where('status', '!=', 'Approved')
            ->orderByDesc('month')
            ->first();
        
        $latestPendingId = $latestPending ? $latestPending->id : null;

        return view('shop.rentals', compact('rentals', 'shop', 'calc', 'latestPendingId'));
    }
}