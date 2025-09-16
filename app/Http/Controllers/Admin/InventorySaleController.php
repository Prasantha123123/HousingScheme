<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventorySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventorySaleController extends Controller
{
    public function index() {
        $rows = InventorySale::orderByDesc('date')->paginate(15);
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
}
