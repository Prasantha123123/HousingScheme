<?php

namespace App\Services;

use App\Models\HouseRental;
use App\Models\ShopRental;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Unified Billing Service for Houses and Shop Rentals
 *
 * Minimal fix: carry-forward totals now detect allocations posted this month
 * even when only approved_at/upda    private function getHouseCollectedInMonth(Carbon $from, Carbon $to): float
    {
        // Get unique payments made in this month - sum by customer payment events, not by bill records
        // This prevents double counting when payments are allocated across multiple bills
        $totalCollected = HouseRental::where(function($query) use ($from, $to) {
                $query->whereBetween('customer_paid_at', [$from, $to])
                      ->orWhereBetween('approved_at', [$from, $to]);
            })
            ->whereNotNull('original_payment_amount')
            ->where('original_payment_amount', '>', 0)
            // Group by house and month to get unique payment transactions
            ->selectRaw('houseNo, MAX(original_payment_amount) as payment_amount')
            ->groupBy('houseNo', 'month')
            ->get()
            ->sum('payment_amount');

        return (float)$totalCollected;
    }ustomer_paid_at missing).
 */
class UnifiedBillingService
{
    /**
     * Process payment for house rental with unified allocation logic
     */
    public function processHousePayment(HouseRental $currentBill, float $paymentAmount, string $paymentMethod, ?string $receiptPath = null, ?Carbon $paymentDate = null): void
    {
        $paymentDate = $paymentDate ?? now();
        $collectionMonth = $paymentDate->format('Y-m');

        DB::transaction(function () use ($currentBill, $paymentAmount, $paymentMethod, $receiptPath, $paymentDate, $collectionMonth) {
            // Get all unpaid bills up to and including current month for this house
            $unpaidBills = HouseRental::where('houseNo', $currentBill->houseNo)
                ->where('month', '<=', $currentBill->month)
                ->orderBy('month') // oldest first
                ->lockForUpdate()
                ->get();

            // Calculate total amount due up to current month
            $totalDue = $unpaidBills->sum(function ($bill) {
                return max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
            });

            // Cap payment to total due (prevent overpayment)
            $effectivePayment = min($paymentAmount, $totalDue);

            if ($effectivePayment <= 0) {
                throw new \Exception('No outstanding amount to pay.');
            }

            // Allocate payment oldest-first
            $remainingPayment = $effectivePayment;
            $paymentDistribution = []; // Track where customer payments actually go for display

            foreach ($unpaidBills as $bill) {
                if ($remainingPayment <= 0) break;

                $billOutstanding = max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
                if ($billOutstanding <= 0) continue;

                $allocation = min($remainingPayment, $billOutstanding);

                // Track payment distribution for display purposes
                $paymentDistribution[$bill->month] = ($paymentDistribution[$bill->month] ?? 0) + $allocation;

                // Update bill with allocated amount
                $bill->paidAmount = round((float)$bill->paidAmount + $allocation, 2);

                // Update status based on bill completion
                if ($bill->paidAmount >= (float)$bill->billAmount - 0.01) {
                    $bill->paidAmount = (float)$bill->billAmount; // Exact amount
                    $bill->status = 'Approved';
                    $bill->approved_at = $bill->approved_at ?? $paymentDate;
                } else {
                    $bill->status = 'PartPayment';
                }

                // Set payment method if not already set
                if (!$bill->paymentMethod) {
                    $bill->paymentMethod = $paymentMethod;
                }

                $bill->save();
                $remainingPayment -= $allocation;
            }

            // Update original_payment_amount to show customer payment amounts per bill
            // This is for display purposes - showing what customer paid for each month
            if (isset($paymentDistribution[$collectionMonth])) {
                // Payment was made in a month that has a bill
                $targetBill = $unpaidBills->where('month', $collectionMonth)->first();
                if ($targetBill) {
                    $targetBill->original_payment_amount = ($targetBill->original_payment_amount ?? 0) + $paymentAmount;
                    $targetBill->save();
                }
            } else {
                // Distribute the payment amount proportionally for display
                $totalAllocated = array_sum($paymentDistribution);
                if ($totalAllocated > 0) {
                    foreach ($paymentDistribution as $month => $allocation) {
                        $billToUpdate = $unpaidBills->where('month', $month)->first();
                        if ($billToUpdate) {
                            // Show proportional amount of customer payment for this bill
                            $proportionalPayment = ($allocation / $totalAllocated) * $paymentAmount;
                            $billToUpdate->original_payment_amount = ($billToUpdate->original_payment_amount ?? 0) + $proportionalPayment;
                            $billToUpdate->save();
                        }
                    }
                }
            }

            // Update the current bill with collection tracking
            $currentBill->customer_paid_at = $paymentDate;

            if ($receiptPath) {
                $currentBill->recipt = $receiptPath;
            }

            $currentBill->save();

            // Store any overpayment as credit (if needed in future)
            if ($paymentAmount > $effectivePayment) {
                $credit = $paymentAmount - $effectivePayment;
                // TODO: Store in customer credits table for future use
                // $this->storeCreditForCustomer($currentBill->houseNo, $credit, $paymentDate);
            }
        });
    }

