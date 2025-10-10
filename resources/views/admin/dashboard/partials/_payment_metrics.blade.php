{{-- Enhanced Payment Metrics Section --}}

{{-- Pending Payments Alert --}}
@if(($pendingPayments['total']['count'] ?? 0) > 0)
<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
  <div class="flex">
    <div class="flex-shrink-0">
      <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
    </div>
    <div class="ml-3">
      <h3 class="text-sm font-medium text-yellow-800">
        {{ $pendingPayments['total']['count'] }} Payment(s) Awaiting Approval
      </h3>
      <div class="mt-2 text-sm text-yellow-700">
        <p>Total Amount: Rs {{ number_format($pendingPayments['total']['amount'], 2) }}
          (Houses: {{ $pendingPayments['house']['count'] }}, Shops: {{ $pendingPayments['shop']['count'] }})</p>
      </div>
    </div>
  </div>
</div>
@endif

{{-- Payment Method Breakdown --}}
<div class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
  <h3 class="text-lg font-semibold text-gray-800 mb-3">💳 Payment Methods ({{ Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }})</h3>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach(['cash' => '💵', 'card' => '💳', 'online' => '🌐'] as $method => $icon)
    <div class="border rounded-lg p-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-semibold text-gray-700">{{ $icon }} {{ ucfirst($method) }}</h4>
        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
          {{ ($paymentMethods[$method]['total']['count'] ?? 0) }} payments
        </span>
      </div>
      <div class="text-2xl font-bold text-gray-900 mb-2">
        Rs {{ number_format($paymentMethods[$method]['total']['amount'] ?? 0, 2) }}
      </div>
      <div class="text-xs text-gray-600 space-y-1">
        <div class="flex justify-between">
          <span>Houses:</span>
          <span class="font-medium">Rs {{ number_format($paymentMethods[$method]['house']['amount'] ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Shops:</span>
          <span class="font-medium">Rs {{ number_format($paymentMethods[$method]['shop']['amount'] ?? 0, 2) }}</span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>

{{-- Payment Type Breakdown (Full vs Partial) --}}
<div class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
  <h3 class="text-lg font-semibold text-gray-800 mb-3">📊 Payment Types</h3>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach(['fullpayment' => ['icon' => '✅', 'label' => 'Full Payments', 'color' => 'green'], 
              'partpayment' => ['icon' => '⚠️', 'label' => 'Partial Payments', 'color' => 'orange']] as $type => $config)
    <div class="border rounded-lg p-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-semibold text-{{ $config['color'] }}-700">
          {{ $config['icon'] }} {{ $config['label'] }}
        </h4>
        <span class="text-xs bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-800 px-2 py-1 rounded">
          {{ ($paymentTypes[$type]['total']['count'] ?? 0) }} payments
        </span>
      </div>
      <div class="text-2xl font-bold text-{{ $config['color'] }}-600 mb-2">
        Rs {{ number_format($paymentTypes[$type]['total']['amount'] ?? 0, 2) }}
      </div>
      <div class="text-sm text-gray-600">
        <div class="flex justify-between mb-1">
          <span>Houses:</span>
          <span class="font-medium">
            {{ ($paymentTypes[$type]['house']['count'] ?? 0) }} 
            (Rs {{ number_format($paymentTypes[$type]['house']['amount'] ?? 0, 2) }})
          </span>
        </div>
        <div class="flex justify-between">
          <span>Shops:</span>
          <span class="font-medium">
            {{ ($paymentTypes[$type]['shop']['count'] ?? 0) }} 
            (Rs {{ number_format($paymentTypes[$type]['shop']['amount'] ?? 0, 2) }})
          </span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>
