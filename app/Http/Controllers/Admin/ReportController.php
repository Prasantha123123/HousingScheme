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
        // Normalize range (defaults to current month)
        $from = $r->filled('from')
            ? Carbon::parse($r->input('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $r->filled('to')
            ? Carbon::parse($r->input('to'))->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // INCOME: count only APPROVED money actually received
        $houseIncome = (float) HouseRental::whereBetween('timestamp', [$from, $to])
            ->where('status', 'Approved')
            ->sum('paidAmount');

        $shopIncome = (float) ShopRental::whereBetween('timestamp', [$from, $to])
            ->where('status', 'Approved')
            ->sum('paidAmount');

        // If InventorySale.date is a DATE (no time), compare as date strings
        $invIncome = (float) InventorySale::whereBetween('date', [
            $from->toDateString(),
            $to->toDateString(),
        ])->sum('total');

        // EXPENSES (adjust fields if needed)
        $payroll = (float) Payroll::whereBetween('timestamp', [$from, $to])->sum('wage_net');
        $other = (float) Expense::whereBetween('timestamp', [$from, $to])->sum('amount');

        return view('admin.reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'income' => [
                'house' => $houseIncome,
                'shop' => $shopIncome,
                'inventory' => $invIncome,
                'total' => $houseIncome + $shopIncome + $invIncome,
            ],
            'expense' => [
                'payroll' => $payroll,
                'other' => $other,
                'total' => $payroll + $other,
            ],
        ]);
    }
}
