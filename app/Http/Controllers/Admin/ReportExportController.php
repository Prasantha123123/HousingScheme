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

class ReportExportController extends Controller
{
    private UnifiedBillingService $billingService;

    public function __construct(UnifiedBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function pdf(Request $r)
    {
        $from = Carbon::parse($r->get('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($r->get('to', now()->endOfMonth()))->endOfDay();

        // Month keys for rentals (YYYY-MM)
        $fromMonth = $from->format('Y-m');
        $toMonth   = $to->format('Y-m');

        // Get unified billing metrics for consistent calculations
        $metrics = $this->billingService->getDashboardMetrics($fromMonth);

        // ===== INVENTORY SALES =====
        $invCollected = (float) InventorySale::whereBetween('date', [
            $from->toDateString(), $to->toDateString(),
        ])->sum('total');

        // ===== EXPENSES (cash) =====
        $exp_payroll  = (float) Payroll::whereBetween('timestamp', [$from, $to])->sum('wage_net');
        $exp_other    = (float) Expense::whereBetween('timestamp', [$from, $to])->sum('amount');

        // ===== PENDING AMOUNTS (Outstanding) =====
        $housePending = HouseRental::where('month', '<=', $toMonth)->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));
        $shopPending = ShopRental::where('month', '<=', $toMonth)->get()
            ->sum(fn($r) => max(0, (float)$r->billAmount - (float)$r->paidAmount));

        // ===== TOTAL AMOUNTS (All time up to period) =====
        $houseTotalBilled = (float) HouseRental::where('month', '<=', $toMonth)->sum('billAmount');
        $houseTotalReceived = (float) HouseRental::where('month', '<=', $toMonth)->sum('paidAmount');
        $shopTotalBilled = (float) ShopRental::where('month', '<=', $toMonth)->sum('billAmount');
        $shopTotalReceived = (float) ShopRental::where('month', '<=', $toMonth)->sum('paidAmount');

        $data = [
            'from' => $from,
            'to' => $to,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
            
            // Cash collections in period (now accurate with carry forward)
            'income_house' => $metrics['collected']['house'],
            'income_shop' => $metrics['collected']['shop'],
            'income_inv' => $invCollected,
            'total_income' => $metrics['collected']['total'] + $invCollected,
            
            // Expenses
            'exp_payroll' => $exp_payroll,
            'exp_other' => $exp_other,
            'total_expenses' => $exp_payroll + $exp_other,
            
            // Net calculation
            'net' => ($metrics['collected']['total'] + $invCollected) - ($exp_payroll + $exp_other),
            
            // Billed amounts in period
            'house_billed' => $metrics['billed']['house'],
            'shop_billed' => $metrics['billed']['shop'],
            'total_billed' => $metrics['billed']['total'],
            
            // Carry forward amounts (NEW)
            'house_carry_forward' => $metrics['carry_forward']['house'],
            'shop_carry_forward' => $metrics['carry_forward']['shop'],
            'total_carry_forward' => $metrics['carry_forward']['total'],
            
            // Pending amounts (outstanding)
            'house_pending' => $housePending,
            'shop_pending' => $shopPending,
            'total_pending' => $housePending + $shopPending,
            
            // Total amounts (all time up to period)
            'house_total_billed' => $houseTotalBilled,
            'house_total_received' => $houseTotalReceived,
            'shop_total_billed' => $shopTotalBilled,
            'shop_total_received' => $shopTotalReceived,
            'grand_total_billed' => $houseTotalBilled + $shopTotalBilled,
            'grand_total_received' => $houseTotalReceived + $shopTotalReceived,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'financial-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}
