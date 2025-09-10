@extends('layouts.app')
@section('content')
<div class="grid md:grid-cols-3 gap-4 mb-4">
  <x-stat title="Total Income (month)" :value="number_format($income ?? 0,2)"/>
  <x-stat title="Total Expenses (month)" :value="number_format($expenses ?? 0,2)"/>
  <x-stat title="Net" :value="number_format(($income ?? 0)-($expenses ?? 0),2)"/>
</div>

<div class="bg-white rounded-lg p-4">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-semibold">Latest 10 Pending Payments</h3>
  </div>
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">Type</th>
      <th class="px-3 py-2 text-left">Ref</th>
      <th class="px-3 py-2 text-right">Amount</th>
    </x-slot:head>
    @forelse(($latestPending ?? []) as $p)
      <tr>
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
@endsection
