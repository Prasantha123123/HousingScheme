@props(['type'=>'info'])
@php $styles = [
  'success'=>'bg-green-50 text-green-800 border-green-200',
  'error'=>'bg-red-50 text-red-800 border-red-200',
  'info'=>'bg-blue-50 text-blue-800 border-blue-200',
]; @endphp
<div {{ $attributes->merge(['class'=>"mb-3 rounded-lg border px-3 py-2 ".$styles[$type]]) }}>
  {{ $slot }}
</div>
