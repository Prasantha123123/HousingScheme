@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">Add Shop</h1>

<form method="post" action="{{ route('admin.shops.store') }}" class="bg-white rounded-lg p-4 space-y-4 max-w-2xl">
  @csrf

  {{-- Shop Number --}}
  <label class="block">
    <span class="text-sm text-gray-700">Shop Number</span>
    <input
      type="text"
      name="shopNumber"
      value="{{ old('shopNumber') }}"
      class="mt-1 w-full rounded border-gray-300"
      placeholder="S-101"
      required
    >
    @error('shopNumber') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  {{-- Merchant (optional) --}}
  <label class="block">
    <span class="text-sm text-gray-700">Merchant (optional)</span>
    <select
      name="MerchantId"
      id="merchantSelect"
      class="mt-1 w-full rounded border-gray-300"
    >
      <option value="">No merchant</option>
      @foreach($merchants as $m)
        <option value="{{ $m->id }}" @selected(old('MerchantId')==$m->id)>{{ $m->name }} — {{ $m->email }}</option>
      @endforeach
    </select>
    @error('MerchantId') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  {{-- Shop Password (required if no merchant selected) --}}
  <label class="block">
    <span class="text-sm text-gray-700">
      Shop Password
      <span id="passwordHint" class="text-xs text-gray-500">(required if no merchant selected)</span>
    </span>
    <input
      type="text"
      name="shop_password"
      id="shopPassword"
      class="mt-1 w-full rounded border-gray-300"
      placeholder="Enter a password if merchant is empty"
      value="{{ old('shop_password') }}"
      @if(!old('MerchantId')) {{-- UX hint only; server-side validates anyway --}}
        {{-- no required here; enforced server-side with required_without --}}
      @endif
    >
    @error('shop_password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  <div class="grid sm:grid-cols-2 gap-3">
    {{-- Lease End --}}
    <label class="block">
      <span class="text-sm text-gray-700">Lease End</span>
      <input
        type="date"
        name="leaseEnd"
        value="{{ old('leaseEnd') }}"
        class="mt-1 w-full rounded border-gray-300"
      >
      @error('leaseEnd') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </label>

    {{-- Monthly Rental Amount --}}
    <label class="block">
      <span class="text-sm text-gray-700">Monthly Rental Amount</span>
      <input
        type="number"
        step="0.01"
        min="0"
        name="rentalAmount"
        value="{{ old('rentalAmount', 0) }}"
        class="mt-1 w-full rounded border-gray-300"
        required
      >
      @error('rentalAmount') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </label>
  </div>

  <div class="flex items-center justify-end gap-2 pt-2">
    <a href="{{ route('admin.shops.index') }}" class="px-3 py-2 rounded-lg border">Cancel</a>
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button>
  </div>
</form>

{{-- Small UX helper: toggle password hint emphasis --}}
<script>
  (function(){
    const select = document.getElementById('merchantSelect');
    const pass   = document.getElementById('shopPassword');
    const hint   = document.getElementById('passwordHint');

    function sync() {
      const hasMerchant = !!select.value;
      // Visual cue only; server-side validation is the source of truth
      hint.textContent = hasMerchant
        ? '(optional because a merchant is selected)'
        : '(required if no merchant selected)';
    }
    select && select.addEventListener('change', sync);
    sync();
  })();
</script>
@endsection
