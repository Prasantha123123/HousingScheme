{{-- resources/views/shop/rentals.blade.php --}}
@extends('layouts.app', ['title' => 'Shop Rentals'])

@section('content')
<style>[x-cloak]{display:none!important}</style>

{{-- Back to Dashboard --}}
<div class="mb-4">
    <a href="{{ route('shop.dashboard') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Dashboard
    </a>
</div>

<h1 class="text-xl font-semibold mb-3">My Shop Rentals - Shop {{ $shop->shopNumber }}</h1>

@if($rentals->count() > 0)
  @foreach($rentals as $r)
    <div class="bg-white rounded-lg p-4 mb-3 border">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="font-medium">Shop {{ $r->shopNumber }} · {{ $r->month }}</div>
        <x-badge :status="$r->status"/>
      </div>

      <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
        <div>Amount: <span class="font-semibold">{{ number_format($r->billAmount,2) }}</span></div>
        <div>Paid: <span>{{ number_format($r->paidAmount,2) }}</span></div>
        <div>
          @if($r->recipt)
            Receipt:
            <a target="_blank" href="{{ asset('storage/'.$r->recipt) }}" class="text-blue-600 hover:underline">Open</a>
          @endif
        </div>
      </div>

      @if($r->status !== 'Approved')
        {{-- Unified payment form (UI-only changes) --}}
        <form
          x-data="{ method: 'card' }"
          x-bind:action="method === 'card'
              ? '{{ route('shop.rentals.pay.card',   $r->id) }}'
              : '{{ route('shop.rentals.pay.transfer',$r->id) }}'"
          method="post" enctype="multipart/form-data"
          class="mt-3"
        >
          @csrf

          {{-- One-row grid: Method (2) | Amount (1) | Bank Ref (2) | Receipt (1) | Button (1) --}}
          <div class="max-w-6xl grid grid-cols-1 sm:grid-cols-7 gap-3 items-end" x-cloak>
            {{-- Payment Method --}}
            <label class="sm:col-span-2 block">
              <span class="text-sm text-gray-600">Payment Method</span>
              <select
                x-model="method"
                name="paymentMethod"
                class="mt-1 w-full rounded border-gray-300 h-10"
                required
              >
                <option value="card">Card</option>
                <option value="online">Bank Transfer</option>
              </select>
            </label>

            {{-- Amount --}}
            <label class="sm:col-span-1 block">
              <span class="text-sm text-gray-600">Amount</span>
              <input
                name="amount"
                type="number"
                step="0.01"
                min="0.01"
                class="mt-1 w-full rounded border-gray-300 h-10"
                value="{{ old('amount', number_format(max(0, $r->billAmount - $r->paidAmount), 2, '.', '')) }}"
                placeholder="{{ number_format(max(0, $r->billAmount - $r->paidAmount), 2) }}"
                required
              >
            </label>

            {{-- Bank Reference (only for bank transfer) --}}
            <label class="sm:col-span-2 block" x-show="method === 'online'">
              <span class="text-sm text-gray-600">Bank Reference</span>
              <input
                name="reference"
                class="mt-1 w-full rounded border-gray-300 h-10"
                placeholder="e.g. HSC-12345"
                :required="method === 'online'"
              >
            </label>

            {{-- Receipt upload (only for bank transfer) --}}
            <label class="sm:col-span-1 block" x-show="method === 'online'">
              <span class="text-sm text-gray-600">Receipt (PDF/JPG/PNG, ≤5MB)</span>
              <input
                type="file"
                name="recipt"
                accept="application/pdf,image/png,image/jpeg"
                class="mt-1 block w-full text-sm"
                :required="method === 'online'"
              >
            </label>

            {{-- Submit button --}}
            <div class="sm:col-span-1 flex sm:justify-end">
              <button
                class="h-10 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg w-full sm:w-auto"
                type="submit"
              >
                <span x-text="method === 'card' ? 'Pay Now' : 'Submit Transfer'"></span>
              </button>
            </div>
          </div>
        </form>
      @endif
    </div>
  @endforeach

  @if(isset($rentals))
    <div class="mt-3">{{ $rentals->links() }}</div>
  @endif
@else
  {{-- No rentals found --}}
  <div class="bg-white shadow overflow-hidden sm:rounded-md">
    <div class="px-4 py-5 sm:p-6">
      <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No rentals found</h3>
        <p class="mt-1 text-sm text-gray-500">
          Rental charges for shop {{ $shop->shopNumber }} will appear here when generated by admin.
        </p>
      </div>
    </div>
  </div>
@endif
@endsection