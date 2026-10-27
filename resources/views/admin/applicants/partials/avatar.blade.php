{{-- Pas foto camaba, atau inisial berwarna jika belum ada / gagal dimuat. --}}
@if($row['photo'])
    <img src="{{ $row['photo'] }}" alt="Pas foto" loading="lazy"
         onerror="this.hidden = true; this.nextElementSibling.hidden = false"
         class="{{ $size }} shrink-0 rounded-md border border-slate-200 bg-slate-100 object-cover">
@endif
<div class="{{ $size }} {{ $row['color'] }} flex shrink-0 items-center justify-center rounded-md font-medium text-white [&[hidden]]:hidden"
     aria-hidden="true"
     @if($row['photo']) hidden @endif>
    {{ $row['initials'] }}
</div>
