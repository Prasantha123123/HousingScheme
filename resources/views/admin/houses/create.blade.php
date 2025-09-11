@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add House</h1>

<form method="post" action="{{ route('admin.houses.store') }}" class="bg-white rounded-lg p-4 max-w-xl">
  @csrf

  <div class="space-y-4">
    <label class="block">
      <span class="text-sm">House No</span>
      <input name="houseNo" value="{{ old('houseNo') }}" required
             class="mt-1 w-full rounded border-gray-300" placeholder="H-12">
      @error('houseNo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </label>

    <label class="block">
      <span class="text-sm">Owner (Houseowner)</span>
      <select name="HouseOwneId" class="mt-1 w-full rounded border-gray-300" required>
        <option value="">Select owner…</option>
        @foreach($owners as $o)
          <option value="{{ $o->id }}" @selected(old('HouseOwneId')==$o->id)>
            {{ $o->name }} — {{ $o->email }}
          </option>
        @endforeach
      </select>
      @error('HouseOwneId') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </label>
  </div>

  <div class="mt-4 flex items-center gap-2">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button>
    <a href="{{ route('admin.houses.index') }}" class="px-3 py-2 rounded-lg border">Cancel</a>
  </div>
</form>
@endsection
