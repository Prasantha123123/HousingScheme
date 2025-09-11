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
      <div>Amount:
        <span class="font-semibold">{{ number_format($r->billAmount,2) }}</span>
      </div>
      <div>Paid:
        <span>{{ number_format($r->paidAmount,2) }}</span>
      </div>
      <div>
        @if($r->recipt)
          Receipt:
          <a target="_blank" href="{{ asset('storage/'.$r->recipt) }}" class="text-blue-600 hover:underline">Open</a>
        @endif
      </div>
    </div>

    @if($r->status !== 'Approved')
      <div class="mt-3 flex flex-col sm:flex-row sm:flex-wrap gap-3">
        {{-- Online transfer (receipt upload) --}}
        <form method="post"
              action="{{ route('merchant.rentals.pay.transfer',$r->id) }}"
              enctype="multipart/form-data"
              class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
          @csrf
          <label class="sr-only" for="reference-{{ $r->id }}">Bank reference</label>
          <input id="reference-{{ $r->id }}"
                 name="reference"
                 class="rounded border-gray-300 w-full sm:w-48"
                 placeholder="Bank Ref"
                 required>
          {{-- Your reusable uploader (pdf/jpg/png, ≤5MB server-validated) --}}
          <x-upload name="recipt" :required="true" class="w-full sm:w-56"/>
          <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">
            Upload Receipt
          </button>
        </form>

        {{-- Card payment --}}
        <form method="post"
              action="{{ route('merchant.rentals.pay.card',$r->id) }}"
              class="w-full sm:w-auto">
          @csrf
          <button class="px-3 py-2 bg-blue-600 text-white rounded-lg w-full sm:w-auto">
            Pay by Card
          </button>
        </form>
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
