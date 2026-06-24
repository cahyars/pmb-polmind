<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Camaba PMB Polmind')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-polmind-bg text-slate-900 antialiased">

<div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">

    <aside class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-slate-200 bg-white transition-transform duration-300 lg:static lg:translate-x-0"
           :class="{ 'translate-x-0': sidebarOpen }">

        <div class="flex h-full flex-col">
            <div class="px-6 py-7">
                <a href="{{ url('/camaba/dashboard') }}" class="text-3xl font-bold text-polmind-blue">
                    Polmind
                </a>
                <p class="mt-1 text-sm text-slate-600">Portal Mahasiswa</p>
            </div>

            <nav class="flex-1 space-y-1 px-4">
                @php
                    $menus = [
                        ['label' => 'Dashboard', 'url' => '/camaba/dashboard', 'icon' => '▦'],
                        ['label' => 'Biodata', 'url' => '/camaba/biodata', 'icon' => '♙'],
                        ['label' => 'Berkas', 'url' => '/camaba/berkas', 'icon' => '▱'],
                        ['label' => 'Pembayaran', 'url' => '/camaba/pembayaran', 'icon' => '▤'],
                        ['label' => 'Status Seleksi', 'url' => '/camaba/status-seleksi', 'icon' => '☑'],
                        ['label' => 'Pengumuman', 'url' => '/camaba/pengumuman', 'icon' => '☊'],
                    ];
                @endphp

                @foreach ($menus as $menu)
                    @php
                        $active = request()->is(ltrim($menu['url'], '/') . '*');
                    @endphp

                    <a href="{{ url($menu['url']) }}"
                       class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
                       {{ $active ? 'bg-polmind-blue text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-polmind-blue' }}">
                        <span class="text-lg">{{ $menu['icon'] }}</span>
                        <span>{{ $menu['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="px-4 pb-6">
                <a href="https://wa.me/6281234567890"
                   target="_blank"
                   class="flex items-center justify-center rounded-xl bg-polmind-yellow px-4 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Hubungi Admin
                </a>
            </div>
        </div>
    </aside>

    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/40 lg:hidden"></div>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-polmind-bg/90 backdrop-blur">
            <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button"
                            class="rounded-lg border border-slate-200 bg-white p-2 lg:hidden"
                            @click="sidebarOpen = true">
                        ☰
                    </button>

                    <div>
                        <h1 class="text-lg font-bold text-polmind-blue sm:text-xl">
                            @yield('page_title', 'Portal Camaba')
                        </h1>
                        <p class="hidden text-sm text-slate-500 sm:block">
                            @yield('page_subtitle', 'Sistem Pendaftaran Mahasiswa Baru Polmind')
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-bold text-slate-900">Ahmad Fauzi</p>
                        <p class="text-xs text-slate-500">ID: PMB20240982</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-polmind-blue text-sm font-bold text-white">
                        AF
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-8 sm:px-6 lg:px-8">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 px-4 py-8 text-sm text-slate-500 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-3 md:flex-row">
                <p>
                    <span class="font-bold text-polmind-blue">Polmind</span> © {{ date('Y') }} Politeknik Mitra Industri.
                </p>
                <div class="flex gap-5">
                    <a href="#" class="hover:text-polmind-blue">Tentang Kami</a>
                    <a href="#" class="hover:text-polmind-blue">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-polmind-blue">Syarat & Ketentuan</a>
                </div>
            </div>
        </footer>
    </div>
</div>

</body>
</html>