@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add Expenses</h1>

<form method="post" action="{{ route('admin.expenses.store') }}"
      class="bg-white rounded-lg p-4 space-y-3"
      x-data="expForm()" x-init="addRowWithDate('{{ now()->toDateString() }}')">
  @csrf

  @if ($errors->any())
    <div class="rounded border border-red-200 bg-red-50 text-red-700 p-3 text-sm">
      <ul class="list-disc list-inside">
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
          <th class="py-2 px-2">Name</th>
          <th class="py-2 px-2 w-36 text-right">Amount</th>
          <th class="py-2 px-2 w-64">Note</th>
          <th class="py-2 px-2 w-14"></th>
        </tr>
      </thead>
      <tbody>
        <template x-for="(row, i) in rows" :key="i">
          <tr class="border-b align-middle">
            <td class="py-2 px-2">
              <input type="date" class="w-full rounded border-gray-300"
                     x-model="row.date"
                     :name="`rows[${i}][date]`" required>
            </td>
            <td class="py-2 px-2">
              <input type="text" class="w-full rounded border-gray-300"
                     x-model="row.name"
                     :name="`rows[${i}][name]`" required>
            </td>
            <td class="py-2 px-2">
              <input type="number" min="0" step="0.01"
                     class="w-full rounded border-gray-300 text-right"
                     x-model.number="row.amount"
                     :name="`rows[${i}][amount]`" required>
            </td>
            <td class="py-2 px-2">
              <input type="text" class="w-full rounded border-gray-300"
                     x-model="row.note"
                     :name="`rows[${i}][note]`" placeholder="optional">
            </td>
            <td class="py-2 px-2 text-right">
              <button type="button" class="px-2 py-1 text-red-700 disabled:text-gray-300"
                      :disabled="rows.length === 1"
                      @click="removeRow(i)">Remove</button>
            </td>
          </tr>
        </template>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" class="py-2 px-2 text-right font-medium">Total</td>
          <td class="py-2 px-2 text-right font-semibold tabular-nums">
            <span x-text="grandTotal()"></span>
          </td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <button type="button" class="px-3 py-2 bg-white border rounded-lg" @click="addRowWithDate('{{ now()->toDateString() }}')">
      + Add Row
    </button>
    <button type="button" class="px-3 py-2 bg-white border rounded-lg" @click="addRows(5, '{{ now()->toDateString() }}')">
      + Add 5 Rows
    </button>
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">
      Save All
    </button>
  </div>
</form>

<script>
function expForm(){
  return {
    rows: [],
    addRow(){ this.rows.push({date:'', name:'', amount:0, note:''}) },
    addRowWithDate(d){ this.rows.push({date:d, name:'', amount:0, note:''}) },
    addRows(n, d){ for(let i=0;i<n;i++) this.addRowWithDate(d) },
    removeRow(i){ this.rows.splice(i,1) },
    grandTotal(){
      const sum = this.rows.reduce((a,r)=> a + Number(r.amount||0), 0);
      return sum.toFixed(2);
    }
  }
}
</script>
@endsection
