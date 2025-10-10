@extends('layouts.app')

@section('content')
{{-- Top filter for month --}}
<form method="get" class="mb-4">
  <div class="flex flex-wrap items-end gap-2">
    <label class="block">
      <span class="text-sm text-gray-600">Month</span>
      <input type="month" name="month" value="{{ $month }}"
             class="mt-1 rounded border-gray-300">
    </label>
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Apply</button>
  </div>
</form>

{{-- AR Summary integrated --}}
<div class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
  <h3 class="text-lg font-semibold text-gray-800 mb-3">Collections Analysis</h3>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="text-center">
      <div class="text-sm text-gray-600">Expected Collections</div>
      <div class="text-lg font-bold text-gray-800">Rs {{ number_format($billedRental ?? 0, 2) }}</div>
      <div class="text-xs text-gray-500">Current month bills</div>
    </div>
    <div class="text-center">
      <div class="text-sm text-gray-600">Actual Collections</div>
      <div class="text-lg font-bold text-green-600">Rs {{ number_format(($houseCollected ?? 0) + ($shopCollected ?? 0), 2) }}</div>
      <div class="text-xs text-gray-500">Total received</div>
    </div>
    <div class="text-center">
      <div class="text-sm text-gray-600">House Carry Forward</div>
      <div class="text-lg font-bold text-blue-600">Rs {{ number_format($houseCarryForward ?? 0, 2) }}</div>
      <div class="text-xs text-gray-500">House pending collected</div>
    </div>
    <div class="text-center">
      <div class="text-sm text-gray-600">Shop Carry Forward</div>
      <div class="text-lg font-bold text-orange-600">Rs {{ number_format($shopCarryForward ?? 0, 2) }}</div>
      <div class="text-xs text-gray-500">Shop pending collected</div>
    </div>
  </div>
</div>

