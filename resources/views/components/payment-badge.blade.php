@props(['payment'])

@php
  $status = $payment->status ?? 'pending';
  $colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'inprogress' => 'bg-blue-100 text-blue-800',
    'approval' => 'bg-green-100 text-green-800',
  ];
  $cls = $colors[$status] ?? 'bg-gray-100 text-gray-800';
@endphp

<div class="flex items-center space-x-2 text-xs">
  <span class="px-2 py-1 rounded {{ $cls }}">
    {{ ucfirst($status) }}
  </span>
  <span class="text-gray-600">
    {{ $payment->customerPaidAt ? $payment->customerPaidAt->format('M j, Y') : '-' }}
  </span>
  <span class="font-medium">
    Rs {{ number_format($payment->paymentmake, 2) }}
  </span>
  <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
    {{ ucfirst($payment->method) }}
  </span>
</div>