@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Shop Rentals</h1>

  <a href="{{ route('admin.shops.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">
    Add Shop
  </a>
</div>

{{-- Filters --}}
<form method="get" class="bg-white rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-2 mb-3">
  <input class="rounded border-gray-300 w-full" type="month" name="month" value="{{ request('month') }}">
  <input class="rounded border-gray-300 w-full" type="text" name="shopNumber" placeholder="Shop No" value="{{ request('shopNumber') }}">

  <select name="status" class="rounded border-gray-300 w-full">
    <option value="">All Status</option>
    @foreach(['Pending','InProgress','Approved','Rejected'] as $s)
      <option @selected(request('status')===$s)>{{ $s }}</option>
    @endforeach
  </select>

  {{-- Any Method --}}
  <select name="method" class="rounded border-gray-300 w-full">
    <option value="">Any Method</option>
    @foreach (['cash','card','online'] as $m)
      <option value="{{ $m }}" @selected(request('method')===$m)>{{ ucfirst($m) }}</option>
    @endforeach
  </select>

  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Filter</button>
</form>

<div class="flex flex-wrap items-center justify-between gap-2 mb-2">
  <div class="text-sm text-gray-600">
    Tip: select items and use <span class="font-medium">Bulk Approve</span>.
  </div>

  {{-- BULK APPROVE --}}
  <form method="post" action="{{ route('admin.shop-rentals.approve', ['id' => 0]) }}" id="bulk-approve-form" class="flex items-center gap-2">
    @csrf
    <input type="hidden" name="bulk" value="1">
    <select name="paymentMethod" class="rounded border-gray-300" required>
      <option value="">Payment method…</option>
      <option value="cash">Cash</option>
      <option value="card">Card</option>
      <option value="online">Online</option>
    </select>
    <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Bulk Approve</button>
  </form>
</div>

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

      <div class="mt-3 flex items-center justify-between gap-3">
        <label class="inline-flex items-center gap-2">
          <input form="bulk-approve-form"
                 type="checkbox"
                 name="ids[]"
                 value="{{ $r->id }}"
                 class="rounded border-gray-300"
                 @if($r->status === 'Approved') disabled @endif>
          <span class="text-sm text-gray-600">Select</span>
        </label>

        <div class="flex items-center gap-3">
          {{-- Approve: CASH modal if method is empty; else one-click using existing method --}}
          @if($r->status === 'Approved')
            <button class="px-2 py-1 text-green-700 opacity-40 cursor-not-allowed" disabled>Approve</button>
          @else
            @if(empty($r->paymentMethod))
              <button type="button" class="px-2 py-1 text-green-700" x-data @click="$dispatch('open-modal','approve-{{ $r->id }}')">
                Approve
              </button>
            @else
              <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">
                @csrf
                <input type="hidden" name="paymentMethod" value="{{ $r->paymentMethod }}">
                {{-- If method already cash, default to full --}}
                @if($r->paymentMethod === 'cash' && (float)$r->paidAmount <= 0)
                  <input type="hidden" name="paidAmount" value="{{ $r->billAmount }}">
                @endif
                <button class="px-2 py-1 text-green-700">Approve</button>
              </form>
            @endif
          @endif

          {{-- Reject --}}
          @if($r->status !== 'Approved')
            <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">
              @csrf
              <button class="px-2 py-1 text-red-700">Reject</button>
            </form>
          @endif
        </div>
      </div>
    </div>

    {{-- CASH-ONLY Approve modal (mobile) --}}
    @if($r->status !== 'Approved' && empty($r->paymentMethod))
      <x-modal :name="'approve-'.$r->id" :title="'Approve Rental #'.$r->id">
        <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="space-y-3">
          @csrf
          <input type="hidden" name="paymentMethod" value="cash">
          <p class="text-sm text-gray-600">Recording a <span class="font-medium">cash</span> payment.</p>
          <label class="block">
            <span class="text-sm">Paid Amount</span>
            <input type="number" name="paidAmount" step="0.01" min="0"
                   value="{{ old('paidAmount', $r->paidAmount > 0 ? $r->paidAmount : $r->billAmount) }}"
                   class="mt-1 w-full rounded border-gray-300" required>
          </label>
          <div class="text-right">
            <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Approve</button>
          </div>
        </form>
      </x-modal>
    @endif
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No data</div>
  @endforelse
</div>

