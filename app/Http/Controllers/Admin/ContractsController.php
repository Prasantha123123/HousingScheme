<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractsController extends Controller
{
    public function index(){ $rows = Contract::orderBy('EmployeeId')->paginate(20); return view('admin.contracts.index', compact('rows')); }
    public function create(){ return view('admin.contracts.create'); }
    public function store(Request $r){
        $data = $r->validate([
            'EmployeeId'=>'required|integer',
            'contractType'=>'required|in:dailysallary,monthlysalary',
            'waheAmount'=>'required|numeric|min:0',
        ]);
        Contract::create($data + ['timestamp'=>now()]);
        return redirect()->route('admin.contracts.index')->with('success','Saved');
    }
    public function edit(Contract $contract){ return view('admin.contracts.edit', ['row'=>$contract]); }
    public function update(Request $r, Contract $contract){
        $data = $r->validate([
            'EmployeeId'=>'required|integer',
            'contractType'=>'required|in:dailysallary,monthlysalary',
            'waheAmount'=>'required|numeric|min:0',
        ]);
        $contract->update($data);
        return back()->with('success','Updated');
    }
    public function destroy(Contract $contract){ $contract->delete(); return back()->with('success','Deleted'); }
}
