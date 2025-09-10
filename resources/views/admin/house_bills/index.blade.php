@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">House Charges</h1>

<form method="get" class="bg-white rounded-lg p-3 grid md:grid-cols-5 gap-2 mb-3">
  <input class="rounded border-gray-300" type="month" name="month" value="{{ request('month') }}">
  <select name="status" class="rounded border-gray-300">
    <option value="">All Status</option>
    @foreach(['Pending','Approved','Rejected'] as $s)
      <option @selected(request('status')===$s)>{{ $s }}</option>
    @endforeach
  </select>
  <input class="rounded border-gray-300" type="text" name="houseNo" placeholder="House No" value="{{ request('houseNo') }}">
  <select name="method" class="rounded border-gray-300">
    <option value="">Any Method</option>
    @foreach(['cash','card','online'] as $m)
      <option @selected(request('method')===$m) value="{{ $m }}">{{ ucfirst($m) }}</option>
    @endforeach
  </select>
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Filter</button>
</form>

<div class="flex items-center justify-between mb-2">
  <div class="text-sm text-gray-600">Tip: select rows and use the Bulk Approve button.</div>
  <form method="post" action="{{ route('admin.house-bills.approve',['id'=>0]) }}" id="bulk-approve-form">
    @csrf
    <input type="hidden" name="bulk" value="1">
    <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Bulk Approve</button>
  </form>
</div>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2"><input type="checkbox" x-data @change="$el.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c=>c.checked=$el.checked)"></th>
    <th class="px-3 py-2 text-left">House No</th>
    <th class="px-3 py-2 text-left">Month</th>
    <th class="px-3 py-2 text-left">Reading</th>
    <th class="px-3 py-2 text-right">Usage</th>
    <th class="px-3 py-2 text-right">Sewerage</th>
    <th class="px-3 py-2 text-right">Unit Price</th>
    <th class="px-3 py-2 text-right">Bill</th>
    <th class="px-3 py-2 text-right">Paid</th>
    <th class="px-3 py-2">Method</th>
    <th class="px-3 py-2">Receipt</th>
    <th class="px-3 py-2">Status</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>

  @php
    $unitPrice = (float)\App\Models\Setting::get('water_unit_price', 0);
    $sewerage  = (float)\App\Models\Setting::get('sewerage_charge', 0);
  @endphp

  @forelse($bills ?? [] as $b)
    @php $usage = max(0, ($b->readingUnit - $b->openingReadingUnit)); @endphp
    <tr>
      <td class="px-3 py-2">
        <input form="bulk-approve-form" type="checkbox" name="ids[]" value="{{ $b->id }}">
      </td>
      <td class="px-3 py-2">{{ $b->houseNo }}</td>
      <td class="px-3 py-2">{{ $b->month }}</td>
      <td class="px-3 py-2">{{ $b->openingReadingUnit }} → {{ $b->readingUnit }}</td>
      <td class="px-3 py-2 text-right">{{ $usage }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($sewerage,2) }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($unitPrice,2) }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($b->billAmount,2) }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($b->paidAmount,2) }}</td>
      <td class="px-3 py-2">{{ $b->paymentMethod }}</td>
      <td class="px-3 py-2">
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

@if(isset($bills)) <div class="mt-3">{{ $bills->links() }}</div> @endif
@endsection
