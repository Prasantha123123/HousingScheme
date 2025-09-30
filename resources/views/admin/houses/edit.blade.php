@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">Edit House</h1>

{{-- Flash: success or status --}}
@if (session('success') || session('status'))
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
    {{ session('success') ?? session('status') }}
  </div>
@endif

<form id="editHouseForm" method="post" action="{{ route('admin.houses.update', $house->houseNo) }}" class="bg-white rounded-lg p-4 max-w-xl">
  @csrf
  @method('PUT')

  <div class="space-y-4">
    {{-- House number: display only (not submitted, cannot be edited) --}}
    <label class="block">
      <span class="text-sm">House No</span>
      <input value="{{ $house->houseNo }}" readonly
             class="mt-1 w-full rounded border-gray-300 bg-gray-100 cursor-not-allowed">
    </label>

    {{-- Owner: display only (disabled, not submitted) --}}
    <label class="block">
      <span class="text-sm">Owner (Houseowner)</span>
      <select class="mt-1 w-full rounded border-gray-300 bg-gray-100 cursor-not-allowed" disabled>
        <option value="">{{ $house->HouseOwneId ? 'Selected owner' : 'No owner' }}</option>
        @foreach($owners as $o)
          <option value="{{ $o->id }}" @selected($house->HouseOwneId == $o->id)>
            {{ $o->name }} — {{ $o->email }}
          </option>
        @endforeach
      </select>
    </label>

    {{-- Editable field: Password --}}
    <label class="block">
      <span class="text-sm">House Password</span>
      <input
        name="house_password"
        type="password"
        class="mt-1 w-full rounded border-gray-300"
        placeholder="Enter new password"
        autocomplete="new-password"
      >
      @error('house_password')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
      @enderror
    </label>
  </div>

  <div class="mt-4 flex items-center gap-2">
    <button type="submit" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Update Password</button>
    <a href="{{ route('admin.houses.index') }}" class="px-3 py-2 rounded-lg border">Back</a>

    <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-lg ml-auto"
            onclick="if(confirm('Delete this house?')) { document.getElementById('deleteForm').submit(); }">
      Delete
    </button>
  </div>
</form>

<form id="deleteForm" method="post" action="{{ route('admin.houses.destroy', $house->houseNo) }}" style="display:none;">
  @csrf
  @method('DELETE')
</form>
@endsection