    /**
     * Process payment for shop rental with unified allocation logic
     */
    public function processShopPayment(ShopRental $currentBill, float $paymentAmount, string $paymentMethod, ?string $receiptPath = null, ?Carbon $paymentDate = null): void
    {
        $paymentDate = $paymentDate ?? now();
        $collectionMonth = $paymentDate->format('Y-m');

        DB::transaction(function () use ($currentBill, $paymentAmount, $paymentMethod, $receiptPath, $paymentDate, $collectionMonth) {
            // Get all unpaid bills up to and including current month for this shop
            $unpaidBills = ShopRental::where('shopNumber', $currentBill->shopNumber)
                ->where('month', '<=', $currentBill->month)
                ->orderBy('month') // oldest first
                ->lockForUpdate()
                ->get();

            // Calculate total amount due up to current month
            $totalDue = $unpaidBills->sum(function ($bill) {
                return max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
            });

            // Cap payment to total due (prevent overpayment)
            $effectivePayment = min($paymentAmount, $totalDue);

            if ($effectivePayment <= 0) {
                throw new \Exception('No outstanding amount to pay.');
            }

            // Allocate payment oldest-first
            $remainingPayment = $effectivePayment;
            $paymentDistribution = []; // Track where customer payments actually go for display

            foreach ($unpaidBills as $bill) {
                if ($remainingPayment <= 0) break;

                $billOutstanding = max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
                if ($billOutstanding <= 0) continue;

                $allocation = min($remainingPayment, $billOutstanding);

                // Track payment distribution for display purposes
                $paymentDistribution[$bill->month] = ($paymentDistribution[$bill->month] ?? 0) + $allocation;

                // Update bill with allocated amount
                $bill->paidAmount = round((float)$bill->paidAmount + $allocation, 2);

                // Update status based on bill completion
                if ($bill->paidAmount >= (float)$bill->billAmount - 0.01) {
                    $bill->paidAmount = (float)$bill->billAmount; // Exact amount
                    $bill->status = 'Approved';
                    $bill->approved_at = $bill->approved_at ?? $paymentDate;
                } else {
                    $bill->status = 'PartPayment';
                }

                // Set payment method if not already set
                if (!$bill->paymentMethod) {
                    $bill->paymentMethod = $paymentMethod;
                }

                $bill->save();
                $remainingPayment -= $allocation;
            }

            // Update original_payment_amount to show customer payment amounts per bill
            if (isset($paymentDistribution[$collectionMonth])) {
                $targetBill = $unpaidBills->where('month', $collectionMonth)->first();
                if ($targetBill) {
                    $targetBill->original_payment_amount = ($targetBill->original_payment_amount ?? 0) + $paymentAmount;
                    $targetBill->save();
                }
            } else {
                $totalAllocated = array_sum($paymentDistribution);
                if ($totalAllocated > 0) {
                    foreach ($paymentDistribution as $month => $allocation) {
                        $billToUpdate = $unpaidBills->where('month', $month)->first();
                        if ($billToUpdate) {
                            $proportionalPayment = ($allocation / $totalAllocated) * $paymentAmount;
                            $billToUpdate->original_payment_amount = ($billToUpdate->original_payment_amount ?? 0) + $proportionalPayment;
                            $billToUpdate->save();
                        }
                    }
                }
            }

            // Update the current bill with collection tracking
            $currentBill->customer_paid_at = $paymentDate;

            if ($receiptPath) {
                $currentBill->recipt = $receiptPath;
            }

            $currentBill->save();

            // Store any overpayment as credit (if needed in future)
            if ($paymentAmount > $effectivePayment) {
                $credit = $paymentAmount - $effectivePayment;
                // TODO: Store in customer credits table for future use
                // $this->storeCreditForCustomer($currentBill->shopNumber, $credit, $paymentDate);
            }
        });
    }

