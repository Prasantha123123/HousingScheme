<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Shop;
use App\Models\HouseRental;
use App\Models\ShopRental;
use App\Models\Payroll;
use App\Models\InventorySale;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $req)
    {
        // Month filter (YYYY-MM)
        $month = $req->get('month', now()->format('Y-m'));
        [$y, $m] = explode('-', $month);
        $from = Carbon::create($y, $m, 1)->startOfDay();
        $to   = (clone $from)->endOfMonth();

        // =========================
        // 1) BILLED (ACCRUAL) - current month rentals
        // =========================
        $houseBilled = HouseRental::where('month', $month)->sum('billAmount');
        $shopBilled  = ShopRental::where('month', $month)->sum('billAmount');
        $billedRental = (float)$houseBilled + (float)$shopBilled;

        // =========================
        // 2) COLLECTED (CASH) - this month
        //    Only count approved payments (admin-approved bills only)
        //    Houses: only approved_at entries
        //    Shops: only 'Approved' status entries
        //    Inventory sales: date column is already cash-like
        // =========================
        $houseCollected = HouseRental::whereBetween('approved_at', [$from, $to])
            ->whereNotNull('approved_at')
            ->sum('paidAmount');

        // shops: only count Approved status cash this month
        $shopCollected = ShopRental::whereBetween('approved_at', [$from, $to])
            ->where('status', 'Approved')
            ->whereNotNull('approved_at')
            ->sum('paidAmount');

        $invCollected = InventorySale::whereBetween('date', [$from, $to])->sum('total');

        $collectedCash = (float)$houseCollected + (float)$shopCollected + (float)$invCollected;

        // =========================
        // 3) A/R Opening & Closing (rentals only)
        // Opening A/R = outstanding amounts at the start of this month
        // Closing A/R = outstanding amounts at the end of this month
        // =========================
        
        // Opening A/R: Sum of unpaid amounts from all months before the selected month
        $houseOpeningAR = HouseRental::where('month', '<', $month)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $shopOpeningAR = ShopRental::where('month', '<', $month)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $openingAR = (float)$houseOpeningAR + (float)$shopOpeningAR;

        // Closing A/R: Sum of unpaid amounts from all months up to and including the selected month
        $houseClosingAR = HouseRental::where('month', '<=', $month)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $shopClosingAR = ShopRental::where('month', '<=', $month)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        $closingAR = (float)$houseClosingAR + (float)$shopClosingAR;

        // =========================
        // 4) Expenses (cash this month)
        // =========================
        $payroll  = Payroll::whereBetween('timestamp', [$from, $to])->sum('wage_net');
        $other    = Expense::whereBetween('timestamp', [$from, $to])->sum('amount');
        $expenses = (float)$payroll + (float)$other;

        // Cash Net (Collections - Expenses):
        $cashNet = $collectedCash - $expenses;

        // =========================
        // 5) Entity totals (overall) & generation counts
        // =========================
        $totalHouses = House::count();
        $totalShops  = Shop::count();

        $houseGeneratedCount = HouseRental::where('month', $month)->count();
        $shopGeneratedCount  = ShopRental::where('month', $month)->count();

        // =========================
        // 6) Pending / Completed (current month)
        //    Pending: Pending, InProgress, PartPayment
        //    Completed: Approved, ExtraPayment
        // =========================
        $pendingStatuses   = ['Pending', 'InProgress', 'PartPayment'];
        $completedStatuses = ['Approved', 'ExtraPayment'];

        // House
        $housePendingCount = HouseRental::where('month', $month)->whereIn('status', $pendingStatuses)->count();
        $houseCompletedCount = HouseRental::where('month', $month)->whereIn('status', $completedStatuses)->count();
        $housePendingTotal = HouseRental::where('month', $month)
            ->whereIn('status', $pendingStatuses)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $houseCompletedTotal = HouseRental::where('month', $month)
            ->whereIn('status', $completedStatuses)
            ->sum('paidAmount');

        // Shop
        $shopPendingCount = ShopRental::where('month', $month)->whereIn('status', $pendingStatuses)->count();
        $shopCompletedCount = ShopRental::where('month', $month)->whereIn('status', $completedStatuses)->count();
        $shopPendingTotal = ShopRental::where('month', $month)
            ->whereIn('status', $pendingStatuses)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $shopCompletedTotal = ShopRental::where('month', $month)
            ->whereIn('status', $completedStatuses)
            ->sum('paidAmount');

        $pendingCount   = $housePendingCount + $shopPendingCount;
        $completedCount = $houseCompletedCount + $shopCompletedCount;
        $pendingTotal   = $housePendingTotal + $shopPendingTotal;
        $completedTotal = $houseCompletedTotal + $shopCompletedTotal;

        // Latest pending/inprogress/partpayment
        $latestPending = collect()
            ->merge(
                HouseRental::whereIn('status', $pendingStatuses)
                    ->latest('timestamp')->take(10)->get()
                    ->map(fn($r) => [
                        'type'      => 'House',
                        'houseNo'   => $r->houseNo,
                        'month'     => $r->month,
                        'amount'    => $r->billAmount,
                        'timestamp' => $r->timestamp,
                    ])
            )
            ->merge(
                ShopRental::whereIn('status', $pendingStatuses)
                    ->latest('timestamp')->take(10)->get()
                    ->map(fn($r) => [
                        'type'        => 'Shop',
                        'shopNumber'  => $r->shopNumber,
                        'month'       => $r->month,
                        'amount'      => $r->billAmount,
                        'timestamp'   => $r->timestamp,
                    ])
            )
            ->sortByDesc('timestamp')->take(10)->values();

        return view('admin.dashboard.index', [
            'month'      => $month,

            // Accrual & AR
            'billedRental' => $billedRental,
            'houseBilled'  => $houseBilled,
            'shopBilled'   => $shopBilled,
            'openingAR'    => $openingAR,
            'closingAR'    => $closingAR,

            // Cash
            'houseCollected' => $houseCollected,
            'shopCollected'  => $shopCollected,
            'invCollected'   => $invCollected,
            'collectedCash'  => $collectedCash,
            'expenses'       => $expenses,
            'cashNet'        => $cashNet,

            // Entities / generation
            'totalHouses'    => $totalHouses,
            'totalShops'     => $totalShops,
            'houseGenerated' => $houseGeneratedCount,
            'shopGenerated'  => $shopGeneratedCount,

            // Status splits (current month)
            'pendingCount'       => $pendingCount,
            'completedCount'     => $completedCount,
            'pendingTotal'       => $pendingTotal,
            'completedTotal'     => $completedTotal,
            'housePendingCount'  => $housePendingCount,
            'houseCompletedCount'=> $houseCompletedCount,
            'housePendingTotal'  => $housePendingTotal,
            'houseCompletedTotal'=> $houseCompletedTotal,
            'shopPendingCount'   => $shopPendingCount,
            'shopCompletedCount' => $shopCompletedCount,
            'shopPendingTotal'   => $shopPendingTotal,
            'shopCompletedTotal' => $shopCompletedTotal,

            'latestPending'  => $latestPending,
        ]);
    }
}
