<?php

namespace App\Services\Payments;

use App\Models\HouseRental;
use App\Models\ShopRental;
use App\Models\HousePayment;
use App\Models\ShopPayment;
use App\Services\UnifiedBillingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnifiedPaymentService
{
    public function __construct(
        private UnifiedBillingService $billingService
    ) {}

    /**
     * Create a new payment and allocate to rentals
     * 
     * @param string $type 'house' or 'shop'
     * @param int $rentalId The rental ID to pay for
     * @param array $data Payment data including amount, method, receipt, isCustomerFlow
     * @return HousePayment|ShopPayment
     */
    public function make(string $type, int $rentalId, array $data): Model
    {
        $amount = (float)$data['amount'];
        $method = $data['method'] ?? 'cash';
        $receipt = $data['receipt'] ?? $data['recipt'] ?? null;
        $isCustomerFlow = $data['isCustomerFlow'] ?? false;

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than 0');
        }

        return DB::transaction(function () use ($type, $rentalId, $amount, $method, $receipt, $isCustomerFlow) {
            // Get rental and lock it
            if ($type === 'house') {
                $rental = HouseRental::lockForUpdate()->findOrFail($rentalId);
                $entityField = 'houseNo';
                $entityValue = $rental->houseNo;
            } else {
                $rental = ShopRental::lockForUpdate()->findOrFail($rentalId);
                $entityField = 'shopNumber';
                $entityValue = $rental->shopNumber;
            }

            // Get all unpaid rentals up to current month
            $unpaidRentals = $this->getUnpaidRentals($type, $entityValue, $rental->month);

            // Calculate total outstanding
            $totalOutstanding = $unpaidRentals->sum(function ($r) {
                return bcadd('0', bcsub((string)$r->billAmount, (string)$r->paidAmount, 2), 2);
            });

            if (bccomp((string)$amount, $totalOutstanding, 2) > 0) {
                throw new \InvalidArgumentException("Payment amount (LKR {$amount}) exceeds outstanding balance (LKR {$totalOutstanding})");
            }

            // Determine payment type
            $paymentType = bccomp((string)$amount, $totalOutstanding, 2) === 0 ? 'fullpayment' : 'partpayment';

            // Create payment record
            $paymentData = [
                $type === 'house' ? 'houseId' : 'shopId' => $rentalId,
                'paymentmake' => $amount,
                'method' => $method,
                'recipt' => $receipt,
                'paymenttype' => $paymentType,
                'status' => 'pending',
                'customerPaidAt' => now(),
                'approvedAt' => null,
            ];

            $payment = $type === 'house' 
                ? HousePayment::create($paymentData)
                : ShopPayment::create($paymentData);

            // Allocate payment across unpaid rentals (oldest first)
            $this->allocatePaymentToRentals($unpaidRentals, $amount, $method, $receipt, $isCustomerFlow);

            return $payment;
        });
    }

    /**
     * Approve a payment
     * 
     * @param string $type 'house' or 'shop'
     * @param int $paymentId
     * @return HousePayment|ShopPayment
     */
    public function approve(string $type, int $paymentId): Model
    {
        return DB::transaction(function () use ($type, $paymentId) {
            // Get and lock payment
            $payment = $type === 'house' 
                ? HousePayment::lockForUpdate()->findOrFail($paymentId)
                : ShopPayment::lockForUpdate()->findOrFail($paymentId);

            // If already approved, return (idempotent)
            if ($payment->status === 'approval') {
                return $payment;
            }

            // Update payment status
            $payment->update([
                'status' => 'approval',
                'approvedAt' => now()
            ]);

            // Get the rental to find entity identifier
            $rental = $type === 'house' ? $payment->houseRental : $payment->shopRental;
            $entityValue = $type === 'house' ? $rental->houseNo : $rental->shopNumber;

            // Re-scan affected rentals and update their statuses
            $affectedRentals = $this->getUnpaidRentals($type, $entityValue, $rental->month);
            
            foreach ($affectedRentals as $rentalRecord) {
                $this->updateRentalStatus($rentalRecord);
            }

            return $payment->fresh();
        });
    }

    /**
     * Find latest pending payment for a rental
     */
    public function latestPendingPaymentForRental(string $type, int $rentalId): ?Model
    {
        if ($type === 'house') {
            return HousePayment::where('houseId', $rentalId)
                ->whereIn('status', ['pending', 'inprogress'])
                ->latest('customerPaidAt')
                ->first();
        } else {
            return ShopPayment::where('shopId', $rentalId)
                ->whereIn('status', ['pending', 'inprogress'])
                ->latest('customerPaidAt')
                ->first();
        }
    }

    /**
     * Get unpaid rentals up to specified month
     */
    private function getUnpaidRentals(string $type, string $entityValue, string $upToMonth): \Illuminate\Database\Eloquent\Collection
    {
        if ($type === 'house') {
            return HouseRental::where('houseNo', $entityValue)
                ->where('month', '<=', $upToMonth)
                ->orderBy('month')
                ->lockForUpdate()
                ->get();
        } else {
            return ShopRental::where('shopNumber', $entityValue)
                ->where('month', '<=', $upToMonth)
                ->orderBy('month')
                ->lockForUpdate()
                ->get();
        }
    }

    /**
     * Allocate payment across rentals (oldest first)
     */
    private function allocatePaymentToRentals($rentals, float $amount, string $method, ?string $receipt, bool $isCustomerFlow): void
    {
        $remainingAmount = $amount;
        $currentMonth = now()->format('Y-m');
        $latestRentalWithAllocation = null;

        foreach ($rentals as $rental) {
            if (bccomp((string)$remainingAmount, '0', 2) <= 0) {
                break;
            }

            $outstanding = bcsub((string)$rental->billAmount, (string)$rental->paidAmount, 2);
            if (bccomp($outstanding, '0', 2) <= 0) {
                continue;
            }

            $allocation = bccomp((string)$remainingAmount, $outstanding, 2) >= 0 
                ? $outstanding 
                : (string)$remainingAmount;

            // Update rental
            $rental->paidAmount = bcadd((string)$rental->paidAmount, $allocation, 2);
            $rental->paymentMethod = $method;
            $rental->customer_paid_at = now();

            if ($receipt) {
                $rental->recipt = $receipt;
            }

            // Update status based on payment completion and flow type
            $isFullyPaid = bccomp((string)$rental->paidAmount, (string)$rental->billAmount, 2) >= 0;
            
            if ($isFullyPaid) {
                if ($isCustomerFlow) {
                    $rental->status = 'InProgress'; // Customer paid, awaiting admin approval
                } else {
                    $rental->status = 'Approved'; // Admin payment, auto-approve
                    $rental->approved_at = $rental->approved_at ?? now();
                }
            } else {
                $rental->status = $isCustomerFlow ? 'InProgress' : 'PartPayment';
            }

            $rental->save();

            // Track for monthly collection reporting
            $latestRentalWithAllocation = $rental;
            $remainingAmount = bcsub((string)$remainingAmount, $allocation, 2);
        }

        // Update monthly collection amount on the latest rental that received allocation
        if ($latestRentalWithAllocation) {
            $latestRentalWithAllocation->monthly_collection_amount = bcadd(
                (string)($latestRentalWithAllocation->monthly_collection_amount ?? '0'),
                (string)($amount - $remainingAmount),
                2
            );
            $latestRentalWithAllocation->collection_month = $currentMonth;
            $latestRentalWithAllocation->save();
        }
    }

    /**
     * Update rental status based on payment amounts
     */
    private function updateRentalStatus($rental): void
    {
        $paidAmount = (string)$rental->paidAmount;
        $billAmount = (string)$rental->billAmount;

        if (bccomp($paidAmount, $billAmount, 2) >= 0) {
            // Fully paid
            $rental->paidAmount = $billAmount; // Exact amount
            $rental->status = 'Approved';
            $rental->approved_at = $rental->approved_at ?? now();
        } elseif (bccomp($paidAmount, '0', 2) > 0) {
            // Partially paid
            $rental->status = 'PartPayment';
            $rental->approved_at = null;
        } else {
            // No payment
            $rental->status = 'Pending';
            $rental->approved_at = null;
        }

        $rental->save();
    }

    /**
     * Get maximum payable amount for validation
     */
    public function getMaxPayableAmount(string $type, string $entityValue, string $month): string
    {
        return $this->billingService->getMaxPayableAmount(
            $type === 'house' ? 'house' : 'shop',
            $entityValue,
            $month
        );
    }
}