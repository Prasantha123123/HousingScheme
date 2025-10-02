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
use App\Services\UnifiedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function index(Request $req)
    {
        // Month filter (YYYY-MM)
        $month = $req->get('month', now()->format('Y-m'));
        
        // Get unified billing metrics
        $metrics = $this->billingService->getDashboardMetrics($month);
        
        [$y, $m] = explode('-', $month);
        $from = Carbon::create($y, $m, 1)->startOfDay();
        $to   = (clone $from)->endOfMonth();

        // =========================
        // Inventory sales (unchanged)
        // =========================
        $invCollected = InventorySale::whereBetween('date', [$from, $to])->sum('total');
        $collectedCash = $metrics['collected']['total'] + (float)$invCollected;

        // AR metrics now come from unified billing service

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

        // Status counts now come from unified billing service
        $pendingStatuses = ['Pending', 'InProgress', 'PartPayment'];

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
            'month' => $month,
            'totalHouses' => $totalHouses,
            'totalShops' => $totalShops,
            'houseGeneratedCount' => $houseGeneratedCount,
            'shopGeneratedCount' => $shopGeneratedCount,
            'payroll' => $payroll,
            'other' => $other,
            'expenses' => $expenses,
            'cashNet' => $cashNet,
            'latestPending' => $latestPending,
            'invCollected' => $invCollected,
            
            // Unified metrics
            'billedRental' => $metrics['billed']['total'],
            'collectedCash' => $collectedCash,
            'openingAR' => $metrics['receivable']['opening']['total'],
            'closingAR' => $metrics['receivable']['closing']['total'],
            
            // House specific
            'houseBilled' => $metrics['billed']['house'],
            'houseCollected' => $metrics['collected']['house'],
            'houseOpeningAR' => $metrics['receivable']['opening']['house'],
            'houseClosingAR' => $metrics['receivable']['closing']['house'],
            'housePendingCount' => $metrics['counts']['pending']['house']['count'],
            'houseCompletedCount' => $metrics['counts']['completed']['house']['count'],
            'housePendingTotal' => $metrics['counts']['pending']['house']['outstanding'],
            'houseCompletedTotal' => $metrics['counts']['completed']['house']['outstanding'],
            
            // Shop specific  
            'shopBilled' => $metrics['billed']['shop'],
            'shopCollected' => $metrics['collected']['shop'],
            'shopOpeningAR' => $metrics['receivable']['opening']['shop'],
            'shopClosingAR' => $metrics['receivable']['closing']['shop'],
            'shopPendingCount' => $metrics['counts']['pending']['shop']['count'],
            'shopCompletedCount' => $metrics['counts']['completed']['shop']['count'],
            'shopPendingTotal' => $metrics['counts']['pending']['shop']['outstanding'],
            'shopCompletedTotal' => $metrics['counts']['completed']['shop']['outstanding'],
            
            // Carry forward
            'houseCarryForward' => $metrics['carry_forward']['house'] ?? 0,
            'shopCarryForward' => $metrics['carry_forward']['shop'] ?? 0,
            'totalCarryForward' => $metrics['carry_forward']['total'] ?? 0,
            
            // Previous Month Pending
            'housePreviousMonthPending' => $metrics['previous_month_pending']['house'] ?? 0,
            'shopPreviousMonthPending' => $metrics['previous_month_pending']['shop'] ?? 0,
            'totalPreviousMonthPending' => $metrics['previous_month_pending']['total'] ?? 0,
            
            // Combined counts
            'pendingCount' => $metrics['counts']['pending']['house']['count'] + $metrics['counts']['pending']['shop']['count'],
            'completedCount' => $metrics['counts']['completed']['house']['count'] + $metrics['counts']['completed']['shop']['count'],
            'pendingTotal' => $metrics['counts']['pending']['house']['outstanding'] + $metrics['counts']['pending']['shop']['outstanding'],
            'completedTotal' => $metrics['counts']['completed']['house']['outstanding'] + $metrics['counts']['completed']['shop']['outstanding'],
            
            // Legacy compatibility
            'houseGenerated' => $houseGeneratedCount,
            'shopGenerated' => $shopGeneratedCount,
        ]);
    }
}
