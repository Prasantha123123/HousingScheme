@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-3">
  <h1 class="text-xl font-semibold">Users</h1>
  <a href="{{ route('admin.users.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add User</a>
</div>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">Name</th>
    <th class="px-3 py-2 text-left">Email</th>
    <th class="px-3 py-2 text-left">Role</th>
    <th class="px-3 py-2 text-left">Address</th>
    <th class="px-3 py-2 text-left">NIC</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>
  @forelse($rows ?? [] as $u)
  <tr>
    <td class="px-3 py-2">{{ $u->name }}</td>
    <td class="px-3 py-2">{{ $u->email }}</td>
    <td class="px-3 py-2">{{ $u->role }}</td>
    <td class="px-3 py-2">{{ $u->address }}</td>
    <td class="px-3 py-2">{{ $u->NIC }}</td>
    <td class="px-3 py-2 text-right">
      <a href="{{ route('admin.users.edit',$u->id) }}" class="text-blue-600 hover:underline">Edit</a>
    </td>
  </tr>
  @empty
    <tr><td class="px-3 py-6 text-gray-500" colspan="6">No users</td></tr>
  @endforelse
</x-table>
@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
