@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Edit House</h1>

@if($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<form method="post" action="{{ route('admin.houses.update',$house->houseNo) }}" class="bg-white rounded-lg p-4 max-w-xl">
  @csrf @method('PUT')

  <div class="space-y-4">
    <label class="block">
      <span class="text-sm">House No</span>
      <input name="houseNo" value="{{ old('houseNo',$house->houseNo) }}" required
             class="mt-1 w-full rounded border-gray-300">
      @error('houseNo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </label>

    <label class="block">
      <span class="text-sm">Owner (Houseowner)</span>
      <select name="HouseOwneId" class="mt-1 w-full rounded border-gray-300">
        <option value="">No owner</option>
        @foreach($owners as $o)
          <option value="{{ $o->id }}" @selected(old('HouseOwneId',$house->HouseOwneId)==$o->id)>
            {{ $o->name }} — {{ $o->email }}
          </option>
        @endforeach
      </select>
      @error('HouseOwneId') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </label>

    <label class="block">
      <span class="text-sm">House Password</span>
      <input name="house_password" type="text" class="mt-1 w-full rounded border-gray-300"
             placeholder="Enter new password" value="{{ old('house_password') }}">
      @error('house_password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </label>
  </div>

  <div class="mt-4 flex items-center gap-2">
    <button type="submit" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Update</button>
    <a href="{{ route('admin.houses.index') }}" class="px-3 py-2 rounded-lg border">Back</a>
    
    <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-lg ml-auto" 
            onclick="if(confirm('Delete this house?')) { document.getElementById('deleteForm').submit(); }">
      Delete
    </button>
  </div>
</form>

<form id="deleteForm" method="post" action="{{ route('admin.houses.destroy',$house->houseNo) }}" style="display: none;">
  @csrf @method('DELETE')
</form>
@endsection
