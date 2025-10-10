{{-- resources/views/admin/house_bills/index.blade.php --}}
@extends('layouts.app')

@section('content')
<h1 class="text-xl font-semibold mb-3">House Charges</h1>

{{-- Filters --}}
<form method="get"
      class="bg-white rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-3">
  <label class="block">
    <span class="text-sm text-gray-700">Month</span>
    <input class="mt-1 rounded border-gray-300 w-full" type="month" name="month" value="{{ request('month') }}">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">From</span>
    <input class="mt-1 rounded border-gray-300 w-full" type="date" name="from_date" value="{{ request('from_date') }}">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">To</span>
    <input class="mt-1 rounded border-gray-300 w-full" type="date" name="to_date" value="{{ request('to_date') }}">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">Status</span>
    <select name="status" class="mt-1 rounded border-gray-300 w-full">
      <option value="">All Status</option>
      @foreach (['Pending','PartPayment','ExtraPayment','Approved','Rejected'] as $s)
        <option @selected(request('status') === $s)>{{ $s }}</option>
      @endforeach
    </select>
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">House No</span>
    <input class="mt-1 rounded border-gray-300 w-full" type="text" name="houseNo" placeholder="House No" value="{{ request('houseNo') }}">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">Method</span>
    <select name="method" class="mt-1 rounded border-gray-300 w-full">
      <option value="">Any Method</option>
      @foreach (['cash','card','online'] as $m)
        <option @selected(request('method') === $m) value="{{ $m }}">{{ ucfirst($m) }}</option>
      @endforeach
    </select>
  </label>

  <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex gap-2">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg flex-1 sm:flex-none">Filter</button>
    <a href="{{ route('admin.house-bills.pdf', request()->query()) }}" 
       class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-center whitespace-nowrap">
      📄 PDF
    </a>
  </div>
</form>

@php
  use App\Models\WaterReading;
  $unitPrice = (float)\App\Models\Setting::get('water_unit_price', 0);
  $sewerage  = (float)\App\Models\Setting::get('sewerage_charge', 0);
  $service   = (float)\App\Models\Setting::get('service_charge', 0);
@endphp

