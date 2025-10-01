<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRental;
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalPayController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function transfer(Request $r, $id)
    {
        $latest = ShopRental::findOrFail($id);
        
        // Verify access permissions
        $this->verifyShopAccess($latest->shopNumber);
        
        // Calculate outstanding amount for validation using unified service
        $maxPayable = $this->billingService->getMaxPayableAmount('shop', $latest->shopNumber, $latest->month);
        $alreadyPaid = (float)$latest->paidAmount;
        $outstanding = max(0, $maxPayable - $alreadyPaid);
        
        $data = $r->validate([
            'amount' => ['required','numeric','min:0.01', 'max:' . $outstanding],
            'reference'=>'required|string|max:100',
            'recipt'=>'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'amount.max' => 'Payment amount cannot exceed the outstanding balance of Rs ' . number_format($outstanding, 2),
        ]);
        
        $receiptPath = $r->file('recipt')->store('receipts','public');
        
        return $this->payLatestWithCarry($latest, 'online', $receiptPath, (float)$data['amount']);
    }

    public function card(Request $r, $id)
    {
        $latest = ShopRental::findOrFail($id);
        
        // Verify access permissions
        $this->verifyShopAccess($latest->shopNumber);
        
        // Calculate outstanding amount for validation using unified service
        $maxPayable = $this->billingService->getMaxPayableAmount('shop', $latest->shopNumber, $latest->month);
        $alreadyPaid = (float)$latest->paidAmount;
        $outstanding = max(0, $maxPayable - $alreadyPaid);
        
        $data = $r->validate([
            'amount' => ['required','numeric','min:0.01', 'max:' . $outstanding],
        ], [
            'amount.max' => 'Payment amount cannot exceed the outstanding balance of Rs ' . number_format($outstanding, 2),
        ]);
        
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
     * Row becomes InProgress until approved.
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

        try {
            // Use unified billing service to record customer payment
            $this->billingService->recordCustomerPayment(
                'shop', 
                $latest->id, 
                $amount, 
                $method, 
                $receiptPath, 
                now()
            );

            return back()->with('success', 'Payment recorded and is in progress. Admin will approve it shortly.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Payment failed: ' . $e->getMessage()]);
        }
    }
}
