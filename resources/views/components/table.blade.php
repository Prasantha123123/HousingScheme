<div class="overflow-auto rounded-lg border bg-white">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50 sticky top-0 z-10">
      <tr>{{ $head }}</tr>
    </thead>
    <tbody class="divide-y">
      {{ $slot }}
    </tbody>
  </table>
</div>
