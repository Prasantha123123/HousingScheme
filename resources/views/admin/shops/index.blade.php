@extends('layouts.app')
@section('content')

<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Shops</h1>

  {{-- Generate bills for all shops --}}
  <div class="flex flex-wrap items-center gap-2">
    <form method="post" action="{{ route('admin.shop-rentals.generate') }}" class="flex items-center gap-2">
      @csrf
      <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"
             class="rounded border-gray-300 w-full sm:w-auto">
      <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">
        Generate Current Month Bills
      </button>
    </form>

    <a href="{{ route('admin.shops.create') }}"
       class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto text-center">
      Add Shop
    </a>
  </div>
</div>

<form method="get" class="mb-3 flex flex-wrap gap-2">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search shop no / merchant"
         class="rounded border-gray-300 w-full sm:w-80">
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">Search</button>
</form>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">Shop No</th>
    <th class="px-3 py-2 text-left">Merchant</th>
    <th class="px-3 py-2 text-left">Lease End</th>
    <th class="px-3 py-2 text-right">Rental Amount</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>

  @forelse($rows ?? [] as $s)
    <tr class="hover:bg-gray-50">
      <td class="px-3 py-2">{{ $s->shopNumber }}</td>
      <td class="px-3 py-2">{{ optional($s->merchant)->name ?? $s->MerchantId }}</td>
      <td class="px-3 py-2">{{ $s->leaseEnd ?: '-' }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($s->rentalAmount,2) }}</td>
      <td class="px-3 py-2 text-right">
        <a href="{{ route('admin.shops.edit',$s->shopNumber) }}" class="text-blue-600 hover:underline">Edit</a>
        <form method="post" action="{{ route('admin.shops.destroy',$s->shopNumber) }}" class="inline"
              onsubmit="return confirm('Delete this shop?')">
          @csrf @method('DELETE')
          <button class="text-red-600 ml-2">Delete</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td class="px-3 py-6 text-gray-500" colspan="6">No shops</td></tr>
  @endforelse
</x-table>

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
