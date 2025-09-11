@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">Payroll</h1>

<form method="post" action="{{ route('admin.payroll.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg p-4">
  @csrf

  {{-- Month selector: wraps on small screens --}}
  <div class="flex flex-wrap items-center gap-2 mb-3">
    <label class="text-sm text-gray-600">Month</label>
    <input type="month" name="month" value="{{ $month ?? now()->format('Y-m') }}" class="rounded border-gray-300 w-full sm:w-auto">
  </div>

  @php $contracts = $contracts ?? collect(); @endphp

  {{-- ============ Mobile: Card inputs ============ --}}
  <div class="sm:hidden space-y-3">
    @forelse($contracts as $i => $c)
      <div class="rounded-lg border p-3 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-gray-500">Employee ID</div>
            <div class="font-medium">{{ $c->EmployeeId }}</div>
          </div>
          <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
            {{ $c->contractType }}
          </span>
        </div>

        <input type="hidden" name="rows[{{ $i }}][EmployeeId]" value="{{ $c->EmployeeId }}">

        <div class="mt-3 grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-600">Wage Amount</label>
            <div class="font-medium">{{ number_format($c->waheAmount,2) }}</div>
          </div>

          <div>
            <label class="block text-xs text-gray-600">Workdays (if daily)</label>
            <input name="rows[{{ $i }}][workdays]" type="number" step="1" min="0" class="mt-1 w-full rounded border-gray-300" inputmode="numeric">
          </div>

          <div>
            <label class="block text-xs text-gray-600">Deduction</label>
            <input name="rows[{{ $i }}][deduction]" type="number" step="0.01" min="0" value="0" class="mt-1 w-full rounded border-gray-300">
          </div>

          <div>
            <label class="block text-xs text-gray-600">Paid Type</label>
            <select name="rows[{{ $i }}][paidType]" class="mt-1 w-full rounded border-gray-300">
              <option value="cash">cash</option>
              <option value="bank">bank</option>
            </select>
          </div>

          <div class="col-span-2">
            <label class="block text-xs text-gray-600">Payslip (PDF/JPG/PNG, ≤5MB)</label>
            <input type="file" name="rows[{{ $i }}][files]" accept="application/pdf,image/png,image/jpeg" class="mt-1 w-full text-sm">
          </div>

          <div>
            <label class="block text-xs text-gray-600">Status</label>
            <select name="rows[{{ $i }}][status]" class="mt-1 w-full rounded border-gray-300">
              <option value="Paid">Paid</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
        </div>
      </div>
    @empty
      <div class="rounded-lg border bg-white p-4 text-gray-500">No contracts</div>
    @endforelse
  </div>

  {{-- ============ Tablet / Desktop: Table ============ --}}
  <div class="hidden sm:block overflow-auto rounded-lg border mt-3 sm:mt-0">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 sticky top-0">
        <tr>
          <th class="px-3 py-2 text-left">EmployeeId</th>
          <th class="px-3 py-2 text-left">Type</th>
          <th class="px-3 py-2 text-right">Wage Amt</th>
          <th class="px-3 py-2 text-right">Workdays (if daily)</th>
          <th class="px-3 py-2 text-right">Deduction</th>
          <th class="px-3 py-2">Paid Type</th>
          <th class="px-3 py-2 hidden md:table-cell">Payslip</th>
          <th class="px-3 py-2">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @foreach($contracts as $i=>$c)
          <tr class="hover:bg-gray-50">
            <td class="px-3 py-2">
              {{ $c->EmployeeId }}
              <input type="hidden" name="rows[{{ $i }}][EmployeeId]" value="{{ $c->EmployeeId }}">
            </td>
            <td class="px-3 py-2">{{ $c->contractType }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($c->waheAmount,2) }}</td>
            <td class="px-3 py-2 text-right">
              <input name="rows[{{ $i }}][workdays]" type="number" step="1" min="0" class="w-24 rounded border-gray-300" inputmode="numeric">
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
            <td class="px-3 py-2 hidden md:table-cell">
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
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Save Payroll</button>
  </div>
</form>
@endsection
