<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brochure->title }} · PMB Politeknik Mitra Industri</title>
    @include('partials.favicon')

    <meta name="description" content="{{ $brochure->description ?: 'Brosur Pendaftaran Mahasiswa Baru Politeknik Mitra Industri.' }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/brochure-viewer.js'])
</head>
<body class="bg-polmind-bg text-slate-900 antialiased">

    @php
        $fileUrl = route('brochures.file', $brochure);
    @endphp

    {{-- Toolbar --}}
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" aria-label="Kembali ke beranda PMB"
               class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-600 transition hover:bg-blue-50 hover:text-polmind-blue">
                <x-icon name="arrow-left" class="h-5 w-5" stroke-width="2" />
            </a>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-bold text-slate-900 sm:text-base">{{ $brochure->title }}</h1>
                <p class="truncate text-xs text-slate-500">
                    {{ $brochure->extension }} · {{ $brochure->size_label }}<span data-page-count></span>
                </p>
            </div>

            <a href="{{ route('brochures.download', $brochure) }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-polmind-border bg-white px-3 py-2.5 text-sm font-bold text-polmind-blue shadow-sm transition hover:bg-blue-50 sm:px-4">
                <x-icon name="download" class="h-4 w-4" stroke-width="2" />
                <span class="hidden sm:inline">Unduh</span>
                <span class="sr-only sm:hidden">Unduh brosur</span>
            </a>
            <a href="{{ url('/register') }}"
               class="hidden shrink-0 rounded-xl bg-polmind-yellow px-4 py-2.5 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95 sm:inline-flex">
                Daftar Sekarang
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
        @if($brochure->description)
            <p class="mx-auto mb-5 max-w-3xl text-sm leading-6 text-slate-600">{{ $brochure->description }}</p>
        @endif

        {{-- Brosur lain --}}
        @if($brochures->count() > 1)
            <nav class="mx-auto mb-6 flex max-w-3xl gap-2 overflow-x-auto pb-1" aria-label="Pilih brosur">
                @foreach($brochures as $item)
                    <a href="{{ route('brochures.show', $item) }}"
                       @if($item->is($brochure)) aria-current="page" @endif
                       class="shrink-0 rounded-full px-4 py-2 text-xs font-bold transition {{ $item->is($brochure) ? 'bg-polmind-blue text-white' : 'border border-polmind-border bg-white text-slate-700 hover:border-polmind-blue hover:text-polmind-blue' }}">
                        {{ $item->title }}
                    </a>
                @endforeach
            </nav>
        @endif

        {{-- Preview --}}
        @if($brochure->isPdf())
            <div data-pdf-viewer data-src="{{ $fileUrl }}" class="mx-auto max-w-3xl">
                <div data-status class="flex min-h-[60vh] flex-col items-center justify-center gap-3 rounded-2xl bg-white p-8 text-center shadow-sm">
                    <span class="h-8 w-8 animate-spin rounded-full border-4 border-blue-100 border-t-polmind-blue" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-slate-600">Memuat brosur...</p>
                </div>
                <div data-pages class="space-y-4 sm:space-y-6"></div>

                <template data-error>
                    <div class="flex min-h-[40vh] flex-col items-center justify-center gap-4 rounded-2xl bg-white p-8 text-center shadow-sm">
                        <p class="text-sm font-semibold text-slate-700">Brosur tidak dapat ditampilkan di perangkat ini.</p>
                        <a href="{{ route('brochures.download', $brochure) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                            <x-icon name="download" class="h-4 w-4" stroke-width="2" />
                            Unduh Brosur
                        </a>
                    </div>
                </template>
            </div>
        @else
            <img src="{{ $fileUrl }}" alt="{{ $brochure->title }}"
                 class="mx-auto w-full max-w-3xl rounded-lg bg-white shadow-md ring-1 ring-slate-200">
        @endif

        {{-- Ajakan setelah membaca --}}
        <section class="mx-auto mt-8 flex max-w-3xl flex-col items-start justify-between gap-5 rounded-2xl bg-polmind-blue-dark p-6 text-white sm:flex-row sm:items-center sm:p-8">
            <div>
                <h2 class="text-lg font-bold">Siap bergabung dengan Polmind?</h2>
                <p class="mt-1 text-sm leading-6 text-blue-100">Daftar online sekarang atau konsultasi dulu dengan admin PMB.</p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <a href="{{ \App\Services\AdminWhatsApp::url() }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-bold text-white ring-1 ring-white/30 transition hover:bg-white/20">
                    Konsultasi WA
                </a>
                <a href="{{ url('/register') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark transition hover:brightness-95">
                    Daftar Sekarang
                </a>
            </div>
        </section>
    </main>
</body>
</html>
