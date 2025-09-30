{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Admin' }}</title>
  <style>[x-cloak]{ display:none !important; }</style>
  @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 text-gray-900">
<div class="min-h-screen flex" x-data="{ openSidebar:false }">

  @php
    $user  = auth()->user();
    $role  = $user?->role ?? 'Guest';
    $brand = $role === 'Admin' ? 'Housing Admin' : 'Housing';

    $active = fn (string $name) => request()->routeIs($name) ? 'bg-gray-100 font-medium' : '';

    // MENUS BY ROLE (Houseowner: ONLY My Bills)
    $menus = [
      'Admin' => [
        ['Dashboard',                 'admin.dashboard.index', 'single', '📊'],
        ['Houses',                    'admin.houses.index', 'single', '🏠'],
        ['Shops',                     'admin.shops.index', 'single', '🏪'],
        ['Income', '', 'parent', '💰', [
          ['House Charges',           'admin.house-bills.index', '🏘️'],
          ['Shop Rentals',            'admin.shop-rentals.index', '🏬'],
          ['Inventory Sales',         'admin.inventory-sales.index', '📦'],
        ]],
        ['Expense', '', 'parent', '💸', [
          ['Payroll',                 'admin.payroll.index', '👥'],
          ['Other Expenses',          'admin.expenses.index', '💳'],
        ]],
        ['Reports',                   'admin.reports.index', 'single', '📈'],
        ['Settings',                  'admin.settings.edit', 'single', '⚙️'],
        ['Users',                     'admin.users.index', 'single', '👤'],
      ],
      'Houseowner' => [
        ['My Bills',                  'customer.bills.index', 'single', '📄'],
      ],
      'Merchant' => [
        ['My Shop Rentals',           'merchant.rentals.index', 'single', '🏬'],
      ],
    ];

    $navItems = $menus[$role] ?? [];
    $showFilters = ($role === 'Admin'); // hide filters for Houseowner/Merchant
  @endphp

  {{-- ============ Sidebar ============ --}}
  <aside
    class="fixed inset-y-0 left-0 w-64 bg-white border-r z-40 transform transition-transform duration-200 md:static md:translate-x-0"
    :class="openSidebar ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

    <div class="p-4 font-bold text-lg">{{ $brand }}</div>

    <nav class="flex-1 px-2 space-y-1" x-data="{ 
      openMenus: {
        income: {{ request()->routeIs('admin.house-bills.*', 'admin.shop-rentals.*', 'admin.inventory-sales.*') ? 'true' : 'false' }},
        expense: {{ request()->routeIs('admin.payroll.*', 'admin.expenses.*') ? 'true' : 'false' }}
      }
    }">
      @foreach($navItems as $item)
        @if(($item[2] ?? 'single') === 'parent')
          {{-- Parent menu with children --}}
          <div class="space-y-1">
            <button 
              @click="openMenus.{{ strtolower($item[0]) }} = !openMenus.{{ strtolower($item[0]) }}"
              class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-100 text-left font-medium">
              <span class="flex items-center">
                <span class="mr-3 text-lg">{{ $item[3] ?? '📁' }}</span>
                {{ $item[0] }}
              </span>
              <svg class="w-4 h-4 transition-transform" 
                   :class="openMenus.{{ strtolower($item[0]) }} ? 'rotate-90' : ''"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
            
            <div x-show="openMenus.{{ strtolower($item[0]) }}" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="ml-4 space-y-1">
              @foreach($item[4] as $subItem)
                <a href="{{ route($subItem[1]) }}"
                   class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-100 text-sm {{ $active($subItem[1]) }}">
                  <span class="mr-3 text-base">{{ $subItem[2] ?? '•' }}</span>
                  {{ $subItem[0] }}
                </a>
              @endforeach
            </div>
          </div>
        @else
          {{-- Single menu item --}}
          <a href="{{ route($item[1]) }}"
             class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-100 {{ $active($item[1]) }}">
            <span class="mr-3 text-lg">{{ $item[3] ?? '📄' }}</span>
            {{ $item[0] }}
          </a>
        @endif
      @endforeach

      {{-- Logout (shown for all roles) --}}
      <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t pt-4">
        @csrf
        <button type="submit" class="w-full flex items-center text-left px-3 py-2 rounded-lg hover:bg-gray-100">
          <span class="mr-3 text-lg">🚪</span>
          Logout
        </button>
      </form>
    </nav>
  </aside>

  {{-- Mobile overlay for sidebar --}}
  <div x-cloak x-show="openSidebar" @click="openSidebar=false"
       class="fixed inset-0 bg-black/40 z-30 md:hidden"></div>

  {{-- ============ Main ============ --}}
  <main class="flex-1">
    {{-- Topbar --}}
    <div class="sticky top-0 bg-white border-b px-4 py-3 flex items-center gap-3 z-50">
      {{-- Hamburger (mobile) --}}
      <button type="button" class="md:hidden p-2 rounded border"
              @click="openSidebar = true" aria-label="Open menu">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      {{-- Filters (Admins only) --}}
      @if($showFilters)
        <div class="relative md:static" x-data="{ openFilters:false }">
          <button type="button"
                  class="md:hidden p-2 rounded border"
                  @click="openFilters = !openFilters"
                  aria-expanded="false" aria-controls="filter-panel">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12M10 19h8"/>
            </svg>
          </button>

          <div id="filter-panel"
               x-cloak
               x-show="openFilters"
               @click.outside="openFilters=false"
               class="absolute left-0 top-full mt-2 w-72 rounded-lg border bg-white p-3 shadow-xl z-50
                      md:static md:mt-0 md:w-auto md:p-0 md:shadow-none md:border-0 md:block md:!visible">
            <form method="get" class="flex flex-col md:flex-row md:items-center gap-2">
              <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="rounded border-gray-300">
              <input type="date"  name="from"  value="{{ request('from') }}" class="rounded border-gray-300">
              <input type="date"  name="to"    value="{{ request('to') }}"   class="rounded border-gray-300">
              <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Apply</button>
            </form>
          </div>
        </div>
      @endif

      {{-- Right side: user + logout --}}
      <div class="ml-auto flex items-center gap-3">
        @auth
          <span class="hidden sm:inline text-sm text-gray-600">
            {{ auth()->user()->name }}
            <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
              {{ $role }}
            </span>
          </span>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="px-3 py-2 rounded-lg border hover:bg-gray-100 text-sm">
              Logout
            </button>
          </form>
        @endauth
      </div>
    </div>

    {{-- Content --}}
    <div class="p-4">
      @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
      @if($errors->any())     <x-alert type="error">{{ $errors->first() }}</x-alert>   @endif

      {{ $slot ?? '' }}
      @yield('content')
    </div>
  </main>
</div>

@vite('resources/js/app.js')
</body>
</html>
