@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">House Charges</h1>

{{-- Filters: stack on mobile, 5 cols on md+ --}}
<form method="get" class="bg-white rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 mb-3">
  <input class="rounded border-gray-300 w-full" type="month" name="month" value="{{ request('month') }}">
  <select name="status" class="rounded border-gray-300 w-full">
    <option value="">All Status</option>
    @foreach(['Pending','Approved','Rejected'] as $s)
      <option @selected(request('status')===$s)>{{ $s }}</option>
    @endforeach
  </select>
  <input class="rounded border-gray-300 w-full" type="text" name="houseNo" placeholder="House No" value="{{ request('houseNo') }}">
  <select name="method" class="rounded border-gray-300 w-full">
    <option value="">Any Method</option>
    @foreach(['cash','card','online'] as $m)
      <option @selected(request('method')===$m) value="{{ $m }}">{{ ucfirst($m) }}</option>
    @endforeach
  </select>
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Filter</button>
</form>

<div class="flex flex-wrap items-center justify-between gap-2 mb-2">
  <div class="text-sm text-gray-600">
    Tip: select items and use <span class="font-medium">Bulk Approve</span>.
  </div>
  <form method="post" action="{{ route('admin.house-bills.approve',['id'=>0]) }}" id="bulk-approve-form">
    @csrf
    <input type="hidden" name="bulk" value="1">
    <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Bulk Approve</button>
  </form>
</div>

@php
  $unitPrice = (float)\App\Models\Setting::get('water_unit_price', 0);
  $sewerage  = (float)\App\Models\Setting::get('sewerage_charge', 0);
@endphp

{{-- ========= Mobile: Cards ========= --}}
<div class="sm:hidden space-y-3">
  @forelse($bills ?? [] as $b)
    @php $usage = max(0, ($b->readingUnit - $b->openingReadingUnit)); @endphp
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-sm text-gray-600">House</div>
          <div class="font-medium">{{ $b->houseNo }}</div>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-600">Month</div>
          <div class="font-medium">{{ $b->month }}</div>
        </div>
      </div>

      <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
        <div>
          <div class="text-gray-500">Reading</div>
          <div>{{ $b->openingReadingUnit }} → {{ $b->readingUnit }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Usage</div>
          <div class="font-medium">{{ $usage }}</div>
        </div>
        <div>
          <div class="text-gray-500">Sewerage</div>
          <div>{{ number_format($sewerage,2) }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Unit Price</div>
          <div>{{ number_format($unitPrice,2) }}</div>
        </div>
        <div>
          <div class="text-gray-500">Method</div>
          <div class="uppercase">{{ $b->paymentMethod ?: '-' }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Status</div>
          <div><x-badge :status="$b->status"/></div>
        </div>
      </div>

      <div class="mt-3 grid grid-cols-2 gap-2 items-end">
        <div>
          <div class="text-xs text-gray-500">Bill</div>
          <div class="text-base font-semibold">{{ number_format($b->billAmount,2) }}</div>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-500">Paid</div>
          <div class="text-base font-semibold">{{ number_format($b->paidAmount,2) }}</div>
        </div>
      </div>

      <div class="mt-3 flex items-center justify-between gap-3">
        <label class="inline-flex items-center gap-2">
          <input form="bulk-approve-form" type="checkbox" name="ids[]" value="{{ $b->id }}" class="rounded border-gray-300">
          <span class="text-sm text-gray-600">Select</span>
        </label>

        <div class="flex items-center gap-3">
          @if($b->recipt)
            <a class="text-blue-600 hover:underline text-sm" target="_blank" href="{{ asset('storage/'.$b->recipt) }}">Receipt</a>
          @endif
          <button type="button" class="text-green-700 text-sm" x-data @click="$dispatch('open-approve-{{ $b->id }}')">Approve</button>
          <button type="button" class="text-red-700 text-sm" x-data @click="$dispatch('open-reject-{{ $b->id }}')">Reject</button>
        </div>
      </div>
    </div>

    {{-- Approve modal --}}
    <x-modal :id="'approve-'.$b->id" :title="'Approve Bill #'.$b->id">
      <form method="post" action="{{ route('admin.house-bills.approve',$b->id) }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <label class="block">
          <span class="text-sm">Paid Amount</span>
          <input name="paidAmount" type="number" step="0.01" min="0" value="{{ $b->billAmount }}" class="mt-1 w-full rounded border-gray-300">
        </label>
        <label class="block">
          <span class="text-sm">Payment Method</span>
          <select name="paymentMethod" class="mt-1 w-full rounded border-gray-300" required>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="online">Online</option>
          </select>
        </label>
        <x-upload name="recipt"/>
        <div class="text-right">
          <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Approve</button>
        </div>
      </form>
    </x-modal>

    {{-- Reject modal --}}
    <x-modal :id="'reject-'.$b->id" :title="'Reject Bill #'.$b->id">
      <form method="post" action="{{ route('admin.house-bills.reject',$b->id) }}" class="space-y-3">
        @csrf
        <label class="block">
          <span class="text-sm">Reason</span>
          <textarea name="reason" class="mt-1 w-full rounded border-gray-300" required></textarea>
        </label>
        <div class="text-right">
          <button class="px-3 py-2 bg-red-600 text-white rounded-lg">Reject</button>
        </div>
      </form>
    </x-modal>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No data</div>
  @endforelse
</div>

{{-- ========= Tablet / Desktop: Table ========= --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2">
        <input type="checkbox"
               x-data
               @change="$el.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c=>c.checked=$el.checked)">
      </th>
      <th class="px-3 py-2 text-left">House No</th>
      <th class="px-3 py-2 text-left">Month</th>
      <th class="px-3 py-2 text-left hidden md:table-cell">Reading</th>
      <th class="px-3 py-2 text-right hidden md:table-cell">Usage</th>
      <th class="px-3 py-2 text-right hidden lg:table-cell">Sewerage</th>
      <th class="px-3 py-2 text-right hidden lg:table-cell">Unit Price</th>
      <th class="px-3 py-2 text-right">Bill</th>
      <th class="px-3 py-2 text-right hidden sm:table-cell">Paid</th>
      <th class="px-3 py-2 hidden lg:table-cell">Method</th>
      <th class="px-3 py-2 hidden lg:table-cell">Receipt</th>
      <th class="px-3 py-2">Status</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>

    @forelse($bills ?? [] as $b)
      @php $usage = max(0, ($b->readingUnit - $b->openingReadingUnit)); @endphp
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">
          <input form="bulk-approve-form" type="checkbox" name="ids[]" value="{{ $b->id }}">
        </td>
        <td class="px-3 py-2">{{ $b->houseNo }}</td>
        <td class="px-3 py-2">{{ $b->month }}</td>
        <td class="px-3 py-2 hidden md:table-cell">{{ $b->openingReadingUnit }} → {{ $b->readingUnit }}</td>
        <td class="px-3 py-2 text-right hidden md:table-cell">{{ $usage }}</td>
        <td class="px-3 py-2 text-right hidden lg:table-cell">{{ number_format($sewerage,2) }}</td>
        <td class="px-3 py-2 text-right hidden lg:table-cell">{{ number_format($unitPrice,2) }}</td>
        <td class="px-3 py-2 text-right">{{ number_format($b->billAmount,2) }}</td>
        <td class="px-3 py-2 text-right hidden sm:table-cell">{{ number_format($b->paidAmount,2) }}</td>
        <td class="px-3 py-2 hidden lg:table-cell uppercase">{{ $b->paymentMethod }}</td>
        <td class="px-3 py-2 hidden lg:table-cell">
          @if($b->recipt)
            <a class="text-blue-600 hover:underline" target="_blank" href="{{ asset('storage/'.$b->recipt) }}">Open</a>
          @endif
        </td>
        <td class="px-3 py-2"><x-badge :status="$b->status"/></td>
        <td class="px-3 py-2 text-right whitespace-nowrap">
          <button type="button" class="text-green-700" x-data @click="$dispatch('open-approve-{{ $b->id }}')">Approve</button>
          <span class="mx-2 text-gray-300">|</span>
          <button type="button" class="text-red-700" x-data @click="$dispatch('open-reject-{{ $b->id }}')">Reject</button>
        </td>
      </tr>

      {{-- Approve modal --}}
      <x-modal :id="'approve-'.$b->id" :title="'Approve Bill #'.$b->id">
        <form method="post" action="{{ route('admin.house-bills.approve',$b->id) }}" enctype="multipart/form-data" class="space-y-3">
          @csrf
          <label class="block">
            <span class="text-sm">Paid Amount</span>
            <input name="paidAmount" type="number" step="0.01" min="0" value="{{ $b->billAmount }}" class="mt-1 w-full rounded border-gray-300">
          </label>
          <label class="block">
            <span class="text-sm">Payment Method</span>
            <select name="paymentMethod" class="mt-1 w-full rounded border-gray-300" required>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="online">Online</option>
            </select>
          </label>
          <x-upload name="recipt"/>
          <div class="text-right">
            <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Approve</button>
          </div>
        </form>
      </x-modal>

      {{-- Reject modal --}}
      <x-modal :id="'reject-'.$b->id" :title="'Reject Bill #'.$b->id">
        <form method="post" action="{{ route('admin.house-bills.reject',$b->id) }}" class="space-y-3">
          @csrf
          <label class="block">
            <span class="text-sm">Reason</span>
            <textarea name="reason" class="mt-1 w-full rounded border-gray-300" required></textarea>
          </label>
          <div class="text-right">
            <button class="px-3 py-2 bg-red-600 text-white rounded-lg">Reject</button>
          </div>
        </form>
      </x-modal>
    @empty
      <tr><td class="px-3 py-6 text-gray-500" colspan="13">No data</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($bills)) 
  <div class="mt-3">{{ $bills->links() }}</div>
@endif
@endsection
