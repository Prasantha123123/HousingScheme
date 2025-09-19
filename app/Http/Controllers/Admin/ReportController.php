<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRental;
use App\Models\ShopRental;
use App\Models\InventorySale;
use App\Models\Payroll;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
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

        // ===== CASH INCOME (collections) =====
        // Houses: prefer approved_at; fallback to customer_paid_at
        $houseCollectedApproved   = (float) HouseRental::whereBetween('approved_at', [$from, $to])->sum('paidAmount');
        $houseCollectedNoApproval = (float) HouseRental::whereNull('approved_at')
            ->whereBetween('customer_paid_at', [$from, $to])
            ->sum('paidAmount');
        $houseCollected = $houseCollectedApproved + $houseCollectedNoApproval;

        // Shops: fallback to timestamp; include money-like statuses
        $shopCollected = (float) ShopRental::whereBetween('timestamp', [$from, $to])
            ->whereIn('status', ['Approved', 'PartPayment', 'ExtraPayment'])
            ->sum('paidAmount');

        // Inventory sales are already cash-dated
        $invCollected = (float) InventorySale::whereBetween('date', [
            $from->toDateString(), $to->toDateString(),
        ])->sum('total');

        $incomeTotal = $houseCollected + $shopCollected + $invCollected;

        // ===== EXPENSES (cash) =====
        $payroll      = (float) Payroll::whereBetween('timestamp', [$from, $to])->sum('wage_net');
        $otherExpense = (float) Expense::whereBetween('timestamp', [$from, $to])->sum('amount');
        $expenseTotal = $payroll + $otherExpense;

        // ===== BILLED (accrual) — rentals in period =====
        $houseBilled  = (float) HouseRental::whereBetween('month', [$fromMonth, $toMonth])->sum('billAmount');
        $shopBilled   = (float) ShopRental::whereBetween('month', [$fromMonth, $toMonth])->sum('billAmount');
        $billedTotal  = $houseBilled + $shopBilled;

        // ===== A/R Closing Balance (rentals only) =====
        // Closing A/R = outstanding amounts at the end of the period
        
        // Closing A/R: Sum of unpaid amounts from all months up to and including the period end
        $houseClosingAR = HouseRental::where('month', '<=', $toMonth)->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $shopClosingAR = ShopRental::where('month', '<=', $toMonth)->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $closingAR = (float)$houseClosingAR + (float)$shopClosingAR;

        // Only rentals reduce AR (exclude inventory)
        $collectedRentalsOnly = $houseCollected + $shopCollected;

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

            // Cash view
            'income' => [
                'house'     => $houseCollected,
                'shop'      => $shopCollected,
                'inventory' => $invCollected,
                'total'     => $incomeTotal,
            ],
            'expense' => [
                'payroll' => $payroll,
                'other'   => $otherExpense,
                'total'   => $expenseTotal,
            ],

            // Accrual info
            'billed' => [
                'house' => $houseBilled,
                'shop'  => $shopBilled,
                'total' => $billedTotal,
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
