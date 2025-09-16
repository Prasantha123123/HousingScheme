<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(){
        $rows = Expense::orderByDesc('date')->paginate(20);
        return view('admin.expenses.index', compact('rows'));
    }

    public function create(){
        return view('admin.expenses.create');
    }

    public function store(Request $r){
        // Multi-row payload
        if ($r->has('rows')) {
            $data = $r->validate([
                'rows'            => ['required','array','min:1'],
                'rows.*.date'     => ['required','date'],
                'rows.*.name'     => ['required','string','max:255'],
                'rows.*.amount'   => ['required','numeric','min:0'],
                'rows.*.note'     => ['nullable','string','max:1000'],
            ]);

            DB::transaction(function () use ($data) {
                foreach ($data['rows'] as $row) {
                    Expense::create([
                        'date'      => $row['date'],
                        'name'      => $row['name'],
                        'amount'    => (float)$row['amount'],
                        'note'      => $row['note'] ?? null,
                        'timestamp' => now(),
                    ]);
                }
            });

            return redirect()->route('admin.expenses.index')->with('success','Expenses saved.');
        }

        // Single-row fallback (backwards compatible with your old form)
        $data = $r->validate([
            'date'   => 'required|date',
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'note'   => 'nullable|string|max:1000',
        ]);

        Expense::create($data + ['timestamp'=>now()]);
        return redirect()->route('admin.expenses.index')->with('success','Saved');
    }

    public function edit(Expense $expense){
        return view('admin.expenses.edit', ['row'=>$expense]);
    }

    public function update(Request $r, Expense $expense){
        $data = $r->validate([
            'date'   => 'required|date',
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'note'   => 'nullable|string|max:1000',
        ]);
        $expense->update($data);
        return back()->with('success','Updated');
    }

    public function destroy(Expense $expense){
        $expense->delete();
        return back()->with('success','Deleted');
    }
}
