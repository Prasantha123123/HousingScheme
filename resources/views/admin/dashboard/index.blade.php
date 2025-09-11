@extends('layouts.app')

@section('content')
{{-- Stats: 1-col on xs, 2-col on sm, 3-col on lg --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
  <x-stat title="Total Income (month)" :value="number_format($income ?? 0,2)"/>
  <x-stat title="Total Expenses (month)" :value="number_format($expenses ?? 0,2)"/>
  <x-stat title="Net" :value="number_format(($income ?? 0)-($expenses ?? 0),2)"/>
</div>

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
