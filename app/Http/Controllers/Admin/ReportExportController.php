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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(Request $r)
    {
        $from = Carbon::parse($r->get('from', now()->startOfMonth()));
        $to   = Carbon::parse($r->get('to', now()->endOfMonth()));

        $income_house = HouseRental::whereBetween('timestamp', [$from,$to])->sum('paidAmount');
        $income_shop  = ShopRental::whereBetween('timestamp', [$from,$to])->sum('paidAmount');
        $income_inv   = InventorySale::whereBetween('date', [$from,$to])->sum('total');
        $exp_payroll  = Payroll::whereBetween('timestamp', [$from,$to])->sum('wage_net');
        $exp_other    = Expense::whereBetween('timestamp', [$from,$to])->sum('amount');

        $rows = [
            ['Type','Category','Amount'],
            ['Income','House Charges',$income_house],
            ['Income','Shop Rentals',$income_shop],
            ['Income','Inventory Sales',$income_inv],
            ['Expense','Payroll',$exp_payroll],
            ['Expense','Other',$exp_other],
            ['Total','Income',$income_house+$income_shop+$income_inv],
            ['Total','Expenses',$exp_payroll+$exp_other],
            ['Total','Net',($income_house+$income_shop+$income_inv)-($exp_payroll+$exp_other)],
        ];

        return new StreamedResponse(function() use ($rows) {
            $out = fopen('php://output','w');
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=report.csv',
        ]);
    }

    // PDF: requires barryvdh/laravel-dompdf or similar. Placeholder:
    public function pdf(Request $r)
    {
        return redirect()->route('admin.reports.index')->with('error','PDF export requires a PDF package (e.g., barryvdh/laravel-dompdf).');
    }
}
