@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add Shop</h1>

<form method="post" action="{{ route('admin.shops.store') }}" class="bg-white rounded-lg p-4 space-y-3">
  @csrf

  <label class="block">
    <span class="text-sm text-gray-700">Shop Number</span>
    <input type="text" name="shopNumber" value="{{ old('shopNumber') }}" class="mt-1 w-full rounded border-gray-300" required>
    @error('shopNumber') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">Merchant</span>
    <select name="MerchantId" class="mt-1 w-full rounded border-gray-300" required>
      <option value="">Select merchant…</option>
      @foreach($merchants as $m)
        <option value="{{ $m->id }}" @selected(old('MerchantId')==$m->id)>{{ $m->name }}</option>
      @endforeach
    </select>
    @error('MerchantId') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  <div class="grid sm:grid-cols-2 gap-3">
    <label class="block">
      <span class="text-sm text-gray-700">Lease End</span>
      <input type="date" name="leaseEnd" value="{{ old('leaseEnd') }}" class="mt-1 w-full rounded border-gray-300">
      @error('leaseEnd') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </label>

    <label class="block">
      <span class="text-sm text-gray-700">Monthly Rental Amount</span>
      <input type="number" step="0.01" min="0" name="rentalAmount" value="{{ old('rentalAmount', 0) }}" class="mt-1 w-full rounded border-gray-300" required>
      @error('rentalAmount') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </label>
  </div>

  <div class="text-right">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button>
  </div>
</form>
@endsection
