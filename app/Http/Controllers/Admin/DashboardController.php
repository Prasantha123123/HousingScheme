<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $month = $req->get('month', now()->format('Y-m'));
        [$y,$m] = explode('-', $month);
        $from = Carbon::create($y,$m,1)->startOfDay();
        $to   = (clone $from)->endOfMonth();

        $houseIncome = HouseRental::whereBetween('timestamp', [$from,$to])->sum('paidAmount');
        $shopIncome  = ShopRental::whereBetween('timestamp', [$from,$to])->sum('paidAmount');
        $invIncome   = InventorySale::whereBetween('date', [$from,$to])->sum('total');
        $income = $houseIncome + $shopIncome + $invIncome;

        $payroll = Payroll::whereBetween('timestamp', [$from,$to])->sum('wage_net');
        $other   = Expense::whereBetween('timestamp', [$from,$to])->sum('amount');
        $expenses = $payroll + $other;

        $pending = HouseRental::where('status','Pending')->count()
                 + ShopRental::where('status','Pending')->count();

        $latestPending = collect()
            ->merge(HouseRental::where('status','Pending')->latest('timestamp')->take(10)->get()->map(fn($r)=>[
                'type'=>'House','houseNo'=>$r->houseNo,'month'=>$r->month,'amount'=>$r->billAmount,'timestamp'=>$r->timestamp
            ]))
            ->merge(ShopRental::where('status','Pending')->latest('timestamp')->take(10)->get()->map(fn($r)=>[
                'type'=>'Shop','shopNumber'=>$r->shopNumber,'month'=>$r->month,'amount'=>$r->billAmount,'timestamp'=>$r->timestamp
            ]))
            ->sortByDesc('timestamp')->take(10)->values();

        return view('admin.dashboard.index', compact('month','income','expenses','pending','latestPending'));
    }
}
