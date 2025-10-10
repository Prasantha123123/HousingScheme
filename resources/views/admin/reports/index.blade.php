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
  </div>
</form>

{{-- Totals: 1 → 2 → 3 columns across breakpoints (Cash P&L) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
  <x-stat title="Total Income (Cash)" :value="number_format(($income['total'] ?? 0),2)"/>
  <x-stat title="Total Expenses (Cash)" :value="number_format(($expense['total'] ?? 0),2)"/>
  <x-stat title="Net (Cash)" :value="number_format(($income['total'] ?? 0)-($expense['total'] ?? 0),2)"/>
</div>

{{-- Accrual KPIs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
  <x-stat title="Billed Rentals (Accrual)" :value="number_format(($billed['total'] ?? 0),2)"/>
  <x-stat title="Outstanding Amounts" :value="number_format(($ar['closing'] ?? 0),2)"/>
  <x-stat title="Collected (Rentals)" :value="number_format(($ar['collected_rentals'] ?? 0),2)"/>
  <x-stat title="Carry Forward" :value="number_format(($carry_forward['total'] ?? 0),2)"/>
</div>

{{-- Breakdown cards: 2x2 grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-4">
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

  {{-- Carry Forward Analysis --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-2">Carry Forward Analysis</h3>
    <ul class="text-sm space-y-1">
      <li class="flex justify-between"><span>House Carry Forward</span><span>{{ number_format($carry_forward['house'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between"><span>Shop Carry Forward</span><span>{{ number_format($carry_forward['shop'] ?? 0, 2) }}</span></li>
      <li class="flex justify-between font-semibold border-t pt-1"><span>Total Carry Forward</span><span>{{ number_format($carry_forward['total'] ?? 0, 2) }}</span></li>
    </ul>
    <p class="text-xs text-gray-500 mt-2">Payments made for previous month bills</p>
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

{{-- Enhanced Payment Analytics --}}
<div class="bg-white rounded-lg p-6 mb-6 border">
  <h2 class="text-xl font-semibold mb-4">💳 Payment Analytics</h2>
  
  {{-- Pending Payments Alert --}}
  @if(($pendingPayments['total']['count'] ?? 0) > 0)
  <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
    <div class="flex">
      <div class="ml-3">
        <h3 class="text-sm font-medium text-yellow-800">
          ⚠️ {{ $pendingPayments['total']['count'] }} Payment(s) Pending Approval
        </h3>
        <div class="mt-1 text-sm text-yellow-700">
          Total: Rs {{ number_format($pendingPayments['total']['amount'], 2) }}
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Payment Methods Grid --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    @foreach(['cash' => '💵', 'card' => '💳', 'online' => '🌐'] as $method => $icon)
    <div class="border rounded-lg p-4">
      <div class="text-sm text-gray-600 mb-1">{{ $icon }} {{ ucfirst($method) }}</div>
      <div class="text-2xl font-bold text-gray-900 mb-2">
        Rs {{ number_format($paymentMethods[$method]['total']['amount'] ?? 0, 2) }}
      </div>
      <div class="text-xs text-gray-600">
        {{ ($paymentMethods[$method]['total']['count'] ?? 0) }} transactions
      </div>
      <div class="mt-2 pt-2 border-t text-xs space-y-1">
        <div class="flex justify-between">
          <span>Houses:</span>
          <span>Rs {{ number_format($paymentMethods[$method]['house']['amount'] ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Shops:</span>
          <span>Rs {{ number_format($paymentMethods[$method]['shop']['amount'] ?? 0, 2) }}</span>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Payment Types --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach(['fullpayment' => ['label' => 'Full Payments', 'color' => 'green', 'icon' => '✅'], 
              'partpayment' => ['label' => 'Partial Payments', 'color' => 'orange', 'icon' => '⚠️']] as $type => $config)
    <div class="border rounded-lg p-4">
      <div class="text-sm text-{{ $config['color'] }}-600 font-medium mb-1">
        {{ $config['icon'] }} {{ $config['label'] }}
      </div>
      <div class="text-2xl font-bold text-{{ $config['color'] }}-700 mb-2">
        Rs {{ number_format($paymentTypes[$type]['total']['amount'] ?? 0, 2) }}
      </div>
      <div class="text-xs text-gray-600 mb-2">
        {{ ($paymentTypes[$type]['total']['count'] ?? 0) }} payments
      </div>
      <div class="text-xs space-y-1">
        <div class="flex justify-between">
          <span>Houses:</span>
          <span class="font-medium">
            {{ ($paymentTypes[$type]['house']['count'] ?? 0) }} payments
            (Rs {{ number_format($paymentTypes[$type]['house']['amount'] ?? 0, 2) }})
          </span>
        </div>
        <div class="flex justify-between">
          <span>Shops:</span>
          <span class="font-medium">
            {{ ($paymentTypes[$type]['shop']['count'] ?? 0) }} payments
            (Rs {{ number_format($paymentTypes[$type]['shop']['amount'] ?? 0, 2) }})
          </span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>

@endsection

