{{-- resources/views/shop/dashboard.blade.php --}}
@extends('layouts.app', ['title' => 'Shop Dashboard'])

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Shop Dashboard</h1>
        <p class="mt-2 text-gray-600">Welcome to your shop portal, {{ $shop->shopNumber }}</p>
    </div>

    {{-- Shop Information Card --}}
    <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Shop Information</h3>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Shop Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->shopNumber }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        @if(is_null($shop->MerchantId))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Self-Managed
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Merchant Assigned
                            </span>
                        @endif
                    </dd>
                </div>
                @if($shop->rentalAmount)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Rental Amount</dt>
                    <dd class="mt-1 text-sm text-gray-900">${{ number_format($shop->rentalAmount, 2) }}</dd>
                </div>
                @endif
                @if($shop->leaseEnd)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Lease End</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->leaseEnd }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {{-- View Rentals --}}
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                View Rentals
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                Check your rental charges
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('shop.rentals') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        View all rentals →
                    </a>
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Need Help?
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                Contact Admin
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-sm text-gray-500">
                        Call admin for assistance
                    </span>
                </div>
            </div>
        </div>

        {{-- Logout --}}
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Logout
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                End your session
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">
                            Logout →
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection