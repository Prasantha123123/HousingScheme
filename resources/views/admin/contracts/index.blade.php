@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center gap-2 justify-between mb-3">
  <h1 class="text-xl font-semibold">Contracts</h1>
  <a href="{{ route('admin.contracts.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add Contract</a>
</div>

{{-- Mobile: cards --}}
<div class="sm:hidden space-y-3">
  @forelse($rows ?? [] as $c)
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="font-medium">Employee #{{ $c->EmployeeId }}</div>
        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
          {{ $c->contractType }}
        </span>
      </div>

      <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
        <dt class="text-gray-500">Wage Amount</dt>
        <dd class="text-right font-medium">₹ {{ number_format($c->waheAmount,2) }}</dd>
      </dl>

      <div class="mt-3 flex items-center justify-end gap-3">
        <a class="text-blue-600 hover:underline" href="{{ route('admin.contracts.edit',$c->id) }}">Edit</a>
        <form method="post" action="{{ route('admin.contracts.destroy',$c->id) }}" onsubmit="return confirm('Delete?')">
          @csrf @method('DELETE')
          <button class="text-red-600">Delete</button>
        </form>
      </div>
    </div>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No contracts</div>
  @endforelse
</div>

{{-- Desktop / Tablet: table --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <table class="min-w-full text-sm bg-white rounded-lg overflow-hidden border">
    <thead class="bg-gray-100 text-gray-700">
      <tr>
        <th class="px-3 py-2 text-left">EmployeeId</th>
        <th class="px-3 py-2 text-left">Type</th>
        <th class="px-3 py-2 text-right">Wage Amount</th>
        <th class="px-3 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($rows ?? [] as $c)
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2">{{ $c->EmployeeId }}</td>
          <td class="px-3 py-2">{{ $c->contractType }}</td>
          <td class="px-3 py-2 text-right">₹ {{ number_format($c->waheAmount,2) }}</td>
          <td class="px-3 py-2 text-right">
            <a class="text-blue-600 hover:underline" href="{{ route('admin.contracts.edit',$c->id) }}">Edit</a>
            <form method="post" action="{{ route('admin.contracts.destroy',$c->id) }}" class="inline" onsubmit="return confirm('Delete?')">
              @csrf @method('DELETE')
              <button class="text-red-600 ml-2">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="px-3 py-6 text-gray-500" colspan="4">No contracts</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if(isset($rows)) 
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
