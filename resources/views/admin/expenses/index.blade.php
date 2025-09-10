@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-3">
  <h1 class="text-xl font-semibold">Other Expenses</h1>
  <a href="{{ route('admin.expenses.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add Expense</a>
</div>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">Date</th>
    <th class="px-3 py-2 text-left">Name</th>
    <th class="px-3 py-2 text-right">Amount</th>
    <th class="px-3 py-2 text-left">Note</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>

  @php $total = 0; @endphp
  @forelse($rows ?? [] as $e)
    @php $total += $e->amount; @endphp
    <tr>
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

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
