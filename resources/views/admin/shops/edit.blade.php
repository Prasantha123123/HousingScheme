@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Edit Shop: {{ $shop->shopNumber }}</h1>

@if($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<form method="post" action="{{ route('admin.shops.update', $shop->shopNumber) }}" class="bg-white rounded-lg p-4 space-y-3">
  @csrf @method('PUT')

  <div class="rounded bg-gray-50 p-3 text-sm">
    <div class="text-gray-600">Shop Number</div>
    <div class="font-medium">{{ $shop->shopNumber }}</div>
  </div>

  <label class="block">
    <span class="text-sm text-gray-700">Merchant</span>
    <select name="MerchantId" class="mt-1 w-full rounded border-gray-300">
      <option value="">-- No merchant --</option>
      @foreach($merchants as $m)
        <option value="{{ $m->id }}" @selected(old('MerchantId', $shop->MerchantId)==$m->id)>{{ $m->name }}</option>
      @endforeach
    </select>
    @error('MerchantId') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">Shop Password</span>
    <input type="text" name="shop_password" class="mt-1 w-full rounded border-gray-300"
           placeholder="Enter new password" value="{{ old('shop_password') }}">
    @error('shop_password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
  </label>

  <div class="grid sm:grid-cols-2 gap-3">
    <label class="block">
      <span class="text-sm text-gray-700">Lease End</span>
      <input type="date" name="leaseEnd" value="{{ old('leaseEnd', $shop->leaseEnd) }}" class="mt-1 w-full rounded border-gray-300">
      @error('leaseEnd') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </label>

    <label class="block">
      <span class="text-sm text-gray-700">Monthly Rental Amount</span>
      <input type="number" step="0.01" min="0" name="rentalAmount" value="{{ old('rentalAmount', $shop->rentalAmount) }}" class="mt-1 w-full rounded border-gray-300" required>
      @error('rentalAmount') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </label>
  </div>

  <div class="text-right">
    <button type="submit" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Update</button>
  </div>
</form>
@endsection
