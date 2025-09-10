<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(){
        $rows = Expense::orderByDesc('date')->paginate(20);
        return view('admin.expenses.index', compact('rows'));
    }
    public function create(){ return view('admin.expenses.create'); }
    public function store(Request $r){
        $data = $r->validate([
            'date'=>'required|date',
            'name'=>'required|string|max:255',
            'amount'=>'required|numeric|min:0',
            'note'=>'nullable|string',
        ]);
        Expense::create($data + ['timestamp'=>now()]);
        return redirect()->route('admin.expenses.index')->with('success','Saved');
    }
    public function edit(Expense $expense){
        return view('admin.expenses.edit', ['row'=>$expense]);
    }
    public function update(Request $r, Expense $expense){
        $data = $r->validate([
            'date'=>'required|date',
            'name'=>'required|string|max:255',
            'amount'=>'required|numeric|min:0',
            'note'=>'nullable|string',
        ]);
        $expense->update($data);
        return back()->with('success','Updated');
    }
    public function destroy(Expense $expense){
        $expense->delete();
        return back()->with('success','Deleted');
    }
}
