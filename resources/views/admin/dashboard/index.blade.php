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

{{-- Existing: Income / Expenses / Net --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
  <x-stat title="Total Income (month)" :value="number_format($income ?? 0,2)"/>
  <x-stat title="Total Expenses (month)" :value="number_format($expenses ?? 0,2)"/>
  <x-stat title="Net" :value="number_format(($income ?? 0)-($expenses ?? 0),2)"/>
</div>

{{-- New: Entities --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
  <x-stat title="Total Houses" :value="number_format($totalHouses ?? 0)"/>
  <x-stat title="Total Shops" :value="number_format($totalShops ?? 0)"/>

  <x-stat title="House Bills Generated ({{ $month }})" :value="number_format($houseGenerated ?? 0)"/>
  <x-stat title="Shop Bills Generated ({{ $month }})" :value="number_format($shopGenerated ?? 0)"/>
</div>

{{-- Houses / Shops split (separate grids) --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
  {{-- Houses --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-3">Houses — {{ $month }}</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
      <div class="rounded border p-3">
        <div class="text-gray-600">Generated</div>
        <div class="text-xl font-semibold">{{ number_format($houseGenerated ?? 0) }}</div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Pending (count · amount)</div>
        <div class="text-xl font-semibold">
          {{ number_format($housePendingCount ?? 0) }} · {{ number_format($housePendingTotal ?? 0, 2) }}
        </div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Completed (count · amount)</div>
        <div class="text-xl font-semibold">
          {{ number_format($houseCompletedCount ?? 0) }} · {{ number_format($houseCompletedTotal ?? 0, 2) }}
        </div>
      </div>
    </div>
  </div>

  {{-- Shops --}}
  <div class="bg-white rounded-lg p-4">
    <h3 class="font-semibold mb-3">Shops — {{ $month }}</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
      <div class="rounded border p-3">
        <div class="text-gray-600">Generated</div>
        <div class="text-xl font-semibold">{{ number_format($shopGenerated ?? 0) }}</div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Pending (count · amount)</div>
        <div class="text-xl font-semibold">
          {{ number_format($shopPendingCount ?? 0) }} · {{ number_format($shopPendingTotal ?? 0, 2) }}
        </div>
      </div>
      <div class="rounded border p-3">
        <div class="text-gray-600">Completed (count · amount)</div>
        <div class="text-xl font-semibold">
          {{ number_format($shopCompletedCount ?? 0) }} · {{ number_format($shopCompletedTotal ?? 0, 2) }}
        </div>
      </div>
    </div>
  </div>
</div>


{{-- Latest 10 Pending (Pending & InProgress) --}}
<div class="bg-white rounded-lg p-4">
  <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
    <h3 class="font-semibold">Latest 10 Pending Payments</h3>
  </div>

  {{-- Mobile: cards --}}
  <div class="sm:hidden space-y-3">
    @forelse(($latestPending ?? []) as $p)
      <div class="rounded-lg border p-3">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium">{{ $p['type'] }}</span>
          <span class="text-sm text-gray-600">{{ $p['month'] }}</span>
        </div>
        <div class="mt-1 text-sm text-gray-700">
          @if(($p['type'] ?? '') === 'House')
            House: <span class="font-medium">{{ $p['houseNo'] }}</span>
          @else
            Shop: <span class="font-medium">{{ $p['shopNumber'] }}</span>
          @endif
        </div>
        <div class="mt-2 text-right font-semibold">
          {{ number_format($p['amount'] ?? 0, 2) }}
        </div>
      </div>
    @empty
      <div class="rounded-lg border p-4 text-gray-500">No pending items</div>
    @endforelse
  </div>

  {{-- Tablet / Desktop: table --}}
  <div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
    <x-table>
      <x-slot:head>
        <th class="px-3 py-2 text-left">Type</th>
        <th class="px-3 py-2 text-left">Ref</th>
        <th class="px-3 py-2 text-right">Amount</th>
      </x-slot:head>

      @forelse(($latestPending ?? []) as $p)
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2">{{ $p['type'] }}</td>
          <td class="px-3 py-2">
            {{ $p['type']=='House' ? $p['houseNo'] : $p['shopNumber'] }} · {{ $p['month'] }}
          </td>
          <td class="px-3 py-2 text-right">{{ number_format($p['amount'],2) }}</td>
        </tr>
      @empty
        <tr><td class="px-3 py-6 text-gray-500" colspan="3">No pending items</td></tr>
      @endforelse
    </x-table>
  </div>
</div>
@endsection
