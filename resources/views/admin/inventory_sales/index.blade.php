@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Inventory Sales</h1>
  <a href="{{ route('admin.inventory-sales.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Add Sale</a>
</div>

@php
  $items = $rows ?? collect();
  $total = collect($items)->sum('total');
@endphp

{{-- ===== Mobile: cards ===== --}}
<div class="sm:hidden space-y-3">
  @forelse($items as $row)
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between gap-2">
        <div>
          <div class="text-xs text-gray-500">Date</div>
          <div class="font-medium">
            {{ \Illuminate\Support\Carbon::parse($row->date)->toDateString() }}
          </div>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-500">Total</div>
          <div class="font-semibold">{{ number_format($row->total,2) }}</div>
        </div>
      </div>

      <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
        <div>
          <div class="text-gray-500">Item</div>
          <div class="font-medium">{{ $row->item }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Qty × Unit</div>
          <div class="font-medium">{{ $row->qty }} × {{ number_format($row->unit_price,2) }}</div>
        </div>
      </div>

      @if(!empty($row->note))
        <div class="mt-2 text-sm text-gray-700 break-words">
          {{ $row->note }}
        </div>
      @endif

      <div class="mt-3 flex items-center justify-end gap-3">
        <a class="text-blue-600 hover:underline" href="{{ route('admin.inventory-sales.edit',$row->id) }}">Edit</a>
        <form method="post" action="{{ route('admin.inventory-sales.destroy',$row->id) }}" onsubmit="return confirm('Delete this record?')">
          @csrf @method('DELETE')
          <button class="text-red-600">Delete</button>
        </form>
      </div>
    </div>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No sales</div>
  @endforelse

  @if(count($items))
    <div class="rounded-lg border bg-gray-50 p-3 text-right font-medium">
      Month Total: {{ number_format($total,2) }}
    </div>
  @endif
</div>

{{-- ===== Tablet / Desktop: table ===== --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">Date</th>
      <th class="px-3 py-2 text-left">Item</th>
      <th class="px-3 py-2 text-right">Qty</th>
      <th class="px-3 py-2 text-right">Unit Price</th>
      <th class="px-3 py-2 text-right">Total</th>
      <th class="px-3 py-2 text-left hidden lg:table-cell">Note</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>

    @forelse($items as $row)
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($row->date)->toDateString() }}</td>
        <td class="px-3 py-2">{{ $row->item }}</td>
        <td class="px-3 py-2 text-right">{{ $row->qty }}</td>
        <td class="px-3 py-2 text-right">{{ number_format($row->unit_price,2) }}</td>
        <td class="px-3 py-2 text-right font-medium">{{ number_format($row->total,2) }}</td>
        <td class="px-3 py-2 hidden lg:table-cell">{{ $row->note }}</td>
        <td class="px-3 py-2 text-right whitespace-nowrap">
          <a class="text-blue-600 hover:underline" href="{{ route('admin.inventory-sales.edit',$row->id) }}">Edit</a>
          <form method="post" action="{{ route('admin.inventory-sales.destroy',$row->id) }}" class="inline" onsubmit="return confirm('Delete this record?')">
            @csrf @method('DELETE')
            <button class="text-red-600 ml-2">Delete</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td class="px-3 py-6 text-gray-500" colspan="7">No sales</td></tr>
    @endforelse

    @if(count($items))
      <tr class="bg-gray-50 font-medium">
        <td class="px-3 py-2" colspan="4">Month Total</td>
        <td class="px-3 py-2 text-right">{{ number_format($total,2) }}</td>
        <td class="px-3 py-2 hidden lg:table-cell" colspan="2"></td>
      </tr>
    @endif
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