    /**
     * Calculate monthly collection amount for dashboard reporting
     * This tracks what was actually collected in a given calendar month
     */
    private function getMonthlyCollectionAmount($bill, float $effectivePayment, Carbon $paymentDate): float
    {
        // For now, return the effective payment amount
        // This could be enhanced to track multiple payments per month
        return $effectivePayment;
    }

    /**
     * Get dashboard metrics for a given month
     */
    public function getDashboardMetrics(string $month): array
    {
        [$year, $monthNum] = explode('-', $month);
        $from = Carbon::create($year, $monthNum, 1)->startOfDay();
        $to = (clone $from)->endOfMonth();

        // Billed amounts (bills created in this month)
        $houseBilled = HouseRental::where('month', $month)->sum('billAmount');
        $shopBilled = ShopRental::where('month', $month)->sum('billAmount');

        // Collected amounts (payments received in this month by timestamp)
        $houseCollected = $this->getHouseCollectedInMonth($from, $to);
        $shopCollected = $this->getShopCollectedInMonth($from, $to);

        // Carry forward amounts (payments made in this month for previous month bills)
        $houseCarryForward = $this->getHouseCarryForwardInMonth($from, $to, $month);
        $shopCarryForward = $this->getShopCarryForwardInMonth($from, $to, $month);

        // Previous Month Pending amounts (partial payments from previous months still pending)
        $housePreviousMonthPending = $this->getHousePreviousMonthPending($month);
        $shopPreviousMonthPending = $this->getShopPreviousMonthPending($month);

        // Opening receivable (unpaid amounts at start of month)
        $houseOpeningAR = $this->getReceivableAtDate($from->copy()->subDay(), 'house');
        $shopOpeningAR = $this->getReceivableAtDate($from->copy()->subDay(), 'shop');

        // Closing receivable (unpaid amounts at end of month)
        $houseClosingAR = $this->getReceivableAtDate($to, 'house');
        $shopClosingAR = $this->getReceivableAtDate($to, 'shop');

        // Pending/Completed counts for bills created this month
        $housePending = $this->getStatusCounts($month, 'house', ['Pending', 'InProgress', 'PartPayment']);
        $houseCompleted = $this->getStatusCounts($month, 'house', ['Approved']);

        $shopPending = $this->getStatusCounts($month, 'shop', ['Pending', 'InProgress', 'PartPayment']);
        $shopCompleted = $this->getStatusCounts($month, 'shop', ['Approved']);

        return [
            'billed' => [
                'house' => (float)$houseBilled,
                'shop' => (float)$shopBilled,
                'total' => (float)$houseBilled + (float)$shopBilled,
            ],
            'collected' => [
                'house' => $houseCollected,
                'shop' => $shopCollected,
                'total' => $houseCollected + $shopCollected,
            ],
            'carry_forward' => [
                'house' => $houseCarryForward,
                'shop' => $shopCarryForward,
                'total' => $houseCarryForward + $shopCarryForward,
            ],
            'previous_month_pending' => [
                'house' => $housePreviousMonthPending,
                'shop' => $shopPreviousMonthPending,
                'total' => $housePreviousMonthPending + $shopPreviousMonthPending,
            ],
            'receivable' => [
                'opening' => [
                    'house' => $houseOpeningAR,
                    'shop' => $shopOpeningAR,
                    'total' => $houseOpeningAR + $shopOpeningAR,
                ],
                'closing' => [
                    'house' => $houseClosingAR,
                    'shop' => $shopClosingAR,
                    'total' => $houseClosingAR + $shopClosingAR,
                ],
            ],
            'counts' => [
                'pending' => [
                    'house' => $housePending,
                    'shop' => $shopPending,
                    'total' => $housePending['count'] + $shopPending['count'],
                ],
                'completed' => [
                    'house' => $houseCompleted,
                    'shop' => $shopCompleted,
                    'total' => $houseCompleted['count'] + $shopCompleted['count'],
                ],
            ],
        ];
    }

