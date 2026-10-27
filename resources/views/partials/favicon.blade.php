{{-- Favicon lambang Polmind. ?v= memaksa browser memuat ulang saat file ikon diganti. --}}
@php
    $iconVersion = fn (string $file) => asset($file) . '?v=' . (@filemtime(public_path($file)) ?: '1');
@endphp
<link rel="icon" href="{{ $iconVersion('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="192x192" href="{{ $iconVersion('favicon-192.png') }}">
<link rel="apple-touch-icon" href="{{ $iconVersion('apple-touch-icon.png') }}">
<meta name="theme-color" content="#003B82">
