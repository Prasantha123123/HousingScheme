@extends('layouts.app')
@section('content')
<h1 class="text-xl font-semibold mb-3">Payroll History</h1>
<x-table>
  <x-slot:head>
    <th class="px-3 py-2 text-left">EmployeeId</th>
    <th class="px-3 py-2 text-left">Month</th>
    <th class="px-3 py-2 text-right">Wage Net</th>
    <th class="px-3 py-2">Paid Type</th>
    <th class="px-3 py-2">Status</th>
    <th class="px-3 py-2">File</th>
  </x-slot:head>
  @forelse($rows ?? [] as $r)
    <tr>
      <td class="px-3 py-2">{{ $r->EmployeeId }}</td>
      <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($r->timestamp)->format('Y-m') }}</td>
      <td class="px-3 py-2 text-right">{{ number_format($r->wage_net,2) }}</td>
      <td class="px-3 py-2">{{ $r->paidType }}</td>
      <td class="px-3 py-2"><x-badge :status="$r->status"/></td>
      <td class="px-3 py-2">@if($r->files)<a target="_blank" class="text-blue-600 hover:underline" href="{{ asset('storage/'.$r->files) }}">Open</a>@endif</td>
    </tr>
  @empty
    <tr><td class="px-3 py-6 text-gray-500" colspan="6">No records</td></tr>
  @endforelse
</x-table>
@if(isset($rows)) <div class="mt-3">{{ $rows->links() }}</div> @endif
@endsection
