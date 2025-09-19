<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalPayController extends Controller
{
    public function transfer(Request $r, $id)
    {
        $data = $r->validate([
            'amount' => ['required','numeric','min:0.01'],
            'reference'=>'required|string|max:100',
            'recipt'=>'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        
        $latest = ShopRental::findOrFail($id);
        
        // Verify access permissions
        $this->verifyShopAccess($latest->shopNumber);
        
        $receiptPath = $r->file('recipt')->store('receipts','public');
        
        return $this->payLatestWithCarry($latest, 'online', $receiptPath, (float)$data['amount']);
    }

    public function card(Request $r, $id)
    {
        $data = $r->validate([
            'amount' => ['required','numeric','min:0.01'],
        ]);
        
        $latest = ShopRental::findOrFail($id);
        
        // Verify access permissions
        $this->verifyShopAccess($latest->shopNumber);
        
        return $this->payLatestWithCarry($latest, 'card', null, (float)$data['amount']);
    }

    /**
     * Verify that the current user has access to the specified shop
     */
    private function verifyShopAccess(string $shopNumber): void
    {
        if (auth('shop')->check()) {
            // Direct shop authentication - verify shop owns this rental
            $shop = auth('shop')->user();
            if ($shop->shopNumber !== $shopNumber) {
                abort(403, 'This rental does not belong to your shop');
            }
        } elseif (auth()->check() && auth()->user()->role === 'Merchant') {
            // Merchant user authentication - verify merchant owns this shop
            $ownedShops = Shop::where('MerchantId', auth()->id())->pluck('shopNumber');
            if (!$ownedShops->contains($shopNumber)) {
                abort(403, 'This shop does not belong to you');
            }
        } else {
            abort(401, 'Authentication required');
        }
    }

    /**
     * Record a payment on the latest month that covers (part of) carry + current.
     * Earlier months stay unchanged; admin approval will reconcile statuses.
     * Row becomes Pending until approved.
     */
    protected function payLatestWithCarry(ShopRental $latest, string $method, ?string $receiptPath, float $amount)
    {
        // You can only pay the latest outstanding bill for that shop
        $latestOpen = ShopRental::where('shopNumber', $latest->shopNumber)
            ->where('status', '!=', 'Approved')
            ->orderByDesc('month')
            ->first();

        if (!$latestOpen || $latestOpen->id !== $latest->id) {
            return back()->withErrors(['amount' => 'You can only pay the latest outstanding bill.']);
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
    }
}
