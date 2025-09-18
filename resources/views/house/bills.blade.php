{{-- resources/views/house/bills.blade.php --}}
@extends('layouts.app', ['title' => 'House Bills'])

@section('content')
<style>[x-cloak]{display:none!important}</style>

{{-- Back to Dashboard --}}
<div class="mb-4">
    <a href="{{ route('house.dashboard') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Dashboard
    </a>
</div>

<h1 class="text-xl font-semibold mb-3">My Bills - House {{ $house->houseNo }}</h1>

@php
  $unitPrice = (float) \App\Models\Setting::get('water_unit_price', 0);
  $sewerage  = (float) \App\Models\Setting::get('sewerage_charge', 0);
  $service   = (float) \App\Models\Setting::get('service_charge', 0);

  // Precompute carry/total in ascending month order
  $calc = [];
  $runningOut = 0;

  foreach ($bills->getCollection()->sortBy('month') as $row) {
      $usage   = max(0, $row->readingUnit - $row->openingReadingUnit);
      $current = $sewerage + $service + ($usage * $unitPrice);
      $carry   = $runningOut;
      $total   = $carry + $current;

      $calc[$row->id] = ['carry' => $carry, 'current' => $current, 'total' => $total];
      // Reduce running outstanding by what was paid on that row
      $runningOut = max(0, $total - (float) $row->paidAmount);
  }

  // Latest (by month) bill that is NOT approved
  $latestPending = optional(
      $bills->getCollection()->sortByDesc('month')->first(fn($r) => $r->status !== 'Approved')
  )->id;
@endphp

@if($bills->count() > 0)
  @foreach($bills as $b)
    @php
      $usage = max(0, $b->readingUnit - $b->openingReadingUnit);
      $carry = $calc[$b->id]['carry']  ?? 0;
      $total = $calc[$b->id]['total']  ?? ($sewerage + $service + $usage * $unitPrice);
      $paid  = (float) $b->paidAmount;
      $balanceThisRow = max(0, $total - $paid);
      $outstanding = number_format($balanceThisRow, 2, '.', ''); // ✅ default for input
      $canPay = ($b->id === $latestPending) && $b->status !== 'Approved';
    @endphp

    <div class="bg-white rounded-lg p-4 mb-3 border">
      <div class="flex items-center justify-between">
        <div class="font-medium">{{ $b->month }}</div>
        <x-badge :status="$b->status"/>
      </div>

      <dl class="grid grid-cols-2 md:grid-cols-6 gap-2 text-sm mt-2">
        <div>
          <dt class="text-gray-500">Sewerage</dt>
          <dd>{{ number_format($sewerage,2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Service</dt>
          <dd>{{ number_format($service,2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Usage</dt>
          <dd>{{ $usage }} × {{ number_format($unitPrice,2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Carry Forward</dt>
          <dd>{{ number_format($carry,2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Paid</dt>
          <dd class="font-semibold">{{ number_format($paid,2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Balance</dt>
          <dd class="font-semibold">{{ number_format($balanceThisRow,2) }}</dd>
        </div>
      </dl>

      <div class="mt-2 text-sm">
        <span class="text-gray-500">Total (carry + current)</span> ·
        <span class="font-semibold">{{ number_format($total,2) }}</span>
      </div>

      @if($b->status !== 'Approved')
        <div class="mt-3" x-data="{ method: '{{ $b->paymentMethod === 'card' ? 'card' : 'online' }}' }">
          <div class="max-w-4xl grid grid-cols-1 sm:grid-cols-6 gap-3 items-end" x-cloak>
            {{-- Payment Method --}}
            <label class="sm:col-span-2">
              <span class="text-sm text-gray-600">Payment Method</span>
              <select x-model="method" class="mt-1 w-full rounded border-gray-300 h-10" @disabled(!$canPay)>
                <option value="card">Card</option>
                <option value="online">Bank Transfer</option>
              </select>
            </label>

            {{-- Paid Amount (defaults to OUTSTANDING, not total) --}}
            <label class="sm:col-span-1">
              <span class="text-sm text-gray-600">Paid Amount</span>
              <input
                name="amount"
                form="pay-form-{{ $b->id }}"
                type="number"
                step="0.01"
                min="0.01"
                class="mt-1 w-full rounded border-gray-300 h-10"
                value="{{ old('amount', $outstanding) }}"
                placeholder="{{ $outstanding }}"
                @disabled(!$canPay)
              >
            </label>

            {{-- Bank Ref (online only) --}}
            <label class="sm:col-span-2" x-show="method==='online'">
              <span class="text-sm text-gray-600">Bank Ref</span>
              <input
                name="reference"
                form="pay-form-{{ $b->id }}"
                class="mt-1 w-full rounded border-gray-300 h-10"
                placeholder="e.g. HSC-12345"
                :required="method==='online'"
                @disabled(!$canPay)
              >
            </label>

            {{-- Receipt (online only) --}}
            <label class="sm:col-span-1" x-show="method==='online'">
              <span class="text-sm text-gray-600">Receipt (PDF/JPG/PNG, ≤5MB)</span>
              <input
                type="file"
                name="recipt"
                form="pay-form-{{ $b->id }}"
                class="mt-1 block w-full text-sm"
                accept="application/pdf,image/png,image/jpeg"
                :required="method==='online'"
                @disabled(!$canPay)
              >
            </label>
          </div>

          <form id="pay-form-{{ $b->id }}" method="post" enctype="multipart/form-data"
                :action="method==='card'
                          ? '{{ route('house.bills.pay.card',$b->id) }}'
                          : '{{ route('house.bills.pay.transfer',$b->id) }}'">
            @csrf
            <button
              class="mt-3 px-3 py-2 rounded-lg text-white {{ $canPay ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed' }}"
              @disabled(!$canPay)>
              Pay Now
            </button>
          </form>

          @unless($canPay)
            <p class="mt-2 text-xs text-gray-500">You can only pay the latest outstanding bill.</p>
          @endunless
        </div>
      @endif
    </div>
  @endforeach

  @if(isset($bills))
    <div class="mt-3">{{ $bills->links() }}</div>
  @endif
@else
  {{-- No bills found --}}
  <div class="bg-white shadow overflow-hidden sm:rounded-md">
    <div class="px-4 py-5 sm:p-6">
      <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No bills found</h3>
        <p class="mt-1 text-sm text-gray-500">
          Bills for house {{ $house->houseNo }} will appear here when generated by admin.
        </p>
      </div>
    </div>
  </div>
@endif
@endsection