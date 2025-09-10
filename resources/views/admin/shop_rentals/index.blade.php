@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Shop Rentals</h1>

<form method="get" class="bg-white rounded-lg p-3 grid md:grid-cols-4 gap-2 mb-3">
  <input class="rounded border-gray-300" type="month" name="month" value="{{ request('month') }}">
  <input class="rounded border-gray-300" type="text" name="shopNumber" placeholder="Shop No" value="{{ request('shopNumber') }}">
  <select name="status" class="rounded border-gray-300">
    <option value="">All Status</option>
    @foreach(['Pending','Approved','Rejected'] as $s)
      <option @selected(request('status')===$s)>{{ $s }}</option>
    @endforeach
  </select>
  <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Filter</button>
</form>

<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">Shop No</th>
    <th class="px-3 py-2 text-left">Merchant</th>
    <th class="px-3 py-2 text-left">Month</th>
    <th class="px-3 py-2 text-right">Bill</th>
    <th class="px-3 py-2 text-right">Paid</th>
    <th class="px-3 py-2">Method</th>
    <th class="px-3 py-2">Receipt</th>
    <th class="px-3 py-2">Status</th>
    <th class="px-3 py-2"></th>
  </x-slot:head>

  @forelse($rows ?? [] as $r)
    <tr>
      <td class="px-3 py-2">{{ $r->shopNumber }}</td>
      <td class="px-3 py-2">{{ $r->merchant_name ?? '-' }}</td>
      <td class="px-3 py-2">{{ $r->month }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($r->billAmount,2) }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($r->paidAmount,2) }}</td>
      <td class="px-3 py-2">{{ $r->paymentMethod }}</td>
      <td class="px-3 py-2">@if($r->recipt)<a target="_blank" class="text-blue-600 hover:underline" href="{{ asset('storage/'.$r->recipt) }}">Open</a>@endif</td>
      <td class="px-3 py-2"><x-badge :status="$r->status"/></td>
      <td class="px-3 py-2 text-right whitespace-nowrap">
        <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">@csrf
          <button class="text-green-700">Approve</button>
        </form>
        <span class="mx-2 text-gray-300">|</span>
        <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">@csrf
          <button class="text-red-700">Reject</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td class="px-3 py-6 text-gray-500" colspan="9">No data</td></tr>
  @endforelse
</x-table>

@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
