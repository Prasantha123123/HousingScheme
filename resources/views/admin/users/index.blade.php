@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Users</h1>
  <a href="{{ route('admin.users.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Add User</a>
</div>

{{-- ===== Mobile: cards ===== --}}
<div class="sm:hidden space-y-3">
  @forelse($rows ?? [] as $u)
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between gap-2">
        <div>
          <div class="text-xs text-gray-500">Name</div>
          <div class="font-medium">{{ $u->name }}</div>
        </div>
        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
          {{ $u->role }}
        </span>
      </div>

      <div class="mt-2 text-sm">
        <div class="text-gray-500">Email</div>
        <div class="break-words">{{ $u->email }}</div>
      </div>

      @if($u->address)
      <div class="mt-2 text-sm">
        <div class="text-gray-500">Address</div>
        <div class="break-words">{{ $u->address }}</div>
      </div>
      @endif

      @if($u->NIC)
      <div class="mt-2 text-sm">
        <div class="text-gray-500">NIC</div>
        <div>{{ $u->NIC }}</div>
      </div>
      @endif

      <div class="mt-3 text-right">
        <a href="{{ route('admin.users.edit',$u->id) }}" class="text-blue-600 hover:underline">Edit</a>
      </div>
    </div>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No users</div>
  @endforelse
</div>

{{-- ===== Tablet / Desktop: table ===== --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">Name</th>
      <th class="px-3 py-2 text-left">Email</th>
      <th class="px-3 py-2 text-left">Role</th>
      <th class="px-3 py-2 text-left hidden md:table-cell">Address</th>
      <th class="px-3 py-2 text-left hidden lg:table-cell">NIC</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>

    @forelse($rows ?? [] as $u)
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ $u->name }}</td>
        <td class="px-3 py-2">{{ $u->email }}</td>
        <td class="px-3 py-2">{{ $u->role }}</td>
        <td class="px-3 py-2 hidden md:table-cell break-words">{{ $u->address }}</td>
        <td class="px-3 py-2 hidden lg:table-cell">{{ $u->NIC }}</td>
        <td class="px-3 py-2 text-right">
          <a href="{{ route('admin.users.edit',$u->id) }}" class="text-blue-600 hover:underline">Edit</a>
        </td>
      </tr>
    @empty
      <tr><td class="px-3 py-6 text-gray-500" colspan="6">No users</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
