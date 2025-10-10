{{-- resources/views/admin/house_bills/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <div>
    <h1 class="text-xl font-semibold">House Charges</h1>
    @if(isset($bills))
      <p class="text-sm text-gray-500">
        @if($bills->hasPages())
          Showing {{ $bills->count() }} bills (Page {{ $bills->currentPage() }} of {{ $bills->lastPage() }})
        @else
          {{ $bills->count() }} {{ Str::plural('bill', $bills->count()) }} found
        @endif
      </p>
    @endif
  </div>

  {{-- Generate Bills Form --}}
  <form method="post" action="{{ route('admin.house-bills.generate') }}"
        class="flex flex-wrap items-center gap-2">
    @csrf
    <input type="month"
           name="month"
           value="{{ request('month', now()->format('Y-m')) }}"
           class="rounded border-gray-300 w-full sm:w-auto">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg w-full sm:w-auto">
      Generate Bills
    </button>
  </form>
</div>

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
    <a href="{{ route('admin.house-bills.index') }}"
       class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-center whitespace-nowrap">
      Clear Filters
    </a>
    <a href="{{ route('admin.house-bills.pdf', request()->query()) }}"
       class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-center whitespace-nowrap">
      📄 PDF
    </a>
  </div>
</form>

{{-- Active Filters Display --}}
@if(request()->hasAny(['month', 'from_date', 'to_date', 'status', 'houseNo', 'method']))
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
    <div class="flex items-center justify-between">
      <div>
        <span class="text-sm font-medium text-blue-800">Active Filters:</span>
        <div class="flex flex-wrap gap-2 mt-1">
          @if(request('month'))
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
              Month: {{ request('month') }}
            </span>
          @endif
          @if(request('from_date'))
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
              From: {{ request('from_date') }}
            </span>
          @endif
          @if(request('to_date'))
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
              To: {{ request('to_date') }}
            </span>
          @endif
          @if(request('status'))
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
              Status: {{ request('status') }}
            </span>
          @endif
          @if(request('houseNo'))
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
              House: {{ request('houseNo') }}
            </span>
          @endif
          @if(request('method'))
            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
              Method: {{ ucfirst(request('method')) }}
            </span>
          @endif
        </div>
      </div>
      <a href="{{ route('admin.house-bills.index') }}"
         class="text-blue-600 hover:text-blue-800 text-sm font-medium">
        Clear All
      </a>
    </div>
  </div>
@endif

@php
  $unitPrice = (float)\App\Models\Setting::get('water_unit_price', 0);
  $sewerage  = (float)\App\Models\Setting::get('sewerage_charge', 0);
  $service   = (float)\App\Models\Setting::get('service_charge', 0);
@endphp

{{-- ========= Mobile: Cards ========= --}}
<div class="sm:hidden space-y-3">
  @forelse($bills ?? [] as $b)
    @php
      $usage   = max(0, ($b->readingUnit - $b->openingReadingUnit));
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
          <div>{{ $b->openingReadingUnit }} → {{ $b->readingUnit }}</div>
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

        {{-- View Bill Button --}}
        <button type="button" class="text-blue-600 text-sm" x-data @click="$dispatch('open-modal','view-bill-{{ $b->id }}')">
          View Bill
        </button>

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
    <div class="rounded-lg border bg-white p-6 text-center">
      <div class="text-gray-500 mb-3">
        <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p>No bills found</p>
      </div>
      <p class="text-sm text-gray-400 mb-4">Use the "Generate Bills" button above to create bills for houses</p>
      <a href="{{ route('admin.houses.index') }}"
         class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        🏠 Go to Houses
      </a>
    </div>
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
        $usage   = max(0, ($b->readingUnit - $b->openingReadingUnit));
        $balance = max(0, (float)$b->billAmount - (float)$b->paidAmount);
      @endphp
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2">{{ $b->houseNo }}</td>
        <td class="px-3 py-2">{{ $b->month }}</td>
        <td class="px-3 py-2 hidden md:table-cell">{{ $b->openingReadingUnit }} → {{ $b->readingUnit }}</td>
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
          <button type="button" class="text-blue-600 mr-2" x-data @click="$dispatch('open-modal','view-bill-{{ $b->id }}')">
            View
          </button>

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
    @empty
      <tr>
        <td class="px-3 py-8 text-center text-gray-500" colspan="11">
          <div class="flex flex-col items-center">
            <svg class="h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>No bills found</p>
            <p class="text-sm text-gray-400 mt-1">Generate bills using the form above</p>
          </div>
        </td>
      </tr>
    @endforelse
  </x-table>
</div>

{{-- Modals for all bills --}}
@if(isset($bills))
  @foreach($bills as $b)
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

      {{-- View Bill Modal --}}
      <x-modal :name="'view-bill-'.$b->id" :title="'Bill Details - House #'.$b->houseNo">
        <div class="space-y-4">
          {{-- Basic Information --}}
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-700">House Number:</span>
                <div class="text-lg font-bold">{{ $b->houseNo }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Billing Month:</span>
                <div class="text-lg font-bold">{{ $b->month }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Bill Date:</span>
                <div>{{ $b->timestamp ? $b->timestamp->format('M j, Y') : '-' }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Status:</span>
                <div><x-badge :status="$b->status"/></div>
              </div>
            </div>
          </div>

          {{-- Water Usage --}}
          <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Water Usage Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Opening Reading:</span>
                <div class="font-medium">{{ number_format($b->openingReadingUnit) }} units</div>
              </div>
              <div>
                <span class="text-gray-600">Current Reading:</span>
                <div class="font-medium">{{ number_format($b->readingUnit) }} units</div>
              </div>
              <div>
                <span class="text-gray-600">Usage:</span>
                <div class="font-bold text-blue-600">{{ number_format($usage) }} units</div>
              </div>
              <div>
                <span class="text-gray-600">Unit Price:</span>
                <div class="font-medium">Rs {{ number_format($unitPrice, 2) }}</div>
              </div>
            </div>
          </div>

          {{-- Bill Breakdown --}}
          <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Bill Breakdown</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Sewerage Charge:</span>
                <span class="font-medium">Rs {{ number_format($sewerage, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Service Charge:</span>
                <span class="font-medium">Rs {{ number_format($service, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Water Usage ({{ $usage }} × Rs {{ number_format($unitPrice, 2) }}):</span>
                <span class="font-medium">Rs {{ number_format($usage * $unitPrice, 2) }}</span>
              </div>
              <hr class="my-2">
              <div class="flex justify-between text-lg font-bold">
                <span>Total Bill Amount:</span>
                <span class="text-green-600">Rs {{ number_format($b->billAmount, 2) }}</span>
              </div>
            </div>
          </div>

          {{-- Payment Status --}}
          <div class="bg-yellow-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Payment Information</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Amount Paid:</span>
                <span class="font-medium text-green-600">Rs {{ number_format($b->paidAmount, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Remaining Balance:</span>
                <span class="font-medium text-red-600">Rs {{ number_format($balance, 2) }}</span>
              </div>
              @if($b->paymentMethod)
                <div class="flex justify-between">
                  <span>Payment Method:</span>
                  <span class="font-medium uppercase">{{ $b->paymentMethod }}</span>
                </div>
              @endif
              @if($b->maxPayable)
                <div class="flex justify-between text-xs text-gray-500">
                  <span>Max Payable (including previous dues):</span>
                  <span>Rs {{ number_format($b->maxPayable, 2) }}</span>
                </div>
              @endif
            </div>
          </div>

          {{-- Receipt --}}
          @if($b->recipt)
            <div class="text-center">
              <a href="{{ asset('storage/'.$b->recipt) }}" target="_blank"
                 class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                📄 View Receipt
              </a>
            </div>
          @endif
        </div>
      </x-modal>

      {{-- Payment History Modal --}}
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
                        <span class="px-2 py-1 text-xs rounded bg-gray-100">
                          {{ ucfirst($payment->method) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded {{ $payment->status === 'approval' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                          {{ ucfirst($payment->status) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                          {{ ucfirst($payment->paymenttype) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        @if($payment->recipt)
                          <a href="{{ asset('storage/' . $payment->recipt) }}" target="_blank"
                             class="text-blue-600 hover:underline text-xs">Receipt</a>
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
                  <strong>{{ $b->payments->where('status', 'pending')->count() }}</strong>
                  payment(s) awaiting approval
                </p>
              </div>
            @endif
          </div>
        </x-modal>
      @endif

      {{-- Reject Modal --}}
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

      {{-- View Bill Modal --}}
      <x-modal :name="'view-bill-'.$b->id" :title="'Bill Details - House #'.$b->houseNo">
        @php
          $usage   = max(0, ($b->readingUnit - $b->openingReadingUnit));
          $balance = max(0, (float)$b->billAmount - (float)$b->paidAmount);
          $unitPrice = (float)\App\Models\Setting::get('water_unit_price', 0);
          $sewerage  = (float)\App\Models\Setting::get('sewerage_charge', 0);
          $service   = (float)\App\Models\Setting::get('service_charge', 0);
        @endphp
        <div class="space-y-4">
          {{-- Basic Information --}}
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-700">House Number:</span>
                <div class="text-lg font-bold">{{ $b->houseNo }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Billing Month:</span>
                <div class="text-lg font-bold">{{ $b->month }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Bill Date:</span>
                <div>{{ $b->timestamp ? $b->timestamp->format('M j, Y') : '-' }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Status:</span>
                <div><x-badge :status="$b->status"/></div>
              </div>
            </div>
          </div>

          {{-- Water Usage --}}
          <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Water Usage Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Opening Reading:</span>
                <div class="font-medium">{{ number_format($b->openingReadingUnit) }} units</div>
              </div>
              <div>
                <span class="text-gray-600">Current Reading:</span>
                <div class="font-medium">{{ number_format($b->readingUnit) }} units</div>
              </div>
              <div>
                <span class="text-gray-600">Usage:</span>
                <div class="font-bold text-blue-600">{{ number_format($usage) }} units</div>
              </div>
              <div>
                <span class="text-gray-600">Unit Price:</span>
                <div class="font-medium">Rs {{ number_format($unitPrice, 2) }}</div>
              </div>
            </div>
          </div>

          {{-- Bill Breakdown --}}
          <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Bill Breakdown</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Sewerage Charge:</span>
                <span class="font-medium">Rs {{ number_format($sewerage, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Service Charge:</span>
                <span class="font-medium">Rs {{ number_format($service, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Water Usage ({{ $usage }} × Rs {{ number_format($unitPrice, 2) }}):</span>
                <span class="font-medium">Rs {{ number_format($usage * $unitPrice, 2) }}</span>
              </div>
              <hr class="my-2">
              <div class="flex justify-between text-lg font-bold">
                <span>Total Bill Amount:</span>
                <span class="text-green-600">Rs {{ number_format($b->billAmount, 2) }}</span>
              </div>
            </div>
          </div>

          {{-- Payment Status --}}
          <div class="bg-yellow-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Payment Information</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Amount Paid:</span>
                <span class="font-medium text-green-600">Rs {{ number_format($b->paidAmount, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Remaining Balance:</span>
                <span class="font-medium text-red-600">Rs {{ number_format($balance, 2) }}</span>
              </div>
              @if($b->paymentMethod)
                <div class="flex justify-between">
                  <span>Payment Method:</span>
                  <span class="font-medium uppercase">{{ $b->paymentMethod }}</span>
                </div>
              @endif
            </div>
          </div>

          {{-- Receipt --}}
          @if($b->recipt)
            <div class="text-center">
              <a href="{{ asset('storage/'.$b->recipt) }}" target="_blank"
                 class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                📄 View Receipt
              </a>
            </div>
          @endif
        </div>
      </x-modal>

      {{-- Payment History Modal --}}
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
                        <span class="px-2 py-1 text-xs rounded bg-gray-100">
                          {{ ucfirst($payment->method) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded {{ $payment->status === 'approval' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                          {{ ucfirst($payment->status) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                          {{ ucfirst($payment->paymenttype) }}
                        </span>
                      </td>
                      <td class="px-3 py-2">
                        @if($payment->recipt)
                          <a href="{{ asset('storage/' . $payment->recipt) }}" target="_blank"
                             class="text-blue-600 hover:underline text-xs">Receipt</a>
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
                  <strong>{{ $b->payments->where('status', 'pending')->count() }}</strong>
                  payment(s) awaiting approval
                </p>
              </div>
            @endif
          </div>
        </x-modal>
      @endif
  @endforeach
@endif

{{-- Table Summary --}}
@if(isset($bills) && $bills->count() > 0)
  @php
    $currentPageBills = $bills->getCollection();
    $totalBillAmount = $currentPageBills->sum('billAmount');
    $totalPaidAmount = $currentPageBills->sum('paidAmount');
    $totalBalance = $currentPageBills->sum(function($bill) {
      return max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
    });
  @endphp

@endif

@if(isset($bills))
  <div class="mt-3">{{ $bills->links() }}</div>
@endif
@endsection
