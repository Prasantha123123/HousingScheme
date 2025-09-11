@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Shop Rentals</h1>

  {{-- Optional quick link to add a new shop --}}
  <a href="{{ route('admin.shops.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">
    Add Shop
  </a>
</div>

{{-- Filters: stack nicely on small screens --}}
<form method="get" class="bg-white rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 mb-3">
  <input class="rounded border-gray-300 w-full" type="month" name="month" value="{{ request('month') }}">
  <input class="rounded border-gray-300 w-full" type="text" name="shopNumber" placeholder="Shop No" value="{{ request('shopNumber') }}">
  <select name="status" class="rounded border-gray-300 w-full">
    <option value="">All Status</option>
    @foreach(['Pending','Approved','Rejected'] as $s)
      <option @selected(request('status')===$s)>{{ $s }}</option>
    @endforeach
  </select>
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Filter</button>
</form>

{{-- ===== Mobile: cards ===== --}}
<div class="sm:hidden space-y-3">
  @forelse($rows ?? [] as $r)
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between gap-2">
        <div>
          <div class="text-xs text-gray-500">Shop No</div>
          <div class="font-medium">{{ $r->shopNumber }}</div>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-500">Month</div>
          <div class="font-medium">{{ $r->month }}</div>
        </div>
      </div>

      <div class="mt-2">
        <div class="text-xs text-gray-500">Merchant</div>
        <div class="text-sm">{{ $r->merchant_name ?? '-' }}</div>
      </div>

      <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
        <div>
          <div class="text-gray-500">Bill</div>
          <div class="font-medium">{{ number_format($r->billAmount,2) }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Paid</div>
          <div class="font-medium">{{ number_format($r->paidAmount,2) }}</div>
        </div>
        <div>
          <div class="text-gray-500">Method</div>
          <div class="uppercase">{{ $r->paymentMethod ?: '-' }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Status</div>
          <x-badge :status="$r->status"/>
        </div>
      </div>

      <div class="mt-2">
        @if($r->recipt)
          <a target="_blank" class="text-blue-600 hover:underline text-sm" href="{{ asset('storage/'.$r->recipt) }}">Open receipt</a>
        @endif
      </div>

      <div class="mt-3 flex items-center justify-end gap-3">
        {{-- Approve (one click) --}}
        <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">
          @csrf
          <button
            class="px-2 py-1 text-green-700"
            @disabled($r->status === 'Approved')
            @class([
              'opacity-50 cursor-not-allowed' => $r->status === 'Approved'
            ])
          >
            Approve
          </button>
        </form>

        {{-- Reject (one click) --}}
        @if($r->status !== 'Approved')
          <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">
            @csrf
            <button class="px-2 py-1 text-red-700">Reject</button>
          </form>
        @endif
      </div>
    </div>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No data</div>
  @endforelse
</div>

{{-- ===== Tablet / Desktop: table ===== --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">Shop No</th>
      <th class="px-3 py-2 text-left">Merchant</th>
      <th class="px-3 py-2 text-left">Month</th>
      <th class="px-3 py-2 text-right">Bill</th>
      <th class="px-3 py-2 text-right hidden md:table-cell">Paid</th>
      <th class="px-3 py-2 hidden lg:table-cell">Method</th>
      <th class="px-3 py-2 hidden lg:table-cell">Receipt</th>
      <th class="px-3 py-2">Status</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>

    @forelse($rows ?? [] as $r)
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ $r->shopNumber }}</td>
        <td class="px-3 py-2">{{ $r->merchant_name ?? '-' }}</td>
        <td class="px-3 py-2">{{ $r->month }}</td>
        <td class="px-3 py-2 text-right">{{ number_format($r->billAmount,2) }}</td>
        <td class="px-3 py-2 text-right hidden md:table-cell">{{ number_format($r->paidAmount,2) }}</td>
        <td class="px-3 py-2 hidden lg:table-cell uppercase">{{ $r->paymentMethod ?: '-' }}</td>
        <td class="px-3 py-2 hidden lg:table-cell">
          @if($r->recipt)
            <a target="_blank" class="text-blue-600 hover:underline" href="{{ asset('storage/'.$r->recipt) }}">Open</a>
          @endif
        </td>
        <td class="px-3 py-2"><x-badge :status="$r->status"/></td>
        <td class="px-3 py-2 text-right whitespace-nowrap">
          {{-- Approve --}}
          <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">
            @csrf
            <button
              class="text-green-700"
              @disabled($r->status === 'Approved')
              @class([
                'opacity-50 cursor-not-allowed' => $r->status === 'Approved'
              ])
            >
              Approve
            </button>
          </form>

          @if($r->status !== 'Approved')
            <span class="mx-2 text-gray-300">|</span>
            {{-- Reject --}}
            <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">
              @csrf
              <button class="text-red-700">Reject</button>
            </form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td class="px-3 py-6 text-gray-500" colspan="9">No data</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
