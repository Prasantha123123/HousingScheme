@extends('layouts.app')

@section('content')
{{-- Header: title + actions (Add House, Generate Bills) --}}
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Houses</h1>

  <div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.houses.create') }}"
       class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">
      Add House
    </a>

    <form method="post" action="{{ route('admin.house-bills.generate') }}"
          class="flex flex-wrap items-center gap-2">
      @csrf
      <input type="month"
             name="month"
             value="{{ request('month', now()->format('Y-m')) }}"
             class="rounded border-gray-300 w-full sm:w-auto">
      <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">
        Generate Current Month Bills
      </button>
    </form>
  </div>
</div>

{{-- Search --}}
<form method="get" class="mb-3 flex flex-wrap gap-2">
  <input type="text"
         name="q"
         value="{{ request('q') }}"
         placeholder="Search house no / owner"
         class="rounded border-gray-300 w-full sm:w-80">
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Search</button>
</form>

{{-- ===== Mobile: Cards ===== --}}
<div class="sm:hidden space-y-3">
  @forelse($rows ?? [] as $row)
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between">
        <div>
          <div class="text-xs text-gray-500">House No</div>
          <div class="font-medium">{{ $row->houseNo }}</div>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-500">Status</div>
          <div><x-badge :status="$row->latest_status ?? 'Pending'"/></div>
        </div>
      </div>

      <div class="mt-2">
        <div class="text-xs text-gray-500">Owner</div>
        <div class="text-sm">{{ $row->owner_name ?? '-' }}</div>
      </div>

      <div class="mt-2 flex items-center justify-between">
        <div class="text-xs text-gray-500">Latest Bill</div>
        <div class="text-sm font-medium">
          {{ $row->latest_bill_month ?? '-' }}
          @if(isset($row->latest_bill_amount))
            · {{ number_format($row->latest_bill_amount,2) }}
          @endif
        </div>
      </div>

      <div class="mt-3 flex flex-wrap items-center justify-end gap-3">
        <a class="text-blue-600 hover:underline"
           href="{{ route('admin.houses.show',$row->houseNo) }}">View</a>
        <a class="text-indigo-600 hover:underline"
           href="{{ route('admin.houses.edit',$row->houseNo) }}">Edit</a>
        <form method="post"
              action="{{ route('admin.houses.destroy',$row->houseNo) }}"
              onsubmit="return confirm('Delete this house?');">
          @csrf @method('DELETE')
          <button class="text-red-600">Delete</button>
        </form>
      </div>
    </div>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No houses</div>
  @endforelse
</div>

{{-- ===== Tablet / Desktop: Table ===== --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">House No</th>
      <th class="px-3 py-2 text-left">Owner</th>
      <th class="px-3 py-2 text-left hidden md:table-cell">Latest Bill</th>
      <th class="px-3 py-2 text-left">Status</th>
      <th class="px-3 py-2 text-right">Actions</th>
    </x-slot:head>

    @forelse($rows ?? [] as $row)
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ $row->houseNo }}</td>
        <td class="px-3 py-2">{{ $row->owner_name ?? '-' }}</td>
        <td class="px-3 py-2 hidden md:table-cell">
          {{ $row->latest_bill_month ?? '-' }}
          @if(isset($row->latest_bill_amount)) · {{ number_format($row->latest_bill_amount,2) }} @endif
        </td>
        <td class="px-3 py-2"><x-badge :status="$row->latest_status ?? 'Pending'"/></td>
        <td class="px-3 py-2 text-right whitespace-nowrap">
          <a class="text-blue-600 hover:underline mr-3"
             href="{{ route('admin.houses.show',$row->houseNo) }}">View</a>
          <a class="text-indigo-600 hover:underline mr-3"
             href="{{ route('admin.houses.edit',$row->houseNo) }}">Edit</a>
          <form method="post" action="{{ route('admin.houses.destroy',$row->houseNo) }}"
                class="inline" onsubmit="return confirm('Delete this house?');">
            @csrf @method('DELETE')
            <button class="text-red-600">Delete</button>
          </form>
        </td>
      </tr>
    @empty
      <tr>
        <td class="px-3 py-6 text-gray-500" colspan="5">No houses</td>
      </tr>
    @endforelse
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
