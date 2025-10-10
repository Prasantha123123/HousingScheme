{{-- Payment Summary Widget for Admin Dashboard --}}
@if(auth()->check() && auth()->user()->role === 'Admin')
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Overview</h3>
    
    @php
      $pendingHousePayments = \App\Models\HousePayment::where('status', 'pending')->count();
      $pendingShopPayments = \App\Models\ShopPayment::where('status', 'pending')->count();
      $totalPending = $pendingHousePayments + $pendingShopPayments;
      
      $todayHousePayments = \App\Models\HousePayment::whereDate('customerPaidAt', today())->count();
      $todayShopPayments = \App\Models\ShopPayment::whereDate('customerPaidAt', today())->count();
      $todayTotal = $todayHousePayments + $todayShopPayments;
    @endphp
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ $totalPending }}</div>
        <div class="text-sm text-gray-600">Pending Approvals</div>
      </div>
      
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-600">{{ $todayTotal }}</div>
        <div class="text-sm text-gray-600">Today's Payments</div>
      </div>
      
      <div class="text-center">
        <div class="text-2xl font-bold text-green-600">{{ $pendingHousePayments }}</div>
        <div class="text-sm text-gray-600">House Pending</div>
      </div>
      
      <div class="text-center">
        <div class="text-2xl font-bold text-purple-600">{{ $pendingShopPayments }}</div>
        <div class="text-sm text-gray-600">Shop Pending</div>
      </div>
    </div>
    
    @if($totalPending > 0)
      <div class="mt-4 flex space-x-2">
        @if($pendingHousePayments > 0)
          <a href="{{ route('admin.house-bills.index') }}?status=InProgress" 
             class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
            Review House Payments
          </a>
        @endif
        
        @if($pendingShopPayments > 0)
          <a href="{{ route('admin.shop-rentals.index') }}?status=InProgress" 
             class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700">
            Review Shop Payments
          </a>
        @endif
      </div>
    @endif
  </div>
@endif