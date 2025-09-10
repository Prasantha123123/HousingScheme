@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-3">
  <h1 class="text-xl font-semibold">Contracts</h1>
  <a href="{{ route('admin.contracts.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add Contract</a>
</div>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">EmployeeId</th>
    <th class="px-3 py-2 text-left">Type</th>
    <th class="px-3 py-2 text-right">Wage Amount</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>
  @forelse($rows ?? [] as $c)
  <tr>
    <td class="px-3 py-2">{{ $c->EmployeeId }}</td>
    <td class="px-3 py-2">{{ $c->contractType }}</td>
    <td class="px-3 py-2 text-right">{{ number_format($c->waheAmount,2) }}</td>
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
</x-table>

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
