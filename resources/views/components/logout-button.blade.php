@props([
    'label' => 'Logout',
    'class' => 'px-3 py-2 rounded-lg border hover:bg-gray-100 text-sm'
])

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" {{ $attributes->merge(['class' => $class]) }}>
        {{ $label }}
    </button>
</form>
