<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin PMB') - PMB Polmind</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-polmind-bg text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">

        @php
            $menus = [
                [
                    'label' => 'Dashboard',
                    'url' => '/admin/dashboard',
                    'icon' => '▦',
                    'active' => request()->is('admin/dashboard'),
                ],
                [
                    'label' => 'Data Camaba',
                    'url' => '/admin/applicants',
                    'icon' => '👤',
                    'active' => request()->is('admin/applicants*'),
                ],
                [
                    'label' => 'Verifikasi Berkas',
                    'url' => '/admin/documents',
                    'icon' => '📄',
                    'active' => request()->is('admin/documents*'),
                ],
                [
                    'label' => 'Verifikasi Pembayaran',
                    'url' => '/admin/payments',
                    'icon' => '💳',
                    'active' => request()->is('admin/payments*'),
                ],
                [
                    'label' => 'Seleksi',
                    'url' => '/admin/selections',
                    'icon' => '✅',
                    'active' => request()->is('admin/selections*'),
                ],
                [
                    'label' => 'Daftar Ulang',
                    'url' => '/admin/re-registrations',
                    'icon' => '🎓',
                    'active' => request()->is('admin/re-registrations*'),
                ],
                [
                    'label' => 'Follow Up',
                    'url' => '/admin/follow-ups',
                    'icon' => '💬',
                    'active' => request()->is('admin/follow-ups*'),
                ],
                [
                    'label' => 'Laporan',
                    'url' => '/admin/reports',
                    'icon' => '📊',
                    'active' => request()->is('admin/reports*'),
                ],
                [
                    'label' => 'Master Data',
                    'url' => '/admin/master-data',
                    'icon' => '⚙️',
                    'active' => request()->is('admin/master-data*'),
                ],
                [
                    'label' => 'Integrasi SIAKAD',
                    'url' => '/admin/integrations',
                    'icon' => '🔄',
                    'active' => request()->is('admin/integrations*'),
                ],
            ];
        @endphp

        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen"
             x-transition.opacity
             x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden">
        </div>

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-polmind-blue text-white shadow-2xl shadow-blue-950/30 transition-transform duration-300 lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            {{-- Brand --}}
            <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-5">
                <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-polmind-yellow text-lg font-black text-polmind-blue-dark">
                        P
                    </div>

                    <div>
                        <p class="text-sm font-black leading-tight">
                            PMB Polmind
                        </p>
                        <p class="text-xs font-medium text-blue-100">
                            Admin Panel
                        </p>
                    </div>
                </a>

                <button type="button"
                        @click="sidebarOpen = false"
                        class="rounded-xl bg-white/10 px-3 py-2 text-sm font-black lg:hidden">
                    ✕
                </button>
            </div>

            {{-- Menu --}}
            <div class="flex-1 overflow-y-auto px-4 py-5">
                <nav class="space-y-1">
                    @foreach($menus as $menu)
                        <a href="{{ url($menu['url']) }}"
                           class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition
                           {{ $menu['active']
                                ? 'bg-polmind-yellow text-polmind-blue-dark shadow-sm'
                                : 'text-blue-100 hover:bg-white/10 hover:text-white'
                           }}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-base
                                {{ $menu['active'] ? 'bg-white/70' : 'bg-white/10' }}">
                                {{ $menu['icon'] }}
                            </span>

                            <span class="line-clamp-1">
                                {{ $menu['label'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Sidebar Footer --}}
            <div class="shrink-0 border-t border-white/10 p-4">
                <div class="rounded-2xl bg-white/10 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-100">
                        Sistem PMB
                    </p>
                    <p class="mt-1 text-sm font-black text-white">
                        Polmind 2026
                    </p>
                    <p class="mt-2 text-xs leading-5 text-blue-100">
                        UI development sebelum integrasi database.
                    </p>
                </div>
            </div>
        </aside>

        {{-- Main Area --}}
        <div class="min-h-screen lg:pl-72">

            {{-- Topbar --}}
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="flex min-h-20 items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">

                    <div class="flex min-w-0 items-center gap-4">
                        <button type="button"
                                @click="sidebarOpen = true"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-lg font-black text-polmind-blue shadow-sm lg:hidden">
                            ☰
                        </button>

                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-black text-polmind-blue sm:text-2xl">
                                @yield('page_title', 'Dashboard')
                            </h1>
                            <p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-500">
                                @yield('page_subtitle', 'Kelola sistem PMB Polmind.')
                            </p>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 sm:flex">
                        <div class="text-right">
                            <p class="text-sm font-black text-slate-900">
                                Admin PMB
                            </p>
                            <p class="text-xs text-slate-500">
                                Politeknik Mitra Industri
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-polmind-blue text-sm font-black text-white">
                            AD
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            // Reserved for future admin layout interactions
        });
    </script>
</body>
</html>