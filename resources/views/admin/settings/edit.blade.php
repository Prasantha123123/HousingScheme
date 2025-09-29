@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Settings</h1>

<form method="post" action="{{ route('admin.settings.update') }}"
      class="bg-white rounded-lg p-4 grid md:grid-cols-2 gap-3">
  @csrf

  <label>Sewerage Charge (default)
    <div class="relative">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600">Rs.</span>
      <input type="number" step="0.01" min="0" name="sewerage_charge"
             class="w-full rounded border-gray-300 pl-12"
             value="{{ old('sewerage_charge', (float) \App\Models\Setting::get('sewerage_charge', 0)) }}" required>
    </div>
  </label>

  <label>Water Unit Price (default)
    <div class="relative">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600">Rs.</span>
      <input type="number" step="0.01" min="0" name="water_unit_price"
             class="w-full rounded border-gray-300 pl-12"
             value="{{ old('water_unit_price', (float) \App\Models\Setting::get('water_unit_price', 0)) }}" required>
    </div>
  </label>

  {{-- NEW: Service Charge --}}
  <label>Service Charge (default)
    <div class="relative">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600">Rs.</span>
      <input type="number" step="0.01" min="0" name="service_charge"
             class="w-full rounded border-gray-300 pl-12"
             value="{{ old('service_charge', (float) \App\Models\Setting::get('service_charge', 0)) }}" required>
    </div>
  </label>

  <label class="md:col-span-2">Bill Due Day (optional)
    <input type="number" min="1" max="31" name="bill_due_day"
           class="w-full rounded border-gray-300"
           value="{{ old('bill_due_day', \App\Models\Setting::get('bill_due_day')) }}">
  </label>

  <div class="md:col-span-2 text-right">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button>
  </div>
</form>
@endsection
