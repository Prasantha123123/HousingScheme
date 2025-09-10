@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">House {{ $houseNo }}</h1>

<div class="bg-white rounded-lg p-4">
  <h3 class="font-medium mb-2">Water Readings (by month)</h3>
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">Month</th>
      <th class="px-3 py-2 text-left">Opening</th>
      <th class="px-3 py-2 text-left">Current</th>
      <th class="px-3 py-2 text-right">Usage</th>
      <th class="px-3 py-2 text-right">Bill</th>
      <th class="px-3 py-2 text-left">Status</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>
    @foreach($rentals ?? [] as $b)
      @php $usage = max(0, ($b->readingUnit - $b->openingReadingUnit)); @endphp
      <tr>
        <td class="px-3 py-2">{{ $b->month }}</td>
        <td class="px-3 py-2">{{ $b->openingReadingUnit }}</td>
        <td class="px-3 py-2">{{ $b->readingUnit }}</td>
        <td class="px-3 py-2 text-right">{{ $usage }}</td>
        <td class="px-3 py-2 text-right">{{ number_format($b->billAmount,2) }}</td>
        <td class="px-3 py-2"><x-badge :status="$b->status"/></td>
        <td class="px-3 py-2 text-right">
          {{-- Optional edit reading if needed --}}
          <a href="#" class="text-blue-600 hover:underline" x-data
             @click.prevent="$dispatch('open-edit-{{ $b->id }}')">Edit Reading</a>
        </td>
      </tr>

      <x-modal :id="'edit-'.$b->id" title="Edit Reading ({{ $b->month }})">
        <form method="post" action="{{ route('admin.houses.show',$houseNo) }}"> {{-- replace with your update route --}}
          @csrf
          <div class="grid gap-3">
            <label>Opening <input name="openingReadingUnit" type="number" step="1" class="w-full rounded border-gray-300" value="{{ $b->openingReadingUnit }}"></label>
            <label>Current <input name="readingUnit" type="number" step="1" class="w-full rounded border-gray-300" value="{{ $b->readingUnit }}"></label>
          </div>
          <div class="text-right mt-3">
            <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Save</button>
          </div>
        </form>
      </x-modal>
    @endforeach
  </x-table>
</div>
@endsection
