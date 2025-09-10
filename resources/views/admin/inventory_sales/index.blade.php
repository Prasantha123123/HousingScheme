@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-3">
  <h1 class="text-xl font-semibold">Inventory Sales</h1>
  <a href="{{ route('admin.inventory-sales.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add Sale</a>
</div>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">Date</th>
    <th class="px-3 py-2 text-left">Item</th>
    <th class="px-3 py-2 text-right">Qty</th>
    <th class="px-3 py-2 text-right">Unit Price</th>
    <th class="px-3 py-2 text-right">Total</th>
    <th class="px-3 py-2 text-left">Note</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>

  @forelse($rows ?? [] as $row)
    <tr>
      <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($row->date)->toDateString() }}</td>
      <td class="px-3 py-2">{{ $row->item }}</td>
      <td class="px-3 py-2 text-right">{{ $row->qty }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($row->unit_price,2) }}</td>
      <td class="px-3 py-2 text-right font-medium">{{ number_format($row->total,2) }}</td>
      <td class="px-3 py-2">{{ $row->note }}</td>
      <td class="px-3 py-2 text-right">
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
</x-table>

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
