<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayrollController extends Controller
{
    public function index(Request $r)
    {
        $month = $r->get('month', now()->format('Y-m'));
        $contracts = Contract::orderBy('EmployeeId')->get();
        return view('admin.payroll.index', compact('contracts','month'));
    }

    public function store(Request $r)
    {
        $month = $r->validate(['month'=>'required|date_format:Y-m'])['month'];

        foreach ((array)$r->get('rows',[]) as $row) {
            $contract = Contract::where('EmployeeId', $row['EmployeeId'] ?? null)->first();
            if (!$contract) continue;

            $workdays  = (int)($row['workdays'] ?? 0);
            $deduction = (float)($row['deduction'] ?? 0);
            $wageAmount= (float)$contract->waheAmount;

            $wage_net = $contract->contractType === 'dailysallary'
                ? max(0, $workdays * $wageAmount - $deduction)
                : max(0, $wageAmount - $deduction);

            $filePath = null;
            if (isset($row['files']) && $row['files']) {
                $file = $row['files'];
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $filePath = $file->store('payslips','public');
                }
            }

            Payroll::create([
                'EmployeeId' => (int)$row['EmployeeId'],
                'workdays'   => $workdays ?: null,
                'wage_net'   => $wage_net,
                'deduction'  => $deduction,
                'files'      => $filePath,
                'paidType'   => $row['paidType'] ?? 'cash',
                'status'     => $row['status'] ?? 'Paid',
                'timestamp'  => Carbon::createFromFormat('Y-m-d', $month.'-01'),
            ]);
        }

        return back()->with('success','Payroll saved');
    }

    public function history(Request $r)
    {
        $rows = Payroll::orderByDesc('timestamp')->paginate(20);
        return view('admin.payroll.history', compact('rows'));
    }
}
