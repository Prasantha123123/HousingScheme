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

        // ===== Existing income/expense =====
        $houseIncome = HouseRental::whereBetween('timestamp', [$from, $to])->sum('paidAmount');
        $shopIncome  = ShopRental::whereBetween('timestamp', [$from, $to])->sum('paidAmount');
        $invIncome   = InventorySale::whereBetween('date', [$from, $to])->sum('total');
        $income = $houseIncome + $shopIncome + $invIncome;

        $payroll  = Payroll::whereBetween('timestamp', [$from, $to])->sum('wage_net');
        $other    = Expense::whereBetween('timestamp', [$from, $to])->sum('amount');
        $expenses = $payroll + $other;

        // ===== New: entity totals (overall) =====
        $totalHouses = House::count();
        $totalShops  = Shop::count();

        // ===== New: current-month generated rows =====
        $houseGeneratedCount = HouseRental::where('month', $month)->count();
        $shopGeneratedCount  = ShopRental::where('month', $month)->count();

        // ===== New: current-month pending/completed counts + totals =====
        // Treat "Pending" and "InProgress" as pending; "Approved" as completed.
        $pendingStatuses = ['Pending', 'InProgress'];

        // House
        $housePendingCount = HouseRental::where('month', $month)->whereIn('status', $pendingStatuses)->count();
        $houseCompletedCount = HouseRental::where('month', $month)->where('status', 'Approved')->count();
        $housePendingTotal = HouseRental::where('month', $month)
            ->whereIn('status', $pendingStatuses)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $houseCompletedTotal = HouseRental::where('month', $month)
            ->where('status', 'Approved')
            ->sum('paidAmount');

        // Shop
        $shopPendingCount = ShopRental::where('month', $month)->whereIn('status', $pendingStatuses)->count();
        $shopCompletedCount = ShopRental::where('month', $month)->where('status', 'Approved')->count();
        $shopPendingTotal = ShopRental::where('month', $month)
            ->whereIn('status', $pendingStatuses)
            ->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $shopCompletedTotal = ShopRental::where('month', $month)
            ->where('status', 'Approved')
            ->sum('paidAmount');

        $pendingCount  = $housePendingCount + $shopPendingCount;
        $completedCount = $houseCompletedCount + $shopCompletedCount;
        $pendingTotal  = $housePendingTotal + $shopPendingTotal;
        $completedTotal = $houseCompletedTotal + $shopCompletedTotal;

        // ===== Latest 10 (Pending/InProgress) - keep your list, include InProgress =====
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
            'month'            => $month,
            'income'           => $income,
            'expenses'         => $expenses,

            'totalHouses'      => $totalHouses,
            'totalShops'       => $totalShops,

            'houseGenerated'   => $houseGeneratedCount,
            'shopGenerated'    => $shopGeneratedCount,

            'pendingCount'     => $pendingCount,
            'completedCount'   => $completedCount,
            'pendingTotal'     => $pendingTotal,
            'completedTotal'   => $completedTotal,

            'housePendingCount'     => $housePendingCount,
            'houseCompletedCount'   => $houseCompletedCount,
            'housePendingTotal'     => $housePendingTotal,
            'houseCompletedTotal'   => $houseCompletedTotal,

            'shopPendingCount'      => $shopPendingCount,
            'shopCompletedCount'    => $shopCompletedCount,
            'shopPendingTotal'      => $shopPendingTotal,
            'shopCompletedTotal'    => $shopCompletedTotal,

            'latestPending'    => $latestPending,
        ]);
    }
}
