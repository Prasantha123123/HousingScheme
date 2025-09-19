<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseController extends Controller
{
    public function index(Request $request){
        $rows = Expense::query()
            ->when($request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $toDate = $request->date('to_date');
                $q->whereBetween('date', [$fromDate, $toDate]);
            })
            ->when($request->filled('from_date') && !$request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $q->where('date', '>=', $fromDate);
            })
            ->when(!$request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $toDate = $request->date('to_date');
                $q->where('date', '<=', $toDate);
            })
            ->when($request->filled('name'), function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->string('name') . '%');
            })
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();
            
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

    /**
     * Download PDF of expenses with applied filters
     */
    public function downloadPdf(Request $request)
    {
        $rows = Expense::query()
            ->when($request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $toDate = $request->date('to_date');
                $q->whereBetween('date', [$fromDate, $toDate]);
            })
            ->when($request->filled('from_date') && !$request->filled('to_date'), function($q) use ($request) {
                $fromDate = $request->date('from_date');
                $q->where('date', '>=', $fromDate);
            })
            ->when(!$request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
                $toDate = $request->date('to_date');
                $q->where('date', '<=', $toDate);
            })
            ->when($request->filled('name'), function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->string('name') . '%');
            })
            ->orderByDesc('date')
            ->get();

        // Calculate totals
        $totalAmount = $rows->sum('amount');

        // Generate filters text for PDF header
        $filters = [];
        if ($request->filled('from_date')) {
            $filters[] = 'From: ' . $request->string('from_date');
        }
        if ($request->filled('to_date')) {
            $filters[] = 'To: ' . $request->string('to_date');
        }
        if ($request->filled('name')) {
            $filters[] = 'Name: ' . $request->string('name');
        }
        $filtersText = empty($filters) ? 'All Records' : implode(', ', $filters);

        $pdf = Pdf::loadView('admin.expenses.pdf', [
            'expenses' => $rows,
            'totalAmount' => $totalAmount,
            'filtersText' => $filtersText
        ])->setPaper('a4', 'landscape');

        $filename = 'expenses-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }
}
