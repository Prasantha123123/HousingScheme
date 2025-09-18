@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Reports</h1>

{{-- Filters: 1-col on phones, 2-col on sm, 4-col on md+ --}}
<form method="get" class="bg-white rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-3">
  <label class="block">
    <span class="text-sm text-gray-600">From</span>
    <input type="date" name="from" value="{{ $from ?? request('from') }}" class="mt-1 w-full rounded border-gray-300" required>
  </label>

  <label class="block">
    <span class="text-sm text-gray-600">To</span>
    <input type="date" name="to" value="{{ $to ?? request('to') }}" class="mt-1 w-full rounded border-gray-300" required>
  </label>

  <div class="sm:col-span-2 md:col-span-2 flex flex-wrap items-end gap-2">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Apply</button>
    <a class="px-3 py-2 bg-red-600 text-white rounded-lg w-full sm:w-auto"
       href="{{ route('admin.reports.export.pdf', request()->query()) }}">Export PDF</a>
    <a class="px-3 py-2 bg-blue-600 text-white rounded-lg w-full sm:w-auto"
       href="{{ route('admin.reports.export.csv', request()->query()) }}">Export CSV</a>
  </div>
</form>

{{-- Totals: 1 → 2 → 3 columns across breakpoints (Cash P&L) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
  <x-stat title="Total Income (Cash)" :value="number_format(($income['total'] ?? 0),2)"/>
  <x-stat title="Total Expenses (Cash)" :value="number_format(($expense['total'] ?? 0),2)"/>
  <x-stat title="Net (Cash)" :value="number_format(($income['total'] ?? 0)-($expense['total'] ?? 0),2)"/>
</div>

{{-- Accrual & A/R KPIs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
  <x-stat title="Billed Rentals (Accrual)" :value="number_format(($billed['total'] ?? 0),2)"/>
  <x-stat title="Opening A/R" :value="number_format(($ar['opening'] ?? 0),2)"/>
  <x-stat title="Collected (Rentals)" :value="number_format(($ar['collected_rentals'] ?? 0),2)"/>
  <x-stat title="Closing A/R" :value="number_format(($ar['closing'] ?? 0),2)"/>
</div>

{{-- A/R Movement Details --}}
<div class="bg-white rounded-lg p-4 mb-4">
  <h3 class="font-semibold mb-3">A/R Movement Analysis</h3>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="text-center">
      <div class="text-2xl font-bold text-blue-600">{{ number_format($ar['increase'] ?? 0, 2) }}</div>
      <div class="text-sm text-gray-600">A/R Increase (Billed)</div>
    </div>
    <div class="text-center">
      <div class="text-2xl font-bold text-green-600">{{ number_format($ar['decrease'] ?? 0, 2) }}</div>
      <div class="text-sm text-gray-600">A/R Decrease (Collections)</div>
    </div>
    <div class="text-center">
      <div class="text-2xl font-bold {{ ($ar['net_movement'] ?? 0) >= 0 ? 'text-red-600' : 'text-green-600' }}">
        {{ number_format($ar['net_movement'] ?? 0, 2) }}
      </div>
      <div class="text-sm text-gray-600">Net A/R Movement</div>
    </div>
  </div>
  
  {{-- Validation check --}}
  @if(isset($ar['closing_alternative']))
    @php
      $diff = abs(($ar['closing'] ?? 0) - ($ar['closing_alternative'] ?? 0));
    @endphp
    @if($diff > 0.01)
      <div class="mt-3 p-2 bg-yellow-100 border border-yellow-400 rounded text-sm">
        <strong>Validation Note:</strong> 
        Direct calculation: {{ number_format($ar['closing'] ?? 0, 2) }} | 
        Formula calculation: {{ number_format($ar['closing_alternative'] ?? 0, 2) }} | 
        Difference: {{ number_format($diff, 2) }}
      </div>
    @endif
  @endif
</div>

{{-- Breakdown cards: 1-col on phones, 3-col on md+ --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  {{-- Income (Cash) --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Income (Cash) Breakdown</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>House Collections</span><span>{{ number_format($income['house'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Shop Collections</span><span>{{ number_format($income['shop'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Inventory Sales</span><span>{{ number_format($income['inventory'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between font-semibold border-t pt-1"><span>Total</span><span>{{ number_format($income['total'] ?? 0, 2) }}</span></li>
    </ul>
  </div>

  {{-- Expense (Cash) --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Expense Breakdown</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>Payroll</span><span>{{ number_format($expense['payroll'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Other</span><span>{{ number_format($expense['other'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between font-semibold border-t pt-1"><span>Total</span><span>{{ number_format($expense['total'] ?? 0, 2) }}</span></li>
    </ul>
  </div>

  {{-- Billed (Accrual) --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Billed (Accrual) Breakdown</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>House Billed</span><span>{{ number_format($billed['house'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Shop Billed</span><span>{{ number_format($billed['shop'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between font-semibold border-t pt-1"><span>Total Billed</span><span>{{ number_format($billed['total'] ?? 0, 2) }}</span></li>
    </ul>
  </div>
</div>

{{-- Paid / Part / Unpaid counts --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-4">
  <x-stat title="Paid (full) — All" :value="number_format($counts['paid'] ?? 0)"/>
  <x-stat title="Part-payment — All" :value="number_format($counts['part'] ?? 0)"/>
  <x-stat title="Unpaid — All" :value="number_format($counts['unpaid'] ?? 0)"/>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Houses — Counts</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>Paid</span><span>{{ number_format($counts['house']['paid'] ?? 0) }}</span></li>
      <li class="flex justify-between"><span>Part-payment</span><span>{{ number_format($counts['house']['part'] ?? 0) }}</span></li>
      <li class="flex justify-between"><span>Unpaid</span><span>{{ number_format($counts['house']['unpaid'] ?? 0) }}</span></li>
    </ul>
  </div>
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Shops — Counts</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>Paid</span><span>{{ number_format($counts['shop']['paid'] ?? 0) }}</span></li>
      <li class="flex justify-between"><span>Part-payment</span><span>{{ number_format($counts['shop']['part'] ?? 0) }}</span></li>
      <li class="flex justify-between"><span>Unpaid</span><span>{{ number_format($counts['shop']['unpaid'] ?? 0) }}</span></li>
    </ul>
  </div>
</div>
@endsection
