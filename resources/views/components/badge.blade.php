@props(['status' => 'Pending'])

@php
  // normalize (handles "InProgress", "in progress", etc.)
  $norm = strtolower(str_replace(' ', '', (string) $status));

  $colors = [
    'pending'    => 'bg-amber-100 text-amber-800',
    'inprogress' => 'bg-blue-100 text-blue-800',
    'approved'   => 'bg-green-100 text-green-800',
    'paid'       => 'bg-green-100 text-green-800',
    'unpaid'     => 'bg-amber-100 text-amber-800',
    'rejected'   => 'bg-red-100 text-red-800',
  ];

  // safe lookup + human label
  $cls   = $colors[$norm] ?? 'bg-gray-100 text-gray-800';
  $label = $norm === 'inprogress' ? 'In Progress' : (string) $status;
@endphp

<span {{ $attributes->merge(['class' => "px-2 py-1 text-xs rounded-full $cls"]) }}>
  {{ $label }}
</span>
