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
        $from = Carbon::parse($r->get('from', now()->startOfMonth()));
        $to   = Carbon::parse($r->get('to', now()->endOfMonth()));

        $houseIncome = HouseRental::whereBetween('timestamp', [$from,$to])->sum('paidAmount');
        $shopIncome  = ShopRental::whereBetween('timestamp', [$from,$to])->sum('paidAmount');
        $invIncome   = InventorySale::whereBetween('date', [$from,$to])->sum('total');

        $payroll = Payroll::whereBetween('timestamp', [$from,$to])->sum('wage_net');
        $other   = Expense::whereBetween('timestamp', [$from,$to])->sum('amount');

        return view('admin.reports.index', [
            'from'=>$from->toDateString(),
            'to'=>$to->toDateString(),
            'income'=>[
                'house'=>$houseIncome, 'shop'=>$shopIncome, 'inventory'=>$invIncome,
                'total'=>$houseIncome+$shopIncome+$invIncome,
            ],
            'expense'=>['payroll'=>$payroll, 'other'=>$other, 'total'=>$payroll+$other],
        ]);
    }
}
