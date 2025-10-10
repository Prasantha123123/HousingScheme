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

  <label class="block">
    <span class="text-sm text-gray-700">Status</span>
    <select name="status" class="mt-1 rounded border-gray-300 w-full">
      <option value="">All Status</option>
      @foreach(['Pending','InProgress','Approved','Rejected'] as $s)
        <option @selected(request('status')===$s)>{{ $s }}</option>
      @endforeach
    </select>
  </label>

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

      {{-- Payment History Modal --}}
      @if($r->payments && $r->payments->count() > 0)
        <x-modal :name="'payments-'.$r->id" :title="'Payment History - Rental #'.$r->id">
          <div class="space-y-3">
            <div class="text-sm text-gray-600">
              <strong>Shop:</strong> {{ $r->shopNumber }} | <strong>Month:</strong> {{ $r->month }}
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
                  @foreach($r->payments->sortByDesc('customerPaidAt') as $payment)
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
                          <form method="post" action="{{ route('admin.shop-rentals.approve', $r->id) }}" class="inline ml-2">
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
            
            @if($r->payments->where('status', 'pending')->count() > 0)
              <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                  <strong>{{ $r->payments->where('status', 'pending')->count() }}</strong> 
                  payment(s) awaiting approval
                </p>
              </div>
            @endif
          </div>
        </x-modal>
      @endif
    @empty
      <tr><td class="px-3 py-6 text-gray-500 text-center" colspan="9">No data</td></tr>
    @endforelse
  </x-table>
</div>

@if(isset($rows))
  <div class="mt-3">{{ $rows->links() }}</div>
@endif
@endsection
