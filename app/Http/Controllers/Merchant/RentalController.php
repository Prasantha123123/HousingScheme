<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function index(Request $r)
    {
        // Handle both merchant users and direct shop authentication
        if (auth('shop')->check()) {
            // Direct shop authentication
            $shop = auth('shop')->user();
            $shopNumbers = [$shop->shopNumber];
        } elseif (auth()->check() && auth()->user()->role === 'Merchant') {
            // Merchant user authentication
            $shopNumbers = Shop::where('MerchantId', auth()->id())->pluck('shopNumber');
        } else {
            abort(401, 'Authentication required');
        }
        
        $rentals = ShopRental::whereIn('shopNumber', $shopNumbers)->orderByDesc('month')->paginate(20);
        
        // Calculate carry forward amounts for each rental (similar to house bills)
        $calc = [];
        $runningOutstanding = [];
        
        foreach ($shopNumbers as $shopNumber) {
            $shopRentals = ShopRental::where('shopNumber', $shopNumber)
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
        }
        
        // Find latest pending bill for each shop
        $latestPending = [];
        foreach ($shopNumbers as $shopNumber) {
            $latest = ShopRental::where('shopNumber', $shopNumber)
                ->where('status', '!=', 'Approved')
                ->orderByDesc('month')
                ->first();
            if ($latest) {
                $latestPending[$shopNumber] = $latest->id;
            }
        }
        
        // Determine which view to use based on authentication method
        $viewName = auth('shop')->check() ? 'shop.rentals' : 'merchant.rentals.index';
        
        return view($viewName, compact('rentals', 'calc', 'latestPending'));
    }
}
