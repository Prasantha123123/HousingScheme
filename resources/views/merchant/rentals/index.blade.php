@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">My Shop Rentals</h1>

@forelse($rentals ?? [] as $r)
<div class="bg-white rounded-lg p-4 mb-3 border">
  <div class="flex items-center justify-between">
    <div class="font-medium">Shop {{ $r->shopNumber }} · {{ $r->month }}</div>
    <x-badge :status="$r->status"/>
  </div>
  <div class="text-sm mt-2 flex flex-wrap items-center gap-4">
    <div>Amount: <span class="font-semibold">{{ number_format($r->billAmount,2) }}</span></div>
    <div>Paid: <span>{{ number_format($r->paidAmount,2) }}</span></div>
  </div>

  @if($r->status!=='Approved')
  <div class="mt-3 flex flex-wrap gap-2">
    <form method="post" action="{{ route('merchant.rentals.pay.transfer',$r->id) }}" enctype="multipart/form-data" class="flex items-center gap-2">
      @csrf
      <input name="reference" class="rounded border-gray-300" placeholder="Bank Ref" required>
      <x-upload name="recipt" :required="true"/>
      <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Upload Receipt</button>
    </form>
    <form method="post" action="{{ route('merchant.rentals.pay.card',$r->id) }}">
      @csrf
      <button class="px-3 py-2 bg-blue-600 text-white rounded-lg">Pay by Card</button>
    </form>
  </div>
  @endif

  @if($r->recipt)
  <div class="mt-2 text-sm">
    Receipt: <a target="_blank" href="{{ asset('storage/'.$r->recipt) }}" class="text-blue-600 hover:underline">Open</a>
  </div>
  @endif
</div>
@empty
  <x-alert type="info">No rentals yet.</x-alert>
@endforelse

@if(isset($rentals)) <div class="mt-3">{{ $rentals->links() }}</div> @endif
@endsection