    /**
     * Get house collections for a specific month by payment timestamps
     */
    private function getHouseCollectedInMonth(Carbon $from, Carbon $to): float
    {
        // Get unique payments made in this month - avoid double counting when payments span multiple bills
        // Sum the actual payment amounts rather than allocated amounts
        $totalCollected = HouseRental::where(function($query) use ($from, $to) {
                $query->whereBetween('customer_paid_at', [$from, $to])
                      ->orWhereBetween('approved_at', [$from, $to]);
            })
            ->whereNotNull('original_payment_amount')
            ->where('original_payment_amount', '>', 0)
            // Take only the largest payment per house to avoid double counting
            ->selectRaw('houseNo, MAX(original_payment_amount) as max_payment')
            ->groupBy('houseNo')
            ->get()
            ->sum('max_payment');

        return (float)$totalCollected;
    }

    /**
     * Get Previous Month Pending amount for houses in the given month
     */
    public function getHousePreviousMonthPending(string $month): float
    {
        [$year, $monthNum] = explode('-', $month);
        $currentDate = Carbon::create($year, $monthNum, 1);
        $previousMonth = $currentDate->copy()->subMonth()->format('Y-m');

        $pendingAmount = HouseRental::where('month', '<', $month)
            ->whereIn('status', ['PartPayment', 'Pending', 'InProgress'])
            ->get()
            ->sum(function ($bill) {
                $pending = (float)$bill->billAmount - (float)$bill->paidAmount;
                return max(0, $pending);
            });

        return (float)$pendingAmount;
    }

    /**
     * Get Previous Month Pending amount for shops in the given month
     */
    public function getShopPreviousMonthPending(string $month): float
    {
        [$year, $monthNum] = explode('-', $month);
        $currentDate = Carbon::create($year, $monthNum, 1);
        $previousMonth = $currentDate->copy()->subMonth()->format('Y-m');

        $pendingAmount = ShopRental::where('month', '<', $month)
            ->whereIn('status', ['PartPayment', 'Pending', 'InProgress'])
            ->get()
            ->sum(function ($bill) {
                $pending = (float)$bill->billAmount - (float)$bill->paidAmount;
                return max(0, $pending);
            });

        return (float)$pendingAmount;
    }

    /**
     * Get shop collections for a specific month by payment timestamps
     */
    private function getShopCollectedInMonth(Carbon $from, Carbon $to): float
    {
        // Get unique payments made in this month - avoid double counting when payments span multiple bills
        $totalCollected = ShopRental::where(function($query) use ($from, $to) {
                $query->whereBetween('customer_paid_at', [$from, $to])
                      ->orWhereBetween('approved_at', [$from, $to]);
            })
            ->whereNotNull('original_payment_amount')
            ->where('original_payment_amount', '>', 0)
            // Take only the largest payment per shop to avoid double counting
            ->selectRaw('shopNumber, MAX(original_payment_amount) as max_payment')
            ->groupBy('shopNumber')
            ->get()
            ->sum('max_payment');

        return (float)$totalCollected;
    }

    /**
     * Get house carry-forward payments for a specific month
     * Shows pending collected amounts (payments received but not fully processed)
     */
    private function getHouseCarryForwardInMonth(Carbon $from, Carbon $to, string $currentMonth): float
    {
        // Track only the cross-month allocation amount (original_payment_amount - billAmount)
        $crossMonthPayments = HouseRental::where('month', $currentMonth)
            ->whereNotNull('original_payment_amount')
            ->whereRaw('original_payment_amount > billAmount')
            ->get()
            ->sum(function($bill) {
                // The difference shows how much went to previous months
                return (float)$bill->original_payment_amount - (float)$bill->billAmount;
            });
        
        return (float)$crossMonthPayments;
    }

    /**
     * Get shop carry-forward payments for a specific month
     * Shows pending collected amounts (payments received but not fully processed)
     */
    private function getShopCarryForwardInMonth(Carbon $from, Carbon $to, string $currentMonth): float
    {
        // Track only the cross-month allocation amount (original_payment_amount - billAmount)
        $crossMonthPayments = ShopRental::where('month', $currentMonth)
            ->whereNotNull('original_payment_amount')
            ->whereRaw('original_payment_amount > billAmount')
            ->get()
            ->sum(function($bill) {
                // The difference shows how much went to previous months
                return (float)$bill->original_payment_amount - (float)$bill->billAmount;
            });
        
        return (float)$crossMonthPayments;
    }

