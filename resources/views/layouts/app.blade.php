{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Admin' }}</title>
  @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 text-gray-900">
<div class="min-h-screen flex">
  {{-- Sidebar --}}
  <aside class="w-64 bg-white border-r hidden md:flex flex-col">
    <div class="p-4 font-bold text-lg">Housing Admin</div>
    <nav class="flex-1 px-2 space-y-1">
      @php $nav = [
        ['Dashboard','admin.dashboard.index'],
        ['Houses','admin.houses.index'],
        ['Income · House Charges','admin.house-bills.index'],
        ['Income · Shop Rentals','admin.shop-rentals.index'],
        ['Income · Inventory Sales','admin.inventory-sales.index'],
        ['Expenses · Payroll','admin.payroll.index'],
        ['Expenses · Other','admin.expenses.index'],
        ['Reports','admin.reports.index'],
        ['Settings','admin.settings.edit'],
        ['Users','admin.users.index'],
      ]; @endphp
      @foreach($nav as [$label,$route])
        <a href="{{ route($route) }}" class="block px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->routeIs($route) ? 'bg-gray-100 font-medium' : '' }}">
          {{ $label }}
        </a>
      @endforeach
    </nav>

    {{-- Sidebar footer: Logout (added, keeps existing UI unchanged) --}}
    @auth
    <div class="px-2 pb-4 mt-auto border-t">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg border hover:bg-gray-50 text-sm">
          {{-- icon --}}
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" d="M3.75 4.5A2.25 2.25 0 0 1 6 2.25h6A2.25 2.25 0 0 1 14.25 4.5v2.25a.75.75 0 1 1-1.5 0V4.5a.75.75 0 0 0-.75-.75H6A.75.75 0 0 0 5.25 4.5v15A.75.75 0 0 0 6 20.25h6a.75.75 0 0 0 .75-.75V17.25a.75.75 0 1 1 1.5 0V19.5A2.25 2.25 0 0 1 12 21.75H6A2.25 2.25 0 0 1 3.75 19.5v-15Z" clip-rule="evenodd"/>
            <path d="M21 12a.75.75 0 0 0-.75-.75h-8.69l2.22-2.22a.75.75 0 1 0-1.06-1.06l-3.5 3.5a.75.75 0 0 0 0 1.06l3.5 3.5a.75.75 0 1 0 1.06-1.06l-2.22-2.22h8.69A.75.75 0 0 0 21 12Z"/>
          </svg>
          <span>Logout</span>
        </button>
      </form>
    </div>
    @endauth
  </aside>

  {{-- Main --}}
  <main class="flex-1">
    {{-- Topbar --}}
    <div class="sticky top-0 bg-white border-b px-4 py-3 flex items-center gap-3">
      <form method="get" class="flex items-center gap-2">
        <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="rounded border-gray-300">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300">
        <button class="px-3 py-2 bg-gray-900 text-white rounded-lg">Apply</button>
      </form>

    </div>

    <div class="p-4">
      @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
      @if($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif
      {{ $slot ?? '' }}
      @yield('content')
    </div>
  </main>
</div>

@vite('resources/js/app.js')
</body>
</html>