{{-- ========= Mobile: Cards ========= --}}
<div class="sm:hidden space-y-3">
  @forelse($bills ?? [] as $b)
    @php
      // Prefer WaterReadings table values for this house+month; fallback to fields on HouseRental
      $wr = WaterReading::where('houseNo', $b->houseNo)
              ->where('month', $b->month)
              ->first();
      $opening = (int)($wr->openingReadingUnit ?? $b->openingReading ?? $b->openingReadingUnit ?? 0);
      $current = (int)($wr->readingUnit        ?? $b->readingUnit        ?? 0);
      $usage   = max(0, $current - $opening);
      $balance = max(0, (float)$b->billAmount - (float)$b->paidAmount);
    @endphp
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-sm text-gray-600">House</div>
          <div class="font-medium">{{ $b->houseNo }}</div>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-600">Month</div>
          <div class="font-medium">{{ $b->month }}</div>
        </div>
      </div>

      <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
        <div>
          <div class="text-gray-500">Reading</div>
          <div>{{ $opening }} → {{ $current }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Usage</div>
          <div class="font-medium">{{ $usage }}</div>
        </div>
        <div>
          <div class="text-gray-500">Sewerage</div>
          <div>{{ number_format($sewerage,2) }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Unit Price</div>
          <div>{{ number_format($unitPrice,2) }}</div>
        </div>
        <div>
          <div class="text-gray-500">Service</div>
          <div>{{ number_format($service,2) }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Method</div>
          <div class="uppercase">{{ $b->paymentMethod ?: '-' }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Status</div>
          <div><x-badge :status="$b->status"/></div>
        </div>
      </div>

      {{-- actions (no select checkbox) --}}
      <div class="mt-3 flex items-center justify-end gap-3">
        @if($b->recipt)
          <a class="text-blue-600 hover:underline text-sm" target="_blank" href="{{ asset('storage/'.$b->recipt) }}">Open</a>
        @endif

        {{-- Payment History Button --}}
        @if($b->payments && $b->payments->count() > 0)
          <button type="button" class="text-purple-600 text-sm" x-data @click="$dispatch('open-modal','payments-{{ $b->id }}')">
            Payments ({{ $b->payments->count() }})
          </button>
        @endif

        @if($b->status === 'Approved')
          <button class="text-green-700 text-sm opacity-40 cursor-not-allowed" disabled>Approve</button>
        @else
          @if(empty($b->paymentMethod) || $b->status === 'PartPayment')
            <button type="button" class="text-green-700 text-sm" x-data @click="$dispatch('open-modal','approve-{{ $b->id }}')">
              Approve
            </button>
          @else
            <form method="post" action="{{ route('admin.house-bills.approve',$b->id) }}" class="inline">
              @csrf
              <input type="hidden" name="paymentMethod" value="{{ $b->paymentMethod }}">
              <button class="text-green-700 text-sm">Approve</button>
            </form>
          @endif
        @endif

        <button type="button" class="text-red-700 text-sm" x-data
                @click="$dispatch('open-modal','reject-{{ $b->id }}')">Reject</button>
      </div>
    </div>

    @if($b->status !== 'Approved' && (empty($b->paymentMethod) || $b->status === 'PartPayment'))
      @php
        $maxPayable = $b->maxPayable ?? 0;
      @endphp
      <x-modal :name="'approve-'.$b->id" :title="'Approve Bill #'.$b->id">
        <div x-data="{ paidAmount: '', maxAmount: {{ $maxPayable }} }">
          <form method="post" action="{{ route('admin.house-bills.approve',$b->id) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="paymentMethod" value="{{ $b->paymentMethod ?: 'cash' }}">
            <p class="text-sm text-gray-600">
              Recording a <span class="font-medium">{{ $b->paymentMethod ?: 'cash' }}</span> payment.
              @if($b->status === 'PartPayment')
                <br><span class="text-orange-600">Additional payment for partial bill.</span>
              @endif
              <br><span class="text-sm text-gray-500">Maximum payable amount: Rs {{ number_format($maxPayable, 2) }}</span>
            </p>
            <label class="block">
              <span class="text-sm">Paid Amount</span>
              <input type="number" name="paidAmount" step="0.01" min="0" max="{{ $maxPayable }}"
                     x-model="paidAmount"
                     value="{{ old('paidAmount', '') }}"
                     placeholder="Enter amount (max: {{ number_format($maxPayable, 2) }})"
                     class="mt-1 w-full rounded border-gray-300" required>
              <div x-show="parseFloat(paidAmount) > maxAmount" class="text-red-600 text-xs mt-1">
                Payment amount cannot exceed the maximum payable amount of Rs {{ number_format($maxPayable, 2) }}
              </div>
            </label>
            <div class="text-right">
              <button type="submit" 
                      :disabled="parseFloat(paidAmount) > maxAmount || !paidAmount"
                      :class="{ 'opacity-50 cursor-not-allowed': parseFloat(paidAmount) > maxAmount || !paidAmount }"
                      class="px-3 py-2 bg-green-600 text-white rounded-lg">
                Approve
              </button>
            </div>
          </form>
        </div>
      </x-modal>
    @endif

    <x-modal :name="'reject-'.$b->id" :title="'Reject Bill #'.$b->id">
      <form method="post" action="{{ route('admin.house-bills.reject',$b->id) }}" class="space-y-3">
        @csrf
        <label class="block">
          <span class="text-sm">Reason</span>
          <textarea name="reason" class="mt-1 w-full rounded border-gray-300" required></textarea>
        </label>
        <div class="text-right">
          <button class="px-3 py-2 bg-red-600 text-white rounded-lg">Reject</button>
        </div>
      </form>
    </x-modal>
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No data</div>
  @endforelse
</div>

{{-- ========= Tablet / Desktop: Table ========= --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table>
    <x-slot:head>
      <th class="px-3 py-2 text-left">House No</th>
      <th class="px-3 py-2 text-left">Month</th>
      <th class="px-3 py-2 text-left hidden md:table-cell">Reading</th>
      <th class="px-3 py-2 text-right hidden md:table-cell">Usage</th>
      <th class="px-3 py-2 text-right">Bill</th>
      <th class="px-3 py-2 text-right">Paid</th>
      <th class="px-3 py-2 text-right">Balance</th>
      <th class="px-3 py-2 hidden lg:table-cell">Method</th>
      <th class="px-3 py-2 hidden lg:table-cell">Receipt</th>
      <th class="px-3 py-2">Status</th>
      <th class="px-3 py-2"></th>
    </x-slot:head>

    @forelse($bills ?? [] as $b)
      @php
        // Prefer WaterReadings for this house+month
        $wr = WaterReading::where('houseNo', $b->houseNo)
                ->where('month', $b->month)
                ->first();
        $opening = (int)($wr->openingReadingUnit ?? $b->openingReading ?? $b->openingReadingUnit ?? 0);
        $current = (int)($wr->readingUnit        ?? $b->readingUnit        ?? 0);
        $usage   = max(0, $current - $opening);
        $balance = max(0, (float)$b->billAmount - (float)$b->paidAmount);
      @endphp
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ $b->houseNo }}</td>
        <td class="px-3 py-2">{{ $b->month }}</td>
        <td class="px-3 py-2 hidden md:table-cell">{{ $opening }} → {{ $current }}</td>
        <td class="px-3 py-2 text-right hidden md:table-cell">{{ $usage }}</td>
        <td class="px-3 py-2 text-right">Rs {{ number_format($b->billAmount,2) }}</td>
        <td class="px-3 py-2 text-right">Rs {{ number_format($b->original_payment_amount ?: $b->paidAmount, 2) }}</td>
        <td class="px-3 py-2 text-right">Rs {{ number_format($balance,2) }}</td>
        <td class="px-3 py-2 hidden lg:table-cell uppercase">{{ $b->paymentMethod ?: '-' }}</td>
        <td class="px-3 py-2 hidden lg:table-cell">
          @if($b->recipt)
            <a class="text-blue-600 hover:underline" target="_blank" href="{{ asset('storage/'.$b->recipt) }}">Open</a>
          @endif
        </td>
        <td class="px-3 py-2"><x-badge :status="$b->status"/></td>
        <td class="px-3 py-2 text-right whitespace-nowrap">
          @if($b->status === 'Approved')
            <button class="text-green-700 opacity-40 cursor-not-allowed" disabled>Approve</button>
          @else
            @if(empty($b->paymentMethod) || $b->status === 'PartPayment')
              <button type="button" class="text-green-700" x-data @click="$dispatch('open-modal','approve-{{ $b->id }}')">
                Approve
              </button>
            @else
              <form method="post" action="{{ route('admin.house-bills.approve',$b->id) }}" class="inline">
                @csrf
                <input type="hidden" name="paymentMethod" value="{{ $b->paymentMethod }}">
                <button class="text-green-700">Approve</button>
              </form>
            @endif
          @endif

          @if($b->status !== 'Approved')
            <span class="mx-2 text-gray-300">|</span>
            <button type="button" class="text-red-700" x-data
                    @click="$dispatch('open-modal','reject-{{ $b->id }}')">Reject</button>
          @endif
        </td>
      </tr>

      {{-- Existing modals and payment history UI unchanged from your version --}}
      @if($b->status !== 'Approved' && (empty($b->paymentMethod) || $b->status === 'PartPayment'))
        @php
          $maxPayable = $b->maxPayable ?? 0;
        @endphp
        <x-modal :name="'approve-'.$b->id" :title="'Approve Bill #'.$b->id">
          <div x-data="{ paidAmount: '', maxAmount: {{ $maxPayable }} }">
            <form method="post" action="{{ route('admin.house-bills.approve',$b->id) }}" class="space-y-3">
              @csrf
              <input type="hidden" name="paymentMethod" value="{{ $b->paymentMethod ?: 'cash' }}">
              <p class="text-sm text-gray-600">
                Recording a <span class="font-medium">{{ $b->paymentMethod ?: 'cash' }}</span> payment.
                @if($b->status === 'PartPayment')
                  <br><span class="text-orange-600">Additional payment for partial bill.</span>
                @endif
                <br><span class="text-sm text-gray-500">Maximum payable amount: Rs {{ number_format($maxPayable, 2) }}</span>
              </p>
              <label class="block">
                <span class="text-sm">Paid Amount</span>
                <input type="number" name="paidAmount" step="0.01" min="0" max="{{ $maxPayable }}"
                       x-model="paidAmount"
                       value="{{ old('paidAmount', '') }}"
                       placeholder="Enter amount (max: {{ number_format($maxPayable, 2) }})"
                       class="mt-1 w-full rounded border-gray-300" required>
                <div x-show="parseFloat(paidAmount) > maxAmount" class="text-red-600 text-xs mt-1">
                  Payment amount cannot exceed the maximum payable amount of Rs {{ number_format($maxPayable, 2) }}
                </div>
              </label>
              <div class="text-right">
                <button type="submit" 
                        :disabled="parseFloat(paidAmount) > maxAmount || !paidAmount"
                        :class="{ 'opacity-50 cursor-not-allowed': parseFloat(paidAmount) > maxAmount || !paidAmount }"
                        class="px-3 py-2 bg-green-600 text-white rounded-lg">
                  Approve
                </button>
              </div>
            </form>
          </div>
        </x-modal>
      @endif

      @if($b->payments && $b->payments->count() > 0)
        <x-modal :name="'payments-'.$b->id" :title="'Payment History - Bill #'.$b->id">
          <div class="space-y-3">
            <div class="text-sm text-gray-600">
              <strong>House:</strong> {{ $b->houseNo }} | <strong>Month:</strong> {{ $b->month }}
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Amount</th>
                    <th class="px-3 py-2 text-left">Method</th>
                    <th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Type</th>
                    <th class="px-3 py-2 text-left">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($b->payments->sortByDesc('customerPaidAt') as $payment)
                    <tr class="border-b">
                      <td class="px-3 py-2">
                        {{ $payment->customerPaidAt ? $payment->customerPaidAt->format('M j, Y H:i') : '-' }}
                      </td>
                      <td class="px-3 py-2 font-medium">
                        Rs {{ number_format($payment->paymentmake, 2) }}
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded bg-gray-100">{{ ucfirst($payment->method) }}</span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded {{ $payment->status === 'approval' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                          {{ ucfirst($payment->status) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">{{ ucfirst($payment->paymenttype) }}</span>
                      </td>
                      <td class="px-3 py-2">
                        @if($payment->recipt)
                          <a href="{{ asset('storage/' . $payment->recipt) }}" target="_blank" class="text-blue-600 hover:underline text-xs">Receipt</a>
                        @endif
                        @if($payment->status === 'pending')
                          <form method="post" action="{{ route('admin.house-bills.approve', $b->id) }}" class="inline ml-2">
                            @csrf
                            <input type="hidden" name="paymentMethod" value="{{ $payment->method }}">
                            <button type="submit" class="text-green-600 hover:underline text-xs">Approve</button>
                          </form>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if($b->payments->where('status', 'pending')->count() > 0)
              <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                  <strong>{{ $b->payments->where('status', 'pending')->count() }}</strong> payment(s) awaiting approval
                </p>
              </div>
            @endif
          </div>
        </x-modal>
      @endif
    @empty
      <tr><td class="px-3 py-6 text-gray-500" colspan="11">No data</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($bills))
  <div class="mt-3">{{ $bills->links() }}</div>
@endif
@endsection