    /**
     * Get total receivable (unpaid amounts) at a specific date
     */
    private function getReceivableAtDate(Carbon $date, string $type): float
    {
        if ($type === 'house') {
            return (float)HouseRental::where('month', '<=', $date->format('Y-m'))
                ->get()
                ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        } else {
            return (float)ShopRental::where('month', '<=', $date->format('Y-m'))
                ->get()
                ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        }
    }

    /**
     * Get status counts for bills created in a specific month
     */
    private function getStatusCounts(string $month, string $type, array $statuses): array
    {
        if ($type === 'house') {
            $bills = HouseRental::where('month', $month)->whereIn('status', $statuses)->get();
        } else {
            $bills = ShopRental::where('month', $month)->whereIn('status', $statuses)->get();
        }

        $count = $bills->count();
        $outstanding = $bills->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        return [
            'count' => $count,
            'outstanding' => (float)$outstanding,
        ];
    }

    /**
     * Calculate carry forward and total amounts for bills display
     */
    public function calculateBillAmounts(string $entityType, string $entityId): array
    {
        $bills = $entityType === 'house'
            ? HouseRental::where('houseNo', $entityId)->orderBy('month')->get()
            : ShopRental::where('shopNumber', $entityId)->orderBy('month')->get();

        $calc = [];
        $runningOutstanding = 0;

        foreach ($bills as $bill) {
            $billAmount = (float)$bill->billAmount;
            $paidAmount = (float)$bill->paidAmount;

            $carry = $runningOutstanding;
            $current = $billAmount;
            $total = $carry + $current;

            $calc[$bill->id] = [
                'carry' => $carry,
                'current' => $current,
                'total' => $total,
                'paid' => $paidAmount,
                'balance' => max(0, $total - $paidAmount),
            ];

            // Update running outstanding for next iteration
            $runningOutstanding = max(0, $total - $paidAmount);
        }

        return $calc;
    }

    /**
     * Record customer payment without allocation (for InProgress status)
     */
    public function recordCustomerPayment(string $type, int $id, float $paymentAmount, string $paymentMethod, ?string $receiptPath = null, ?Carbon $paymentDate = null): void
    {
        $paymentDate = $paymentDate ?? now();

        DB::transaction(function () use ($type, $id, $paymentAmount, $paymentMethod, $receiptPath, $paymentDate) {
            if ($type === 'house') {
                $bill = HouseRental::lockForUpdate()->findOrFail($id);

                // Add to existing payment amount
                $bill->paidAmount = (float)$bill->paidAmount + $paymentAmount;
                $bill->paymentMethod = $paymentMethod;
                $bill->status = 'InProgress';
                $bill->customer_paid_at = $paymentDate;

                if ($receiptPath) {
                    $bill->recipt = $receiptPath;
                }

                $bill->save();

            } elseif ($type === 'shop') {
                $bill = ShopRental::lockForUpdate()->findOrFail($id);

                // Add to existing payment amount
                $bill->paidAmount = (float)$bill->paidAmount + $paymentAmount;
                $bill->paymentMethod = $paymentMethod;
                $bill->status = 'InProgress';
                $bill->customer_paid_at = $paymentDate;

                if ($receiptPath) {
                    $bill->recipt = $receiptPath;
                }

                $bill->save();
            }
        });
    }

    /**
     * Get maximum payable amount for a bill (prevents overpayment)
     */
    public function getMaxPayableAmount(string $entityType, string $entityId, string $month): float
    {
        if ($entityType === 'house') {
            $bills = HouseRental::where('houseNo', $entityId)
                ->where('month', '<=', $month)
                ->orderBy('month')
                ->get();
        } else {
            $bills = ShopRental::where('shopNumber', $entityId)
                ->where('month', '<=', $month)
                ->orderBy('month')
                ->get();
        }

        return $bills->sum(fn($bill) => max(0, (float)$bill->billAmount - (float)$bill->paidAmount));
    }
}
