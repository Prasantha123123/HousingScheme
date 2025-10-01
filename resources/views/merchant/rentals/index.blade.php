@extends('layouts.app')
@section('content')
<style>[x-cloak]{display:none!important}</style>

<h1 class="text-xl font-semibold mb-3">My Shop Rentals</h1>

@forelse($rentals ?? [] as $r)
  @php
    $carry = $calc[$r->id]['carry'] ?? 0;
    $current = $calc[$r->id]['current'] ?? $r->billAmount;
    $total = $calc[$r->id]['total'] ?? ($carry + $current);
    $outstanding = max(0, $total - (float)$r->paidAmount);
    $canPay = isset($latestPending[$r->shopNumber]) && $latestPending[$r->shopNumber] === $r->id && $r->status !== 'Approved';
    
    // Allow payment for PartPayment status (partial payments enabled)
    $allowPayment = $canPay && ($r->status !== 'Pending' || $r->status === 'PartPayment');
  @endphp
  
  <div class="bg-white rounded-lg p-4 mb-3 border">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="font-medium">Shop {{ $r->shopNumber }} · {{ $r->month }}</div>
      <x-badge :status="$r->status"/>
    </div>

    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
      <div>
        <div class="text-gray-500">Current Month</div>
        <div class="font-semibold">Rs {{ number_format($current, 2) }}</div>
      </div>
      <div>
        <div class="text-gray-500">Carry Forward</div>
        <div class="font-medium">Rs {{ number_format($carry, 2) }}</div>
      </div>
      <div>
        <div class="text-gray-500">Total Due</div>
        <div class="font-semibold">Rs {{ number_format($total, 2) }}</div>
      </div>
      <div>
        <div class="text-gray-500">Paid</div>
        <div>Rs {{ number_format($r->paidAmount, 2) }}</div>
      </div>
    </div>
    
    @if($outstanding > 0)
      <div class="mt-2 text-sm">
        <div class="text-red-600 font-medium">Outstanding: Rs {{ number_format($outstanding, 2) }}</div>
      </div>
    @endif

    @if($r->recipt)
      <div class="mt-2 text-sm">
        Receipt: <a target="_blank" href="{{ asset('storage/'.$r->recipt) }}" class="text-blue-600 hover:underline">Open</a>
      </div>
    @endif

    @if($allowPayment && $outstanding > 0)
      {{-- Enhanced payment form with carry forward support --}}
      <div x-data="{ 
        method: 'card', 
        amount: {{ $outstanding }},
        maxAmount: {{ $outstanding }},
        isValidAmount() {
          return this.amount > 0 && this.amount <= this.maxAmount;
        }
      }" class="mt-3">
      
      <form
        x-bind:action="method === 'card'
            ? '{{ route('merchant.rentals.pay.card',   $r->id) }}'
            : '{{ route('merchant.rentals.pay.transfer',$r->id) }}'"
        method="post" enctype="multipart/form-data"
      >
        @csrf

        @php
          $isPaymentPending = in_array($r->status, ['InProgress', 'Pending', 'ExtraPayment']) && $r->paidAmount > 0;
        @endphp

        @if($isPaymentPending)
          <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
              <div class="text-yellow-600">⏳</div>
              <div class="ml-2 text-sm text-yellow-800">
                @if($r->status === 'InProgress')
                  Payment in progress - awaiting admin approval
                @elseif($r->status === 'Pending')
                  Payment submitted and awaiting admin approval
                @elseif($r->status === 'ExtraPayment')
                  Overpayment detected - awaiting admin review
                @endif
              </div>
            </div>
          </div>
        @endif

        {{-- Enhanced grid: Method (2) | Amount (1) | Bank Ref (2) | Receipt (1) | Button (1) --}}
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

          {{-- Amount Input --}}
          <label class="sm:col-span-1 block">
            <span class="text-sm text-gray-600">Paid Amount</span>
            <input
              type="number"
              name="amount"
              step="0.01"
              min="0.01"
              max="{{ $outstanding }}"
              x-model="amount"
              value="{{ old('amount', $outstanding) }}"
              placeholder="{{ $outstanding }}"
              class="mt-1 w-full rounded border-gray-300 h-10"
              :class="{ 'border-red-500': !isValidAmount() }"
              required
            >
            <div class="text-xs mt-1" :class="!isValidAmount() ? 'text-orange-600' : 'text-gray-500'">
              <span x-show="isValidAmount()">Maximum: Rs {{ number_format($outstanding, 2) }}</span>
              <span x-show="!isValidAmount()">⚠ Amount cannot exceed Rs {{ number_format($outstanding, 2) }}</span>
            </div>
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
            @php
              $isDisabled = in_array($r->status, ['InProgress', 'Pending', 'ExtraPayment']) && $r->paidAmount > 0;
            @endphp
            <button
              type="submit"
              class="h-10 px-4 rounded-lg w-full sm:w-auto text-white"
              :class="({{ $isDisabled ? 'true' : 'false' }} || !isValidAmount()) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
              :disabled="{{ $isDisabled ? 'true' : 'false' }} || !isValidAmount()"
            >
              <span x-text="method === 'card' ? 'Pay Now' : 'Submit Transfer'"></span>
            </button>
          </div>
        </div>
      </form>
      </div>
    @elseif($r->status !== 'Approved' && $outstanding <= 0)
      <div class="mt-3 text-sm text-green-600 font-medium">
        Fully paid - awaiting admin approval
      </div>
    @endif
  </div>
@empty
  <x-alert type="info">No rentals yet.</x-alert>
@endforelse

@if(isset($rentals))
  <div class="mt-3">{{ $rentals->links() }}</div>
@endif
@endsection
