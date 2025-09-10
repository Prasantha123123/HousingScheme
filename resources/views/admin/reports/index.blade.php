@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Reports</h1>

<form method="get" class="bg-white rounded-lg p-4 grid md:grid-cols-4 gap-3 mb-3">
  <label>From <input type="date" name="from" value="{{ $from ?? request('from') }}" class="w-full rounded border-gray-300" required></label>
  <label>To <input type="date" name="to" value="{{ $to ?? request('to') }}" class="w-full rounded border-gray-300" required></label>
  <div class="md:col-span-2 flex items-end gap-2">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Apply</button>
    <a class="px-3 py-2 bg-red-600 text-white rounded-lg" href="{{ route('admin.reports.export.pdf', request()->query()) }}">Export PDF</a>
    <a class="px-3 py-2 bg-blue-600 text-white rounded-lg" href="{{ route('admin.reports.export.csv', request()->query()) }}">Export CSV</a>
  </div>
</form>

<div class="grid md:grid-cols-3 gap-4 mb-4">
  <x-stat title="Total Income" :value="number_format(($income['total'] ?? 0),2)"/>
  <x-stat title="Total Expenses" :value="number_format(($expense['total'] ?? 0),2)"/>
  <x-stat title="Net" :value="number_format(($income['total'] ?? 0)-($expense['total'] ?? 0),2)"/>
</div>

<div class="grid md:grid-cols-2 gap-4">
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Income Breakdown</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>House Charges</span><span>{{ number_format($income['house'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Shop Rentals</span><span>{{ number_format($income['shop'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Inventory Sales</span><span>{{ number_format($income['inventory'] ?? 0, 2) }}</span></li>
    </ul>
  </div>
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Expense Breakdown</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>Payroll</span><span>{{ number_format($expense['payroll'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Other</span><span>{{ number_format($expense['other'] ?? 0, 2) }}</span></li>
    </ul>
  </div>
</div>
@endsection
