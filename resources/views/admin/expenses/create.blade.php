@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add Expense</h1>
<form method="post" action="{{ route('admin.expenses.store') }}" class="bg-white rounded-lg p-4 grid md:grid-cols-2 gap-3">
  @csrf
  <label>Date
    <input type="date" name="date" class="w-full rounded border-gray-300" required>
  </label>
  <label>Name
    <input type="text" name="name" class="w-full rounded border-gray-300" required>
  </label>
  <label>Amount
    <input type="number" name="amount" step="0.01" min="0" class="w-full rounded border-gray-300" required>
  </label>
  <label class="md:col-span-2">Note
    <textarea name="note" class="w-full rounded border-gray-300"></textarea>
  </label>
  <div class="md:col-span-2 text-right">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button>
  </div>
</form>
@endsection
