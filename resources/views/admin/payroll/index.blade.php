@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Payroll</h1>

<form method="post" action="{{ route('admin.payroll.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg p-4">
  @csrf
  <div class="flex items-center gap-2 mb-3">
    <label class="text-sm text-gray-600">Month</label>
    <input type="month" name="month" value="{{ $month ?? now()->format('Y-m') }}" class="rounded border-gray-300">
  </div>

  <div class="overflow-auto rounded-lg border">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 sticky top-0">
        <tr>
          <th class="px-3 py-2 text-left">EmployeeId</th>
          <th class="px-3 py-2 text-left">Type</th>
          <th class="px-3 py-2 text-right">Wage Amt</th>
          <th class="px-3 py-2 text-right">Workdays (if daily)</th>
          <th class="px-3 py-2 text-right">Deduction</th>
          <th class="px-3 py-2">Paid Type</th>
          <th class="px-3 py-2">Payslip</th>
          <th class="px-3 py-2">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @foreach($contracts ?? [] as $i=>$c)
          <tr>
            <td class="px-3 py-2">{{ $c->EmployeeId }}
              <input type="hidden" name="rows[{{ $i }}][EmployeeId]" value="{{ $c->EmployeeId }}">
            </td>
            <td class="px-3 py-2">{{ $c->contractType }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($c->waheAmount,2) }}</td>
            <td class="px-3 py-2 text-right">
              <input name="rows[{{ $i }}][workdays]" type="number" step="1" min="0" class="w-24 rounded border-gray-300">
            </td>
            <td class="px-3 py-2 text-right">
              <input name="rows[{{ $i }}][deduction]" type="number" step="0.01" min="0" class="w-24 rounded border-gray-300" value="0">
            </td>
            <td class="px-3 py-2">
              <select name="rows[{{ $i }}][paidType]" class="rounded border-gray-300">
                <option value="cash">cash</option>
                <option value="bank">bank</option>
              </select>
            </td>
            <td class="px-3 py-2">
              <input type="file" name="rows[{{ $i }}][files]" accept="application/pdf,image/png,image/jpeg" class="text-sm">
            </td>
            <td class="px-3 py-2">
              <select name="rows[{{ $i }}][status]" class="rounded border-gray-300">
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
              </select>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="text-right mt-3">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save Payroll</button>
  </div>
</form>
@endsection
