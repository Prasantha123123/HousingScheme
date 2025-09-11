@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">My Bills</h1>

@php
  $unitPrice = (float) \App\Models\Setting::get('water_unit_price', 0);
  $sewerage  = (float) \App\Models\Setting::get('sewerage_charge', 0);

  $calc = [];
  $runningOut = 0;

  foreach ($bills->getCollection()->sortBy('month') as $row) {
      $usage   = max(0, $row->readingUnit - $row->openingReadingUnit);
      $current = $sewerage + ($usage * $unitPrice);
      $carry   = $runningOut;
      $total   = $carry + $current;

      $calc[$row->id] = ['carry' => $carry, 'current' => $current, 'total' => $total];
      $runningOut = max(0, $total - (float) $row->paidAmount);
  }

  $latestPending = optional(
      $bills->getCollection()->sortByDesc('month')->first(fn($r) => $r->status !== 'Approved')
  )->id;
@endphp

@foreach($bills as $b)
  @php
    $usage = max(0, $b->readingUnit - $b->openingReadingUnit);
    $carry = $calc[$b->id]['carry']  ?? 0;
    $total = $calc[$b->id]['total']  ?? ($sewerage + $usage * $unitPrice);
    $canPay = ($b->id === $latestPending) && $b->status !== 'Approved';
  @endphp

  <div class="bg-white rounded-lg p-4 mb-3 border">
    <div class="flex items-center justify-between">
      <div class="font-medium">{{ $b->month }}</div>
      <x-badge :status="$b->status"/>
    </div>

    <dl class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm mt-2">
      <div><dt class="text-gray-500">Sewerage</dt><dd>{{ number_format($sewerage,2) }}</dd></div>
      <div><dt class="text-gray-500">Usage (units)</dt><dd>{{ $usage }} × {{ number_format($unitPrice,2) }}</dd></div>
      <div><dt class="text-gray-500">Carry Forward</dt><dd>{{ number_format($carry,2) }}</dd></div>
      <div><dt class="text-gray-500">Total</dt><dd class="font-semibold">{{ number_format($total,2) }}</dd></div>
    </dl>

    @if($b->status !== 'Approved')
      <div class="mt-3" x-data="{ method: '{{ $b->paymentMethod === 'card' ? 'card' : 'online' }}' }">
        <div class="max-w-xl grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
          <label class="sm:col-span-1">
            <span class="text-sm text-gray-600">Payment Method</span>
            <select x-model="method" class="mt-1 w-full rounded border-gray-300" @disabled(!$canPay)>
              <option value="card">Card</option>
              <option value="online">Bank Transfer</option>
            </select>
          </label>

          <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-2" x-show="method==='online'">
            <label class="sm:col-span-1">
              <span class="text-sm text-gray-600">Bank Ref</span>
              <input name="reference" form="pay-form-{{ $b->id }}"
                     class="mt-1 w-full rounded border-gray-300"
                     placeholder="e.g. HSC-12345"
                     :required="method==='online'"
                     @disabled(!$canPay)>
            </label>
            <label class="sm:col-span-2">
              <span class="text-sm text-gray-600">Receipt (PDF/JPG/PNG, ≤5MB)</span>
              <input type="file" name="recipt" form="pay-form-{{ $b->id }}"
                     class="mt-1 block w-full text-sm"
                     accept="application/pdf,image/png,image/jpeg"
                     :required="method==='online'"
                     @disabled(!$canPay)>
            </label>
          </div>
        </div>

        <form id="pay-form-{{ $b->id }}" method="post" enctype="multipart/form-data"
              :action="method==='card'
                        ? '{{ route('customer.bills.pay.card',$b->id) }}'
                        : '{{ route('customer.bills.pay.transfer',$b->id) }}'">
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
@endsection
