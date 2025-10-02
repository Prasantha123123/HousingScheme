<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use App\Models\ShopRental;
use App\Models\InventorySale;
use App\Models\Payroll;
use App\Models\Expense;
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }
    public function index(Request $r)
    {
        // Range (defaults to current month)
        $from = $r->filled('from')
            ? Carbon::parse($r->input('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $r->filled('to')
            ? Carbon::parse($r->input('to'))->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // Month keys for rentals (YYYY-MM)
        $fromMonth = $from->format('Y-m');
        $toMonth   = $to->format('Y-m');

        // Get unified billing metrics for consistent calculations
        $metrics = $this->billingService->getDashboardMetrics($fromMonth);

        // ===== INVENTORY SALES =====
        $invCollected = (float) InventorySale::whereBetween('date', [
            $from->toDateString(), $to->toDateString(),
        ])->sum('total');

        $incomeTotal = $metrics['collected']['total'] + $invCollected;

        // ===== EXPENSES (cash) =====
        $payroll      = (float) Payroll::whereBetween('timestamp', [$from, $to])->sum('wage_net');
        $otherExpense = (float) Expense::whereBetween('timestamp', [$from, $to])->sum('amount');
        $expenseTotal = $payroll + $otherExpense;

        // ===== A/R Closing Balance (rentals only) =====
        // Closing A/R = outstanding amounts at the end of the period
        $houseClosingAR = HouseRental::where('month', '<=', $toMonth)->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $shopClosingAR = ShopRental::where('month', '<=', $toMonth)->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $closingAR = (float)$houseClosingAR + (float)$shopClosingAR;

        // Only rentals reduce AR (exclude inventory)
        $collectedRentalsOnly = $metrics['collected']['total'];

        // ===== STATUS COUNTS in the period (by billed month) =====
        $paidStatuses        = ['Approved', 'ExtraPayment'];
        $partPaymentStatuses = ['PartPayment'];
        $unpaidStatuses      = ['Pending', 'InProgress', 'Rejected'];

        // Houses
        $housePaidCount   = HouseRental::whereBetween('month', [$fromMonth, $toMonth])->whereIn('status', $paidStatuses)->count();
        $housePartCount   = HouseRental::whereBetween('month', [$fromMonth, $toMonth])->whereIn('status', $partPaymentStatuses)->count();
        $houseUnpaidCount = HouseRental::whereBetween('month', [$fromMonth, $toMonth])->whereIn('status', $unpaidStatuses)->count();

        // Shops
        $shopPaidCount   = ShopRental::whereBetween('month', [$fromMonth, $toMonth])->whereIn('status', $paidStatuses)->count();
        $shopPartCount   = ShopRental::whereBetween('month', [$fromMonth, $toMonth])->whereIn('status', $partPaymentStatuses)->count();
        $shopUnpaidCount = ShopRental::whereBetween('month', [$fromMonth, $toMonth])->whereIn('status', $unpaidStatuses)->count();

        return view('admin.reports.index', [
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),

            // Cash view (now includes carry forward correctly)
            'income' => [
                'house'     => $metrics['collected']['house'],
                'shop'      => $metrics['collected']['shop'],
                'inventory' => $invCollected,
                'total'     => $incomeTotal,
            ],
            'expense' => [
                'payroll' => $payroll,
                'other'   => $otherExpense,
                'total'   => $expenseTotal,
            ],

            // Accrual info (now includes proper carry forward)
            'billed' => [
                'house' => $metrics['billed']['house'],
                'shop'  => $metrics['billed']['shop'],
                'total' => $metrics['billed']['total'],
            ],
            'carry_forward' => [
                'house' => $metrics['carry_forward']['house'],
                'shop'  => $metrics['carry_forward']['shop'],
                'total' => $metrics['carry_forward']['total'],
            ],
            'ar' => [
                'closing'           => $closingAR,
                'collected_rentals' => $collectedRentalsOnly,
            ],

            // Counts
            'counts' => [
                'paid'   => $housePaidCount + $shopPaidCount,
                'part'   => $housePartCount + $shopPartCount,
                'unpaid' => $houseUnpaidCount + $shopUnpaidCount,
                'house'  => [
                    'paid'   => $housePaidCount,
                    'part'   => $housePartCount,
                    'unpaid' => $houseUnpaidCount,
                ],
                'shop'   => [
                    'paid'   => $shopPaidCount,
                    'part'   => $shopPartCount,
                    'unpaid' => $shopUnpaidCount,
                ],
            ],
        ]);
    }
}
