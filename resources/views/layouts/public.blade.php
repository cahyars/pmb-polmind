<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PMB Politeknik Mitra Industri')</title>
    @include('partials.favicon')

    <meta name="description" content="@yield('meta_description', 'Sistem Pendaftaran Mahasiswa Baru Politeknik Mitra Industri')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-polmind-bg text-slate-900 antialiased">

    @php
        // Dropdown Prodi mengikuti Master Data (dikirim oleh HomeController).
        $navPrograms = collect($studyPrograms ?? [])
            ->map(fn ($program) => ['id' => 'prodi-' . strtolower($program->code), 'name' => $program->degree . ' ' . $program->name])
            ->all();
        $navLinks = [
            ['href' => '#jalur-masuk', 'label' => 'Jalur Masuk'],
            ['href' => '#biaya', 'label' => 'Biaya'],
            ['href' => '#alur', 'label' => 'Panduan'],
            ['href' => '#faq', 'label' => 'FAQ'],
        ];
    @endphp

    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center">
                <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri" class="h-11 w-auto">
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex" aria-label="Navigasi utama">
                <a href="{{ url('/') }}" class="font-black text-polmind-blue">Beranda</a>
                <a href="#jalur-masuk" class="transition hover:text-polmind-blue">Jalur Masuk</a>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button"
                            @click="open = ! open"
                            :aria-expanded="open"
                            class="inline-flex items-center gap-1.5 transition hover:text-polmind-blue">
                        Prodi
                        <svg class="h-4 w-4 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition.origin.top
                         x-cloak
                         class="absolute left-1/2 top-full mt-4 w-80 -translate-x-1/2 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-300/40">
                        @foreach($navPrograms as $program)
                            <a href="#{{ $program['id'] }}"
                               @click="open = false"
                               class="block rounded-xl px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-blue-50 hover:text-polmind-blue">
                                {{ $program['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @foreach(array_slice($navLinks, 1) as $link)
                    <a href="{{ $link['href'] }}" class="transition hover:text-polmind-blue">{{ $link['label'] }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                <a href="{{ url('/login') }}"
                   class="rounded-xl bg-polmind-blue px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-polmind-blue-dark">
                    Masuk
                </a>
                <a href="{{ url('/register') }}"
                   class="rounded-xl bg-polmind-yellow px-5 py-2.5 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Daftar
                </a>
            </div>

            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-polmind-blue lg:hidden"
                    x-data
                    @click="$dispatch('toggle-mobile-menu')">
                <span class="sr-only">Buka menu</span>
                <x-icon name="menu" class="h-5 w-5" stroke-width="2" />
            </button>
        </div>

        <div x-data="{ open: false }"
             x-on:toggle-mobile-menu.window="open = !open"
             x-show="open"
             x-transition
             x-cloak
             @click="if ($event.target.closest('a')) open = false"
             class="border-t border-slate-200 bg-white lg:hidden">
            <div class="space-y-1 px-4 py-4">
                <a href="{{ url('/') }}" class="block rounded-lg px-3 py-2 text-sm font-bold text-polmind-blue">Beranda</a>
                <a href="#jalur-masuk" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700">Jalur Masuk</a>
                <p class="px-3 pt-2 text-[11px] font-black uppercase tracking-wide text-slate-400">Prodi</p>
                @foreach($navPrograms as $program)
                    <a href="#{{ $program['id'] }}" class="block rounded-lg px-3 py-2 pl-6 text-sm font-medium text-slate-700">{{ $program['name'] }}</a>
                @endforeach
                @foreach(array_slice($navLinks, 1) as $link)
                    <a href="{{ $link['href'] }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700">{{ $link['label'] }}</a>
                @endforeach
                <div class="mt-3 flex gap-2">
                    <a href="{{ url('/login') }}" class="flex-1 rounded-xl border border-polmind-blue px-4 py-2 text-center text-sm font-bold text-polmind-blue">
                        Masuk
                    </a>
                    <a href="{{ url('/register') }}" class="flex-1 rounded-xl bg-polmind-yellow px-4 py-2 text-center text-sm font-bold text-polmind-blue-dark">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <h2 class="text-lg font-bold text-polmind-blue">Polmind</h2>
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-600">
                    Politeknik Mitra Industri adalah institusi pendidikan vokasi di bawah Yayasan Mitra Global Mandiri. Kami adalah Inkubator Talenta Global!
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-slate-900">Tautan Cepat</h3>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <a href="https://polmind.ac.id" target="_blank" class="block hover:text-polmind-blue">Website Polmind</a>
                    <a href="#" class="block hover:text-polmind-blue">Program Studi</a>
                    <a href="https://polmind.ac.id/pmb" target="_blank" class="block hover:text-polmind-blue">Informasi PMB</a>
                </div>
            </div>

            <div>
                <h3 class="font-semibold text-slate-900">Kontak Kami</h3>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <p class="leading-6">{{ config('pmb.campus_address') }}</p>
                    <p><a href="{{ \App\Services\AdminWhatsApp::url() }}" target="_blank" rel="noopener" class="hover:text-polmind-blue">{{ \App\Services\AdminWhatsApp::display() }}</a></p>
                    <p>info@polmind.ac.id</p>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 py-5">
            <div class="mx-auto flex max-w-7xl flex-col justify-between gap-3 px-4 text-xs text-slate-500 sm:px-6 md:flex-row lg:px-8">
                <p>© {{ date('Y') }} Politeknik Mitra Industri. All Rights Reserved.</p>
                <div class="flex items-center gap-5"
                     x-data
                     @keydown.window="if ($event.altKey && $event.shiftKey && $event.code === 'KeyA' && ! $event.target.closest('input, textarea, select, [contenteditable]')) window.location = $refs.adminLogin.href">
                    <a href="#" class="hover:text-polmind-blue">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-polmind-blue">Syarat & Ketentuan</a>
                    {{-- Shortcut login admin PMB (juga Alt+Shift+A) --}}
                    <a href="{{ route('admin.login') }}"
                       x-ref="adminLogin"
                       title="Login Admin PMB (Alt+Shift+A)"
                       aria-label="Login Admin PMB"
                       class="-m-1.5 rounded-full p-1.5 text-slate-300 transition hover:bg-blue-50 hover:text-polmind-blue focus-visible:text-polmind-blue focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200">
                        <x-icon name="lock" class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.whatsapp-float')

</body>
</html>