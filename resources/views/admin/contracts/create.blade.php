@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add Contract</h1>
<form method="post" action="{{ route('admin.contracts.store') }}" class="bg-white rounded-lg p-4 grid md:grid-cols-2 gap-3">
  @csrf
  <label>EmployeeId
    <input type="number" name="EmployeeId" class="w-full rounded border-gray-300" required>
  </label>
  <label>Type
    <select name="contractType" class="w-full rounded border-gray-300" required>
      <option value="dailysallary">dailysallary</option>
      <option value="monthlysalary">monthlysalary</option>
    </select>
  </label>
  <label class="md:col-span-2">Wage Amount
    <input type="number" step="0.01" min="0" name="waheAmount" class="w-full rounded border-gray-300" required>
  </label>
  <div class="md:col-span-2 text-right"><button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button></div>
</form>
@endsection
