<?php

namespace App\Http\Controllers\House;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use Illuminate\Http\Request;

/**
 * House Bills Controller for House Guard Authentication
 * 
 * Handles bill display and management for houses that authenticate
 * directly via the house guard (not through user accounts).
 */
class BillController extends Controller
{
    /**
     * Display bills for the authenticated house.
     * 
     * Works with auth('house') - the authenticated entity is a House model.
     */
    public function index(Request $request)
    {
        // Get the authenticated house from the house guard
        $house = auth('house')->user();
        
        if (!$house) {
            abort(401, 'House authentication required');
        }

        // Get all bills for this specific house
        $bills = HouseRental::where('houseNo', $house->houseNo)
            ->orderByDesc('month')
            ->paginate(20);

        return view('house.bills', compact('bills', 'house'));
    }
}