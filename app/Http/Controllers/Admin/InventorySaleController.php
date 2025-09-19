<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventorySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InventorySaleController extends Controller
{
    public function index(Request $request) {
        $rows = InventorySale::query()
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
            ->when($request->filled('item'), function($q) use ($request) {
                $q->where('item', 'like', '%' . $request->string('item') . '%');
            })
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();
            
        return view('admin.inventory_sales.index', compact('rows'));
    }

    public function create(){
        return view('admin.inventory_sales.create');
    }

    public function store(Request $r){
        // If the new multi-row payload is present
        if ($r->has('rows')) {
            $data = $r->validate([
                'rows'                 => ['required','array','min:1'],
                'rows.*.date'          => ['required','date'],
                'rows.*.item'          => ['required','string','max:255'],
                'rows.*.qty'           => ['required','integer','min:0'],
                'rows.*.unit_price'    => ['required','numeric','min:0'],
                'rows.*.note'          => ['nullable','string','max:1000'],
            ]);

            DB::transaction(function () use ($data) {
                foreach ($data['rows'] as $row) {
                    InventorySale::create([
                        'date'       => $row['date'],
                        'item'       => $row['item'],
                        'qty'        => (int) $row['qty'],
                        'unit_price' => (float) $row['unit_price'],
                        'total'      => (int)$row['qty'] * (float)$row['unit_price'],
                        'note'       => $row['note'] ?? null,
                        'timestamp'  => now(),
                    ]);
                }
            });

            return redirect()->route('admin.inventory-sales.index')->with('success','Saved items.');
        }

        // Fallback: single-row form (backwards compatible)
        $data = $r->validate([
            'date'       => 'required|date',
            'item'       => 'required|string|max:255',
            'qty'        => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'note'       => 'nullable|string|max:1000'
        ]);

        $data['total']     = $data['qty'] * $data['unit_price'];
        $data['timestamp'] = now();

        InventorySale::create($data);

        return redirect()->route('admin.inventory-sales.index')->with('success','Saved.');
    }

    public function edit(InventorySale $inventory_sale){
        return view('admin.inventory_sales.edit', ['row'=>$inventory_sale]);
    }

    public function update(Request $r, InventorySale $inventory_sale){
        $data = $r->validate([
            'date'       => 'required|date',
            'item'       => 'required|string|max:255',
            'qty'        => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'note'       => 'nullable|string|max:1000'
        ]);
        $data['total'] = $data['qty'] * $data['unit_price'];
        $inventory_sale->update($data);
        return back()->with('success','Updated');
    }

    public function destroy(InventorySale $inventory_sale){
        $inventory_sale->delete();
        return back()->with('success','Deleted');
    }

    /**
     * Download PDF of inventory sales with applied filters
     */
    public function downloadPdf(Request $request)
    {
        $rows = InventorySale::query()
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
            ->when($request->filled('item'), function($q) use ($request) {
                $q->where('item', 'like', '%' . $request->string('item') . '%');
            })
            ->orderByDesc('date')
            ->get();

        // Calculate totals
        $totalAmount = $rows->sum('total');
        $totalQty = $rows->sum('qty');

        // Generate filters text for PDF header
        $filters = [];
        if ($request->filled('from_date')) {
            $filters[] = 'From: ' . $request->string('from_date');
        }
        if ($request->filled('to_date')) {
            $filters[] = 'To: ' . $request->string('to_date');
        }
        if ($request->filled('item')) {
            $filters[] = 'Item: ' . $request->string('item');
        }
        $filtersText = empty($filters) ? 'All Records' : implode(', ', $filters);

        $pdf = Pdf::loadView('admin.inventory_sales.pdf', compact(
            'rows', 'totalAmount', 'totalQty', 'filtersText'
        ))->setPaper('a4', 'landscape');

        $filename = 'inventory-sales-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }
}
