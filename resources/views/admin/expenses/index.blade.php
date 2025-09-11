@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Other Expenses</h1>
  <a href="{{ route('admin.expenses.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add Expense</a>
</div>

@php
  $items = $rows ?? collect();
  $total = collect($items)->sum('amount');
@endphp

{{-- Mobile: card list --}}
<div class="sm:hidden space-y-3">
  @forelse($items as $e)
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">
          {{ \Illuminate\Support\Carbon::parse($e->timestamp ?? $e->date ?? now())->toDateString() }}
        </span>
        <span class="font-medium">{{ $e->name }}</span>
      </div>

      @if(!empty($e->note))
        <div class="mt-1 text-sm text-gray-700 break-words">
          {{ $e->note }}
        </div>
      @endif

      <div class="mt-2 flex items-center justify-between">
        <span class="text-sm text-gray-500">Amount</span>
        <span class="font-semibold">{{ number_format($e->amount,2) }}</span>
      </div>

      <div class="mt-3 flex items-center justify-end gap-3">
        <a class="text-blue-600 hover:underline" href="{{ route('admin.expenses.edit',$e->id) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.expenses.destroy',$e->id) }}" onsubmit="return confirm('Delete?')">
          @csrf @method('DELETE')
          <button class="text-red-600">Delete</button>
        </form>
      </div>
    </div>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No expenses</div>
  @endforelse

  @if(count($items))
    <div class="rounded-lg border bg-gray-50 p-3 text-right font-medium">
      Month Total: {{ number_format($total,2) }}
    </div>
  @endif
</div>

{{-- Tablet / Desktop: original table --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">Date</th>
      <th class="px-3 py-2 text-left">Name</th>
      <th class="px-3 py-2 text-right">Amount</th>
      <th class="px-3 py-2 text-left">Note</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>

    @forelse($items as $e)
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($e->timestamp ?? $e->date ?? now())->toDateString() }}</td>
        <td class="px-3 py-2">{{ $e->name }}</td>
        <td class="px-3 py-2 text-right">{{ number_format($e->amount,2) }}</td>
        <td class="px-3 py-2">{{ $e->note }}</td>
        <td class="px-3 py-2 text-right">
          <a class="text-blue-600 hover:underline" href="{{ route('admin.expenses.edit',$e->id) }}">Edit</a>
          <form class="inline" method="post" action="{{ route('admin.expenses.destroy',$e->id) }}" onsubmit="return confirm('Delete?')">
            @csrf @method('DELETE')
            <button class="text-red-600 ml-2">Delete</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td class="px-3 py-6 text-gray-500" colspan="5">No expenses</td></tr>
    @endforelse

    <tr class="bg-gray-50 font-medium">
      <td class="px-3 py-2" colspan="2">Month Total</td>
      <td class="px-3 py-2 text-right">{{ number_format($total,2) }}</td>
      <td class="px-3 py-2" colspan="2"></td>
    </tr>
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
