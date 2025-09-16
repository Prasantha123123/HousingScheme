{{-- resources/views/admin/inventory_sales/create.blade.php --}}
@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">Add Inventory Sales</h1>

<form method="post" action="{{ route('admin.inventory-sales.store') }}"
      class="bg-white rounded-lg p-4 space-y-3"
      x-data="salesForm()" x-init="addRowWithDate('{{ now()->toDateString() }}')">
  @csrf

  {{-- Errors --}}
  @if ($errors->any())
    <div class="rounded border border-red-200 bg-red-50 text-red-700 p-3 text-sm">
      <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="overflow-x-auto">
    <table class="w-full table-fixed border-collapse">
      <thead>
        <tr class="text-left text-sm text-gray-600 border-b">
          <th class="py-2 px-2 w-40">Date</th>
          <th class="py-2 px-2">Item</th>
          <th class="py-2 px-2 w-28 text-right">Qty</th>
          <th class="py-2 px-2 w-36 text-right">Unit Price</th>
          <th class="py-2 px-2 w-36 text-right">Total</th>
          <th class="py-2 px-2 w-64">Note</th>
          <th class="py-2 px-2 w-14"></th>
        </tr>
      </thead>
      <tbody>
        <template x-for="(row, i) in rows" :key="i">
          <tr class="border-b align-middle">
            {{-- date --}}
            <td class="py-2 px-2">
              <input type="date" class="w-full rounded border-gray-300"
                     x-model="row.date"
                     :name="`rows[${i}][date]`" required>
            </td>

            {{-- item --}}
            <td class="py-2 px-2">
              <input type="text" class="w-full rounded border-gray-300"
                     x-model="row.item"
                     :name="`rows[${i}][item]`" required>
            </td>

            {{-- qty --}}
            <td class="py-2 px-2">
              <input type="number" min="0" step="1" class="w-full rounded border-gray-300 text-right"
                     x-model.number="row.qty"
                     :name="`rows[${i}][qty]`" required>
            </td>

            {{-- unit price --}}
            <td class="py-2 px-2">
              <input type="number" min="0" step="0.01" class="w-full rounded border-gray-300 text-right"
                     x-model.number="row.unit_price"
                     :name="`rows[${i}][unit_price]`" required>
            </td>

            {{-- total (display only) --}}
            <td class="py-2 px-2 text-right tabular-nums">
              <span x-text="rowTotal(row)"></span>
            </td>

            {{-- note --}}
            <td class="py-2 px-2">
              <input type="text" class="w-full rounded border-gray-300"
                     x-model="row.note"
                     :name="`rows[${i}][note]`" placeholder="optional">
            </td>

            {{-- remove --}}
            <td class="py-2 px-2 text-right">
              <button type="button" class="px-2 py-1 text-red-700 disabled:text-gray-300"
                      :disabled="rows.length === 1"
                      @click="removeRow(i)">
                Remove
              </button>
            </td>
          </tr>
        </template>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="py-2 px-2 text-right font-medium">Grand Total</td>
          <td class="py-2 px-2 text-right font-semibold tabular-nums">
            <span x-text="grandTotal()"></span>
          </td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <button type="button" class="px-3 py-2 bg-white border rounded-lg"
            @click="addRowWithDate('{{ now()->toDateString() }}')">
      + Add Row
    </button>

    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">
      Save All
    </button>
  </div>
</form>

<script>
function salesForm(){
  return {
    rows: [],
    addRow(){ this.rows.push({date: '', item: '', qty: 1, unit_price: 0, note: ''}) },
    addRowWithDate(d){ this.rows.push({date: d, item: '', qty: 1, unit_price: 0, note: ''}) },
    removeRow(i){ this.rows.splice(i,1) },
    rowTotal(r){
      const q = Number(r.qty||0), p = Number(r.unit_price||0);
      return (q*p).toFixed(2);
    },
    grandTotal(){
      const sum = this.rows.reduce((acc,r)=> acc + (Number(r.qty||0)*Number(r.unit_price||0)), 0);
      return sum.toFixed(2);
    }
  }
}
</script>
@endsection
