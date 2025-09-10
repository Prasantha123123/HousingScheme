@props(['status'])
@php
$colors = [
  'Pending'=>'bg-amber-100 text-amber-800',
  'Approved'=>'bg-green-100 text-green-800',
  'Rejected'=>'bg-red-100 text-red-800',
  'Paid'=>'bg-green-100 text-green-800',
  'Unpaid'=>'bg-amber-100 text-amber-800',
];
@endphp
<span {{ $attributes->merge(['class'=>"px-2 py-1 text-xs rounded-full ".$colors[$status] ?? 'bg-gray-100 text-gray-800']) }}>
  {{ $status }}
</span>
