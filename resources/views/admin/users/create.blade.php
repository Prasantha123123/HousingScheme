@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Add User</h1>
<form method="post" action="{{ route('admin.users.store') }}" class="bg-white rounded-lg p-4 grid md:grid-cols-2 gap-3">
  @csrf
  <label>Name <input class="w-full rounded border-gray-300" name="name" required></label>
  <label>Email <input type="email" class="w-full rounded border-gray-300" name="email" required></label>
  <label>Role
    <select name="role" class="w-full rounded border-gray-300" required>
      <option>Admin</option><option>Houseowner</option><option>Merchant</option><option>Employee</option>
    </select>
  </label>
  <label>Address <input class="w-full rounded border-gray-300" name="address"></label>
  <label>NIC <input class="w-full rounded border-gray-300" name="NIC"></label>
  <label class="md:col-span-2">Password <input type="password" class="w-full rounded border-gray-300" name="password" required></label>
  <div class="md:col-span-2 text-right"><button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button></div>
</form>
@endsection