{{-- Accrual & Cash overview --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
  <x-stat title="Billed Rentals ({{ $month }})" :value="number_format($billedRental ?? 0, 2)"/>
  <x-stat title="Collected (Cash)" :value="number_format($collectedCash ?? 0, 2)"/>
  <x-stat title="Opening A/R" :value="number_format($openingAR ?? 0, 2)"/>
  <x-stat title="Closing A/R" :value="number_format($closingAR ?? 0, 2)"/>
</div>



{{-- Cash P&L style (Collections vs Expenses) --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
  <x-stat title="House Collected" :value="number_format($houseCollected ?? 0, 2)"/>
  <x-stat title="Shop Collected" :value="number_format($shopCollected ?? 0, 2)"/>
  <x-stat title="Inventory Sales (Cash)" :value="number_format($invCollected ?? 0, 2)"/>
</div>

{{-- Carry Forward Breakdown --}}
<div class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
  <h3 class="text-lg font-semibold text-gray-800 mb-3">Carry Forward Analysis ({{ Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }})</h3>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    {{-- House Carry Forward --}}
    <div class="border rounded-lg p-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-semibold text-blue-700">🏠 House Rentals</h4>
        <span class="text-lg font-bold text-blue-600">Rs {{ number_format($houseCarryForward ?? 0, 2) }}</span>
      </div>
      <div class="text-sm text-gray-600">
        <div class="flex justify-between py-1">
          <span>Current Month Billed:</span>
          <span>Rs {{ number_format($houseBilled ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between py-1">
          <span>Current Month Collected:</span>
          <span>Rs {{ number_format($houseCollected ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between py-1 border-t pt-2 font-semibold">
          <span>Previous Month Pending:</span>
          <span class="text-blue-600">Rs {{ number_format($housePreviousMonthPending ?? 0, 2) }}</span>
        </div>
        @php
          $housePreviousCollected = max(0, floatval($houseCollected ?? 0) - floatval($houseBilled ?? 0));
        @endphp
        <div class="flex justify-between py-1 {{ $housePreviousCollected > 0 ? 'bg-green-50' : 'bg-gray-50' }} px-2 rounded mt-2">
          <span class="{{ $housePreviousCollected > 0 ? 'text-green-700' : 'text-gray-600' }} font-semibold">
            {{ $housePreviousCollected > 0 ? '✓' : '•' }} Previous Pending Collected:
          </span>
          <span class="{{ $housePreviousCollected > 0 ? 'text-green-700' : 'text-gray-600' }} font-bold">
            Rs {{ number_format($housePreviousCollected, 2) }}
          </span>
        </div>
      </div>
    </div>
    
    {{-- Shop Carry Forward --}}
    <div class="border rounded-lg p-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-semibold text-orange-700">🏪 Shop Rentals</h4>
        <span class="text-lg font-bold text-orange-600">Rs {{ number_format($shopCarryForward ?? 0, 2) }}</span>
      </div>
      <div class="text-sm text-gray-600">
        <div class="flex justify-between py-1">
          <span>Current Month Billed:</span>
          <span>Rs {{ number_format($shopBilled ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between py-1">
          <span>Current Month Collected:</span>
          <span>Rs {{ number_format($shopCollected ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between py-1 border-t pt-2 font-semibold">
          <span>Previous Month Pending:</span>
          <span class="text-orange-600">Rs {{ number_format($shopPreviousMonthPending ?? 0, 2) }}</span>
        </div>
        @php
          $shopPreviousCollected = max(0, floatval($shopCollected ?? 0) - floatval($shopBilled ?? 0));
        @endphp
        <div class="flex justify-between py-1 {{ $shopPreviousCollected > 0 ? 'bg-green-50' : 'bg-gray-50' }} px-2 rounded mt-2">
          <span class="{{ $shopPreviousCollected > 0 ? 'text-green-700' : 'text-gray-600' }} font-semibold">
            {{ $shopPreviousCollected > 0 ? '✓' : '•' }} Previous Pending Collected:
          </span>
          <span class="{{ $shopPreviousCollected > 0 ? 'text-green-700' : 'text-gray-600' }} font-bold">
            Rs {{ number_format($shopPreviousCollected, 2) }}
          </span>
        </div>
      </div>
    </div>
  </div>
  
  {{-- Total Summary --}}
  <div class="mt-4 pt-4 border-t">
    <div class="flex justify-between items-center">
      <span class="text-lg font-semibold">Total Carry Forward:</span>
      <span class="text-xl font-bold text-green-600">Rs {{ number_format(($houseCarryForward ?? 0) + ($shopCarryForward ?? 0), 2) }}</span>
    </div>
    <div class="text-sm text-gray-600 mt-1">
      This represents previous month pending amounts collected in {{ Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
    </div>
  </div>
</div>
{{-- Expenses and Net Summary --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
  <x-stat title="Expenses (month)" :value="number_format($expenses ?? 0, 2)"/>
  <x-stat title="Cash Net (Collections - Expenses)" :value="number_format($cashNet ?? 0, 2)"/>
  <x-stat title="Entities" :value="number_format(($totalHouses ?? 0) + ($totalShops ?? 0))" subtitle="{{ number_format($totalHouses ?? 0) }} Houses → {{ number_format($totalShops ?? 0) }} Shops"/>
</div>

{{-- Generation counts --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
  <x-stat title="House Bills Generated ({{ $month }})" :value="number_format($houseGenerated ?? 0)"/>
  <x-stat title="Shop Bills Generated ({{ $month }})" :value="number_format($shopGenerated ?? 0)"/>
  <x-stat title="Pending Count" :value="number_format($pendingCount ?? 0)"/>
  <x-stat title="Completed Count" :value="number_format($completedCount ?? 0)"/>
</div>

{{-- Houses / Shops split --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
  {{-- Houses --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-3">Houses — {{ $month }}</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
      <div class="rounded border p-3">
        <div class="text-gray-600">Billed</div>
        <div class="text-xl font-semibold">{{ number_format($houseBilled ?? 0, 2) }}</div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Pending (count → outstanding)</div>
        <div class="text-xl font-semibold">
          {{ number_format($housePendingCount ?? 0) }} → {{ number_format($housePendingTotal ?? 0, 2) }}
        </div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Completed (count → collected)</div>
        <div class="text-xl font-semibold">
          {{ number_format($houseCompletedCount ?? 0) }} → {{ number_format($houseCompletedTotal ?? 0, 2) }}
        </div>
      </div>
    </div>
  </div>

  {{-- Shops --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-3">Shops — {{ $month }}</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
      <div class="rounded border p-3">
        <div class="text-gray-600">Billed</div>
        <div class="text-xl font-semibold">{{ number_format($shopBilled ?? 0, 2) }}</div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Pending (count → outstanding)</div>
        <div class="text-xl font-semibold">
          {{ number_format($shopPendingCount ?? 0) }} → {{ number_format($shopPendingTotal ?? 0, 2) }}
        </div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Completed (count → collected)</div>
        <div class="text-xl font-semibold">
          {{ number_format($shopCompletedCount ?? 0) }} → {{ number_format($shopCompletedTotal ?? 0, 2) }}
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Enhanced Payment Metrics Section --}}
@include('admin.dashboard.partials._payment_metrics')

@endsection

