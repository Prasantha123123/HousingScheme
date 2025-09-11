@extends('layouts.app')
@section('content')
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
          Receipt: <a target="_blank" href="{{ asset('storage/'.$r->recipt) }}" class="text-blue-600 hover:underline">Open</a>
        @endif
      </div>
    </div>

    @if($r->status !== 'Approved')
      {{-- Unified payment form --}}
      <form
        x-data="{ method: 'card' }"
        x-bind:action="method === 'card'
            ? '{{ route('merchant.rentals.pay.card',   $r->id) }}'
            : '{{ route('merchant.rentals.pay.transfer',$r->id) }}'"
        method="post" enctype="multipart/form-data"
        class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end"
      >
        @csrf

        <label class="block">
          <span class="text-sm text-gray-600">Payment Method</span>
          <select x-model="method" name="paymentMethod" class="mt-1 w-full rounded border-gray-300" required>
            <option value="card">Card</option>
            <option value="online">Bank Transfer</option>
          </select>
        </label>

        {{-- Bank transfer fields (conditional) --}}
        <div class="grid gap-3 sm:col-span-2" x-show="method === 'online'">
          <label class="block">
            <span class="text-sm text-gray-600">Bank Reference</span>
            <input name="reference" class="mt-1 w-full rounded border-gray-300" placeholder="Bank Ref">
          </label>

          <label class="block">
            <span class="text-sm text-gray-600">Upload Receipt (PDF/JPG/PNG ≤ 5MB)</span>
            <input type="file" name="recipt" accept="application/pdf,image/png,image/jpeg" class="mt-1 w-full">
          </label>
        </div>

        <div class="sm:col-span-2 lg:col-span-1 text-right">
          <button
            class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto"
            :disabled="false"
          >
            <span x-text="method === 'card' ? 'Pay Now' : 'Submit Transfer'"></span>
          </button>
        </div>
      </form>
    @endif
  </div>
@empty
  <x-alert type="info">No rentals yet.</x-alert>
@endforelse

@if(isset($rentals)) <div class="mt-3">{{ $rentals->links() }}</div> @endif
@endsection
