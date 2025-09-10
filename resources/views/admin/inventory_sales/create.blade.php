@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add Inventory Sale</h1>
<form method="post" action="{{ route('admin.inventory-sales.store') }}" class="bg-white rounded-lg p-4 grid md:grid-cols-2 gap-3">
  @csrf
  <label> Date
    <input type="date" name="date" class="w-full rounded border-gray-300" required>
  </label>
  <label> Item
    <input type="text" name="item" class="w-full rounded border-gray-300" required>
  </label>
  <label> Qty
    <input type="number" step="1" min="0" name="qty" class="w-full rounded border-gray-300" required>
  </label>
  <label> Unit Price
    <input type="number" step="0.01" min="0" name="unit_price" class="w-full rounded border-gray-300" required>
  </label>
  <label class="md:col-span-2"> Note
    <textarea name="note" class="w-full rounded border-gray-300"></textarea>
  </label>
  <div class="md:col-span-2 text-right"><button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button></div>
</form>
@endsection