{{-- ===== Tablet / Desktop: table ===== --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table class="table-fixed w-full">
    <x-slot:head>
      <th class="px-3 py-2 w-10 text-center">
        <input
          type="checkbox"
          x-data
          @change="$el.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c=>{ if(!c.disabled) c.checked=$el.checked })"
          class="align-middle"
        >
      </th>
      <th class="px-3 py-2 text-left  w-32">Shop No</th>
      <th class="px-3 py-2 text-left  w-44">Merchant</th>
      <th class="px-3 py-2 text-left  w-28">Month</th>
      <th class="px-3 py-2 text-right w-28">Bill</th>
      <th class="px-3 py-2 text-right w-28">Paid</th>
      <th class="px-3 py-2 text-center w-28 hidden lg:table-cell">Method</th>
      <th class="px-3 py-2 text-center w-28 hidden lg:table-cell">Receipt</th>
      <th class="px-3 py-2 text-center w-28">Status</th>
      <th class="px-3 py-2 w-32"></th>
    </x-slot:head>

    @forelse($rows ?? [] as $r)
      <tr class="hover:bg-gray-50 align-middle">
        <td class="px-3 py-2 w-10 text-center">
          <input
            form="bulk-approve-form"
            type="checkbox"
            name="ids[]"
            value="{{ $r->id }}"
            class="rounded"
            @if($r->status === 'Approved') disabled @endif
          >
        </td>

        <td class="px-3 py-2 w-32">{{ $r->shopNumber }}</td>
        <td class="px-3 py-2 w-44">{{ $r->merchant_name ?? '-' }}</td>
        <td class="px-3 py-2 w-28">{{ $r->month }}</td>

        <td class="px-3 py-2 text-right w-28">{{ number_format($r->billAmount,2) }}</td>
        <td class="px-3 py-2 text-right w-28">{{ number_format($r->paidAmount,2) }}</td>

        <td class="px-3 py-2 text-center w-28 hidden lg:table-cell uppercase">
          {{ $r->paymentMethod ?: '-' }}
        </td>

        <td class="px-3 py-2 text-center w-28 hidden lg:table-cell">
          @if($r->recipt)
            <a target="_blank" class="text-blue-600 hover:underline" href="{{ asset('storage/'.$r->recipt) }}">Open</a>
          @else
            <span class="text-gray-400">-</span>
          @endif
        </td>

        <td class="px-3 py-2 text-center w-28">
          <x-badge :status="$r->status"/>
        </td>

        <td class="px-3 py-2 w-32 text-right whitespace-nowrap">
          {{-- Approve: CASH modal if method empty; else one-click --}}
          @if($r->status === 'Approved')
            <button class="text-green-700 opacity-50 cursor-not-allowed" disabled>Approve</button>
          @else
            @if(empty($r->paymentMethod))
              <button type="button" class="text-green-700" x-data @click="$dispatch('open-modal','approve-{{ $r->id }}')">
                Approve
              </button>
            @else
              <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">
                @csrf
                <input type="hidden" name="paymentMethod" value="{{ $r->paymentMethod }}">
                @if($r->paymentMethod === 'cash' && (float)$r->paidAmount <= 0)
                  <input type="hidden" name="paidAmount" value="{{ $r->billAmount }}">
                @endif
                <button class="text-green-700">Approve</button>
              </form>
            @endif
          @endif

          @if($r->status !== 'Approved')
            <span class="mx-2 text-gray-300">|</span>
            <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">
              @csrf
              <button class="text-red-700">Reject</button>
            </form>
          @endif
        </td>
      </tr>

      {{-- CASH-ONLY Approve modal (desktop) --}}
      @if($r->status !== 'Approved' && empty($r->paymentMethod))
        <x-modal :name="'approve-'.$r->id" :title="'Approve Rental #'.$r->id">
          <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="paymentMethod" value="cash">
            <p class="text-sm text-gray-600">Recording a <span class="font-medium">cash</span> payment.</p>
            <label class="block">
              <span class="text-sm">Paid Amount</span>
              <input type="number" name="paidAmount" step="0.01" min="0"
                     value="{{ old('paidAmount', $r->paidAmount > 0 ? $r->paidAmount : $r->billAmount) }}"
                     class="mt-1 w-full rounded border-gray-300" required>
            </label>
            <div class="text-right">
              <button class="px-3 py-2 bg-green-600 text-white rounded-lg">Approve</button>
            </div>
          </form>
        </x-modal>
      @endif
    @empty
      <tr><td class="px-3 py-6 text-gray-500 text-center" colspan="10">No data</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
