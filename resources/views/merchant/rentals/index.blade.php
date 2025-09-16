@extends('layouts.app')
@section('content')
<style>[x-cloak]{display:none!important}</style>

<h1 class="text-xl font-semibold mb-3">My Shop Rentals</h1>

@forelse($rentals ?? [] as $r)
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
            ? '{{ route('merchant.rentals.pay.card',   $r->id) }}'
            : '{{ route('merchant.rentals.pay.transfer',$r->id) }}'"
        method="post" enctype="multipart/form-data"
        class="mt-3"
      >
        @csrf

        {{-- One-row grid: Method (2) | Bank Ref (2) | Receipt (1) | Button (1) --}}
        <div class="max-w-5xl grid grid-cols-1 sm:grid-cols-6 gap-3 items-end" x-cloak>
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

          {{-- Bank Reference (only for bank transfer) --}}
          <label class="sm:col-span-2 block" x-show="method === 'online'">
            <span class="text-sm text-gray-600">Bank Reference</span>
            <input
              name="reference"
              class="mt-1 w-full rounded border-gray-300 h-10"
              placeholder="e.g. HSC-12345"
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
            >
          </label>

          {{-- Submit button --}}
          <div class="sm:col-span-1 flex sm:justify-end">
            <button
              class="h-10 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg w-full sm:w-auto"
              :disabled="false"
            >
              <span x-text="method === 'card' ? 'Pay Now' : 'Submit Transfer'"></span>
            </button>
          </div>
        </div>
      </form>
    @endif
  </div>
@empty
  <x-alert type="info">No rentals yet.</x-alert>
@endforelse

@if(isset($rentals))
  <div class="mt-3">{{ $rentals->links() }}</div>
@endif
@endsection
