{{-- resources/views/customer/bills/index.blade.php --}}
@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">My Bills</h1>

@foreach($bills as $b)
@php
  $unitPrice = (float) \App\Models\Setting::get('water_unit_price', 0);
  $sewerage  = (float) \App\Models\Setting::get('sewerage_charge', 0);
  $usage = max(0, $b->readingUnit - $b->openingReadingUnit);
  $prevUnpaid = $b->billAmount - ($sewerage + $usage*$unitPrice); // display only
@endphp
<div class="bg-white rounded-lg p-4 mb-3">
  <div class="flex items-center justify-between">
    <div class="font-medium">{{ $b->month }}</div>
    <x-badge :status="$b->status"/>
  </div>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm mt-2">
    <div><dt class="text-gray-500">Sewerage</dt><dd>{{ number_format($sewerage,2) }}</dd></div>
    <div><dt class="text-gray-500">Usage (units)</dt><dd>{{ $usage }} × {{ number_format($unitPrice,2) }}</dd></div>
    <div><dt class="text-gray-500">Carry Forward</dt><dd>{{ number_format(max(0,$prevUnpaid),2) }}</dd></div>
    <div><dt class="text-gray-500">Total</dt><dd class="font-semibold">{{ number_format($b->billAmount,2) }}</dd></div>
  </dl>

  @if($b->status!=='Approved')
  <div class="mt-3 flex flex-wrap gap-2">
    <form method="post" action="{{ route('customer.bills.pay.transfer',$b->id) }}" enctype="multipart/form-data" class="flex items-center gap-2">
      @csrf
      <input name="reference" class="rounded border-gray-300" placeholder="Bank Ref" required>
      <x-upload name="recipt"/>
      <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Upload Receipt</button>
    </form>
    <form method="post" action="{{ route('customer.bills.pay.card',$b->id) }}">
      @csrf
      <button class="px-3 py-2 bg-blue-600 text-white rounded-lg">Pay by Card</button>
    </form>
  </div>
  @endif
</div>
@endforeach

<div class="mt-3">{{ $bills->links() }}</div>
@endsection
