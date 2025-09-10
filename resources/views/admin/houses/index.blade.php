@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-3">
  <h1 class="text-xl font-semibold">Houses</h1>
  <form method="post" action="{{ route('admin.house-bills.generate') }}" class="flex items-center gap-2">
    @csrf
    <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="rounded border-gray-300">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Generate Current Month Bills</button>
  </form>
</div>

<form method="get" class="mb-3 flex gap-2">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search house no / owner"
         class="rounded border-gray-300">
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Search</button>
</form>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">House No</th>
    <th class="px-3 py-2 text-left">Owner</th>
    <th class="px-3 py-2 text-left">Latest Bill</th>
    <th class="px-3 py-2 text-left">Status</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>

  @forelse($rows ?? [] as $row)
    <tr>
      <td class="px-3 py-2">{{ $row->houseNo }}</td>
      <td class="px-3 py-2">{{ $row->owner_name ?? '-' }}</td>
      <td class="px-3 py-2">
        {{ $row->latest_bill_month ?? '-' }}
        @if(isset($row->latest_bill_amount)) · {{ number_format($row->latest_bill_amount,2) }} @endif
      </td>
      <td class="px-3 py-2"><x-badge :status="$row->latest_status ?? 'Pending'"/></td>
      <td class="px-3 py-2 text-right">
        <a class="text-blue-600 hover:underline" href="{{ route('admin.houses.show',$row->houseNo) }}">View</a>
      </td>
    </tr>
  @empty
    <tr><td class="px-3 py-6 text-gray-500" colspan="5">No houses</td></tr>
  @endforelse
</x-table>

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
