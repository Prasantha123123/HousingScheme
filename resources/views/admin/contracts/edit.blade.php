@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Edit Contract</h1>
<form method="post" action="{{ route('admin.contracts.update',$row->id) }}" class="bg-white rounded-lg p-4 grid md:grid-cols-2 gap-3">
  @csrf @method('PUT')
  <label>EmployeeId
    <input type="number" name="EmployeeId" value="{{ $row->EmployeeId }}" class="w-full rounded border-gray-300" required>
  </label>
  <label>Type
    <select name="contractType" class="w-full rounded border-gray-300" required>
      @foreach(['dailysallary','monthlysalary'] as $t)
        <option @selected($row->contractType===$t) value="{{ $t }}">{{ $t }}</option>
      @endforeach
    </select>
  </label>
  <label class="md:col-span-2">Wage Amount
    <input type="number" step="0.01" min="0" name="waheAmount" value="{{ $row->waheAmount }}" class="w-full rounded border-gray-300" required>
  </label>
  <div class="md:col-span-2 text-right"><button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Update</button></div>
</form>
@endsection
