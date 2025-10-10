@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
  <h1 class="text-xl font-semibold">Shop Rentals</h1>
  {{-- <a href="{{ route('admin.shops.create') }}" class="px-3 py-2 bg-gray-900 text-white rounded-lg">Add Shop</a> --}}
</div>

{{-- Filters (labels + real placeholders, UI unchanged otherwise) --}}
<form method="get" class="bg-white rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-3">

  <label class="block">
    <span class="text-sm text-gray-700">Month</span>
    <input
      class="mt-1 rounded border-gray-300 w-full"
      type="text"
      name="month"
      value="{{ request('month') }}"
      placeholder="-------- ----"
      autocomplete="off"
      onfocus="this.type='month'"
      onblur="if(!this.value) this.type='text'">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">From</span>
    <input
      class="mt-1 rounded border-gray-300 w-full"
      type="text"
      name="from_date"
      value="{{ request('from_date') }}"
      placeholder="mm/dd/yyyy"
      autocomplete="off"
      onfocus="this.type='date'"
      onblur="if(!this.value) this.type='text'">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">To</span>
    <input
      class="mt-1 rounded border-gray-300 w-full"
      type="text"
      name="to_date"
      value="{{ request('to_date') }}"
      placeholder="mm/dd/yyyy"
      autocomplete="off"
      onfocus="this.type='date'"
      onblur="if(!this.value) this.type='text'">
  </label>

  <label class="block">
    <span class="text-sm text-gray-700">Shop No</span>
    <input
      class="mt-1 rounded border-gray-300 w-full"
      type="text"
      name="shopNumber"
      placeholder="Shop No"
      value="{{ request('shopNumber') }}">
  </label>
{{-- 
  <label class="block">
    <span class="text-sm text-gray-700">Status</span>
    <select name="status" class="mt-1 rounded border-gray-300 w-full">
      <option value="">All Status</option>
      @foreach(['Pending','InProgress','Approved','Rejected'] as $s)
        <option @selected(request('status')===$s)>{{ $s }}</option>
      @endforeach
    </select>
  </label> --}}

  <label class="block">
    <span class="text-sm text-gray-700">Method</span>
    <select name="method" class="mt-1 rounded border-gray-300 w-full">
      <option value="">Any Method</option>
      @foreach (['cash','card','online'] as $m)
        <option value="{{ $m }}" @selected(request('method')===$m)>{{ ucfirst($m) }}</option>
      @endforeach
    </select>
  </label>

  <div class="flex gap-2 col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-6">
    <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Filter</button>
    <a href="{{ route('admin.shop-rentals.pdf', request()->query()) }}"
       class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-center whitespace-nowrap">
      📄 PDF
    </a>
  </div>
</form>

