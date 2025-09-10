@props(['name','accept'=>'application/pdf,image/png,image/jpeg','required'=>false])
<input {{ $required ? 'required' : '' }} type="file" name="{{ $name }}" accept="{{ $accept }}"
       class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2">
<p class="text-xs text-gray-500">PDF/JPG/PNG · ≤ 5MB</p>
