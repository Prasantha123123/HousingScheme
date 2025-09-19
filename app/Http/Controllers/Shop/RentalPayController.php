<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shop Rental Payment Controller for Shop Guard Authentication
 * 
 * Handles rental payments for shops that authenticate directly 
 * via the shop guard (not through user accounts).
 */
class RentalPayController extends Controller
{
    /** Bank transfer (receipt upload) – record payment on latest rental ONLY incl. carry */
    public function transfer(Request $request, int $id)
    {
        $shop = auth('shop')->user();
        
        if (!$shop) {
            abort(401, 'Shop authentication required');
        }

        $data = $request->validate([
            'amount'    => ['required','numeric','min:0.01'],
            'reference' => ['required','string','max:100'],
            'recipt'    => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
            'note'      => ['nullable','string','max:500'],
        ]);

        $rental = ShopRental::findOrFail($id);
        
        // Verify this rental belongs to the authenticated shop
        if ($rental->shopNumber !== $shop->shopNumber) {
            abort(403, 'This rental does not belong to your shop');
        }

        $receiptPath = $request->file('recipt')->store('receipts', 'public');

        return $this->payLatestWithCarry($rental, 'online', $receiptPath, (float)$data['amount']);
    }

    /** Card payment – record payment on latest rental ONLY incl. carry */
    public function card(Request $request, int $id)
    {
        $shop = auth('shop')->user();
        
        if (!$shop) {
            abort(401, 'Shop authentication required');
        }

        $data = $request->validate([
            'amount' => ['required','numeric','min:0.01'],
        ]);

        $rental = ShopRental::findOrFail($id);
        
        // Verify this rental belongs to the authenticated shop
        if ($rental->shopNumber !== $shop->shopNumber) {
            abort(403, 'This rental does not belong to your shop');
        }

        return $this->payLatestWithCarry($rental, 'card', null, (float)$data['amount']);
    }

    /**
     * Record a payment on the latest month that covers (part of) carry + current.
     * Earlier months stay unchanged; admin approval will reconcile statuses.
     * Row becomes InProgress until approved.
     */
    protected function payLatestWithCarry(ShopRental $latest, string $method, ?string $receiptPath, float $amount)
    {
        // You can only pay the latest outstanding rental for that shop
        $latestOpen = ShopRental::where('shopNumber', $latest->shopNumber)
            ->where('status', '!=', 'Approved')
            ->orderByDesc('month')
            ->first();

        if (!$latestOpen || $latestOpen->id !== $latest->id) {
            return back()->withErrors(['amount' => 'You can only pay the latest outstanding rental.']);
        }

        // Calculate carry from previous unpaid months
        $carry = ShopRental::where('shopNumber', $latest->shopNumber)
            ->where('month', '<', $latest->month)
            ->get()
            ->sum(fn (ShopRental $r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $totalDue = (float)$latest->billAmount + $carry;
        $alreadyPaid = (float)$latest->paidAmount;
        $outstanding = max(0, $totalDue - $alreadyPaid);
        $toApply = min($amount, $outstanding); // prevent accidental overpay

        if ($toApply <= 0) {
            return back()->withErrors(['amount' => 'Nothing outstanding to pay.']);
        }

        try {
            DB::transaction(function () use ($latest, $method, $receiptPath, $toApply) {
                $latest->paymentMethod = $method;
                if ($receiptPath) {
                    $latest->recipt = $receiptPath;
                }
                // Add this payment to whatever was already paid (supports multiple part-payments)
                $latest->paidAmount = (float)$latest->paidAmount + $toApply;
                $latest->status = 'Pending';   // waiting for admin approval
                $latest->customer_paid_at = now();
                $latest->save();
            });

            return back()->with('success', 'Payment recorded. Admin will approve it shortly.');
        } catch (\Exception $e) {
            Log::error('Payment failed for shop ' . $latest->shopNumber . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
        }
    }
}