{{-- ===== Mobile: cards ===== --}}
<div class="sm:hidden space-y-3">
  @forelse($rows ?? [] as $r)
    @php
      $balance = max(0, (float)$r->billAmount - (float)$r->paidAmount);
    @endphp
    <div class="rounded-lg border bg-white p-3 shadow-sm">
      <div class="flex items-start justify-between gap-2">
        <div>
          <div class="text-xs text-gray-500">Shop No</div>
          <div class="font-medium">{{ $r->shopNumber }}</div>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-500">Month</div>
          <div class="font-medium">{{ $r->month }}</div>
        </div>
      </div>

      <div class="mt-2">
        <div class="text-xs text-gray-500">Merchant</div>
        <div class="text-sm">{{ $r->merchant_name ?? '-' }}</div>
      </div>

      <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
        <div>
          <div class="text-gray-500">Bill</div>
          <div class="font-medium">{{ number_format($r->billAmount,2,'.', ',') }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Paid</div>
          <div class="font-medium">{{ number_format($r->original_payment_amount ?: $r->paidAmount, 2, '.', ',') }}</div>
        </div>
        <div>
          <div class="text-gray-500">Balance</div>
          <div class="font-medium {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($balance,2,'.', ',') }}</div>
        </div>
        <div class="text-right">
          <div class="text-gray-500">Method</div>
          <div class="uppercase">{{ $r->paymentMethod ?: '-' }}</div>
        </div>
        <div class="col-span-2 text-center">
          <div class="text-gray-500">Status</div>
          <x-badge :status="$r->status"/>
        </div>
      </div>

      <div class="mt-2">
        @if($r->recipt)
          <a target="_blank" class="text-blue-600 hover:underline text-sm" href="{{ asset('storage/'.$r->recipt) }}">Open receipt</a>
        @endif
      </div>

      <div class="mt-3 flex items-center justify-end gap-3">
        {{-- Payment History Button --}}
        @if($r->payments && $r->payments->count() > 0)
          <button type="button" class="px-2 py-1 text-purple-600" x-data @click="$dispatch('open-modal','payments-{{ $r->id }}')">
            Payments ({{ $r->payments->count() }})
          </button>
        @endif

        @if($r->status === 'Approved')
          <button class="px-2 py-1 text-green-700 opacity-40 cursor-not-allowed" disabled>Approve</button>
        @else
          @if(empty($r->paymentMethod) || $r->status === 'PartPayment')
            <button type="button" class="px-2 py-1 text-green-700" x-data @click="$dispatch('open-modal','approve-{{ $r->id }}')">
              Approve
            </button>
          @else
            @php
              // Calculate outstanding balance for cash payment auto-fill
              $carry = $rows->where('shopNumber', $r->shopNumber)
                           ->where('month', '<', $r->month)
                           ->sum(fn($rental) => max(0, (float)$rental->billAmount - (float)$rental->paidAmount));
              $totalDue = (float)$r->billAmount + $carry;
              $outstanding = max(0, $totalDue - (float)$r->paidAmount);
              $cashAmount = min((float)$r->billAmount, $outstanding); // Cap to outstanding for cash
            @endphp
            <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">
              @csrf
              <input type="hidden" name="paymentMethod" value="{{ $r->paymentMethod }}">
              @if($r->paymentMethod === 'cash' && (float)$r->paidAmount <= 0)
                <input type="hidden" name="paidAmount" value="{{ $cashAmount }}">
              @endif
              <button class="px-2 py-1 text-green-700">Approve</button>
            </form>
          @endif
        @endif

        @if($r->status !== 'Approved')
          <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">
            @csrf
            <button class="px-2 py-1 text-red-700">Reject</button>
          </form>
        @endif
      </div>
    </div>

    @if($r->status !== 'Approved' && (empty($r->paymentMethod) || $r->status === 'PartPayment'))
      @php
        $maxPayable = $r->maxPayable ?? 0;
      @endphp
      <x-modal :name="'approve-'.$r->id" :title="'Approve Rental #'.$r->id">
        <div x-data="{ paidAmount: '{{ number_format($maxPayable, 2, '.', '') }}', maxAmount: {{ $maxPayable }} }">
          <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="paymentMethod" value="{{ $r->paymentMethod ?: 'cash' }}">
            <p class="text-sm text-gray-600">
              Recording a <span class="font-medium">{{ $r->paymentMethod ?: 'cash' }}</span> payment.
              @if($r->status === 'PartPayment')
                <br><span class="text-orange-600">Additional payment for partial rental.</span>
              @endif
              <br><span class="text-sm text-gray-500">Remaining balance to pay: Rs {{ number_format($maxPayable, 2) }}</span>
            </p>
            <label class="block">
              <span class="text-sm">Paid Amount</span>
              <input type="number" name="paidAmount" step="0.01" min="0" max="{{ $maxPayable }}"
                     x-model="paidAmount"
                     value="{{ old('paidAmount', number_format($maxPayable, 2, '.', '')) }}"
                     placeholder="Enter amount (max: {{ number_format($maxPayable, 2) }})"
                     class="mt-1 w-full rounded border-gray-300" required>
              <div x-show="parseFloat(paidAmount) > maxAmount" class="text-red-600 text-xs mt-1">
                Payment amount cannot exceed the remaining balance of Rs {{ number_format($maxPayable, 2) }}
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
  @empty
    <div class="rounded-lg border bg-white p-4 text-gray-500">No data</div>
  @endforelse
</div>

{{-- ===== Tablet / Desktop: table ===== --}}
<div class="hidden sm:block overflow-x-auto -mx-4 md:mx-0">
  <x-table class="table-fixed w-full">
    <x-slot:head>
      <th class="px-3 py-2 text-left  w-32">Shop No</th>
      <th class="px-3 py-2 text-left  w-28">Month</th>
      <th class="px-3 py-2 text-right w-28">Bill</th>
      <th class="px-3 py-2 text-right w-28">Paid</th>
      <th class="px-3 py-2 text-right w-28">Balance</th>
      <th class="px-3 py-2 text-center w-28 hidden lg:table-cell">Method</th>
      <th class="px-3 py-2 text-center w-28 hidden lg:table-cell">Receipt</th>
      <th class="px-3 py-2 text-center w-28">Status</th>
      <th class="px-3 py-2 w-32">Action</th>
    </x-slot:head>

    @forelse($rows ?? [] as $r)
      @php
        $balance = max(0, (float)$r->billAmount - (float)$r->paidAmount);
        // Show monthly collection amount if this bill matches the collection month
        $monthlyCollected = ($r->collection_month === $r->month) ? (float)$r->monthly_collection_amount : 0;
      @endphp
      <tr class="hover:bg-gray-50 align-middle">
        <td class="px-3 py-2 w-32">{{ $r->shopNumber }}</td>
        <td class="px-3 py-2 w-28">{{ $r->month }}</td>
        <td class="px-3 py-2 text-right w-28">{{ number_format($r->billAmount,2,'.', ',') }}</td>
        <td class="px-3 py-2 text-right w-28">{{ number_format($r->original_payment_amount ?: $r->paidAmount, 2, '.', ',') }}</td>
        <td class="px-3 py-2 text-right w-28 {{ $balance > 0 ? 'text-red-600 font-medium' : 'text-green-600' }}">{{ number_format($balance,2,'.', ',') }}</td>
        <td class="px-3 py-2 text-center w-28 hidden lg:table-cell uppercase">
          {{ $r->paymentMethod ?: '-' }}
        </td>
        <td class="px-3 py-2 text-center w-28 hidden lg:table-cell">
          @if($r->recipt)
            <a target="_blank" class="text-blue-600 hover:underline" href="{{ asset('storage/'.$r->recipt) }}">Open</a>
          @else
            <span class="text-gray-400">-</span>
          @endif
        </td>
        <td class="px-3 py-2 text-center w-28">
          <x-badge :status="$r->status"/>
        </td>
        <td class="px-3 py-2 w-32 text-right whitespace-nowrap">
          <button type="button" class="text-blue-600 mr-2" x-data @click="$dispatch('open-modal','view-rental-{{ $r->id }}')">
            View
          </button>
          @if($r->status === 'Approved')
            <button class="text-green-700 opacity-50 cursor-not-allowed" disabled>Approve</button>
          @else
            @if(empty($r->paymentMethod) || $r->status === 'PartPayment')
              <button type="button" class="text-green-700" x-data @click="$dispatch('open-modal','approve-{{ $r->id }}')">
                Approve
              </button>
            @else
              @php
                // Calculate outstanding balance for cash payment auto-fill
                $carry = $rows->where('shopNumber', $r->shopNumber)
                             ->where('month', '<', $r->month)
                             ->sum(fn($rental) => max(0, (float)$rental->billAmount - (float)$rental->paidAmount));
                $totalDue = (float)$r->billAmount + $carry;
                $outstanding = max(0, $totalDue - (float)$r->paidAmount);
                $cashAmount = min((float)$r->billAmount, $outstanding); // Cap to outstanding for cash
              @endphp
              <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="inline">
                @csrf
                <input type="hidden" name="paymentMethod" value="{{ $r->paymentMethod }}">
                @if($r->paymentMethod === 'cash' && (float)$r->paidAmount <= 0)
                  <input type="hidden" name="paidAmount" value="{{ $cashAmount }}">
                @endif
                <button class="text-green-700">Approve</button>
              </form>
            @endif
          @endif

          @if($r->status !== 'Approved')
            <span class="mx-2 text-gray-300">|</span>
            <form method="post" action="{{ route('admin.shop-rentals.reject',$r->id) }}" class="inline">
              @csrf
              <button class="text-red-700">Reject</button>
            </form>
          @endif
        </td>
      </tr>

      @if($r->status !== 'Approved' && (empty($r->paymentMethod) || $r->status === 'PartPayment'))
        @php
          $maxPayable = $r->maxPayable ?? 0;
        @endphp
        <x-modal :name="'approve-'.$r->id" :title="'Approve Rental #'.$r->id">
          <div x-data="{ paidAmount: '{{ number_format($maxPayable, 2, '.', '') }}', maxAmount: {{ $maxPayable }} }">
            <form method="post" action="{{ route('admin.shop-rentals.approve',$r->id) }}" class="space-y-3">
              @csrf
              <input type="hidden" name="paymentMethod" value="{{ $r->paymentMethod ?: 'cash' }}">
              <p class="text-sm text-gray-600">
                Recording a <span class="font-medium">{{ $r->paymentMethod ?: 'cash' }}</span> payment.
                @if($r->status === 'PartPayment')
                  <br><span class="text-orange-600">Additional payment for partial rental.</span>
                @endif
                <br><span class="text-sm text-gray-500">Remaining balance to pay: Rs {{ number_format($maxPayable, 2) }}</span>
              </p>
              <label class="block">
                <span class="text-sm">Paid Amount</span>
                <input type="number" name="paidAmount" step="0.01" min="0" max="{{ $maxPayable }}"
                       x-model="paidAmount"
                       value="{{ old('paidAmount', number_format($maxPayable, 2, '.', '')) }}"
                       placeholder="Enter amount (max: {{ number_format($maxPayable, 2) }})"
                       class="mt-1 w-full rounded border-gray-300" required>
                <div x-show="parseFloat(paidAmount) > maxAmount" class="text-red-600 text-xs mt-1">
                  Payment amount cannot exceed the remaining balance of Rs {{ number_format($maxPayable, 2) }}
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

      {{-- View Rental Modal --}}
      <x-modal :name="'view-rental-'.$r->id" :title="'Rental Details - Shop #'.$r->shopNumber">
        <div class="space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-700">Shop Number:</span>
                <div class="text-lg font-bold">{{ $r->shopNumber }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Billing Month:</span>
                <div class="text-lg font-bold">{{ $r->month }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Bill Date:</span>
                <div>{{ $r->timestamp ? $r->timestamp->format('M j, Y') : '-' }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">Status:</span>
                <div><x-badge :status="$r->status"/></div>
              </div>
            </div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Bill Breakdown</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Rental Amount:</span>
                <span class="font-medium">Rs {{ number_format($r->billAmount, 2) }}</span>
              </div>
              <hr class="my-2">
              <div class="flex justify-between text-lg font-bold">
                <span>Total Bill Amount:</span>
                <span class="text-green-600">Rs {{ number_format($r->billAmount, 2) }}</span>
              </div>
            </div>
          </div>
          <div class="bg-yellow-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-3">Payment Information</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Amount Paid:</span>
                <span class="font-medium text-green-600">Rs {{ number_format($r->paidAmount, 2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Remaining Balance:</span>
                <span class="font-medium text-red-600">Rs {{ number_format($balance, 2) }}</span>
              </div>
              @if($r->paymentMethod)
                <div class="flex justify-between">
                  <span>Payment Method:</span>
                  <span class="font-medium uppercase">{{ $r->paymentMethod }}</span>
                </div>
              @endif
            </div>
          </div>
          @if($r->recipt)
            <div class="text-center">
              <a href="{{ asset('storage/'.$r->recipt) }}" target="_blank"
                 class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                📄 View Receipt
              </a>
            </div>
          @endif
        </div>
      </x-modal>

      {{-- Payment History Modal --}}
      {{-- Payment history is now only shown in modals, not as a table row. --}}
    @empty
  <tr><td class="px-3 py-6 text-gray-500 text-center" colspan="9">No bills found</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
