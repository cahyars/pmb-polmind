<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Camaba') - PMB Polmind</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-polmind-bg text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">

        @php
            $menus = [
                [
                    'label' => 'Dashboard',
                    'url' => '/camaba/dashboard',
                    'icon' => '▦',
                    'active' => request()->is('camaba/dashboard'),
                ],
                [
                    'label' => 'Biodata',
                    'url' => '/camaba/biodata',
                    'icon' => '👤',
                    'active' => request()->is('camaba/biodata*'),
                ],
                [
                    'label' => 'Berkas',
                    'url' => '/camaba/upload-berkas',
                    'icon' => '📄',
                    'active' => request()->is('camaba/upload-berkas*'),
                ],
                [
                    'label' => 'Pembayaran',
                    'url' => '/camaba/pembayaran',
                    'icon' => '💳',
                    'active' => request()->is('camaba/pembayaran*'),
                ],
                [
                    'label' => 'Status Seleksi',
                    'url' => '/camaba/status-seleksi',
                    'icon' => '✅',
                    'active' => request()->is('camaba/status-seleksi*'),
                ],
                [
                    'label' => 'Pengumuman',
                    'url' => '/camaba/pengumuman',
                    'icon' => '🎓',
                    'active' => request()->is('camaba/pengumuman*'),
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
        <aside class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-white shadow-2xl shadow-slate-900/10 transition-transform duration-300 lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            {{-- Brand --}}
            <div class="flex h-20 shrink-0 items-center justify-between border-b border-slate-200 px-5">
                <a href="{{ url('/camaba/dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-polmind-blue text-lg font-black text-white">
                        P
                    </div>

                    <div>
                        <p class="text-sm font-black leading-tight text-polmind-blue">
                            PMB Polmind
                        </p>
                        <p class="text-xs font-medium text-slate-500">
                            Portal Camaba
                        </p>
                    </div>
                </a>

                <button type="button"
                        @click="sidebarOpen = false"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-black text-slate-600 lg:hidden">
                    ✕
                </button>
            </div>

            {{-- Applicant Card --}}
            <div class="shrink-0 border-b border-slate-200 p-5">
                <div class="rounded-3xl bg-polmind-blue p-5 text-white shadow-lg shadow-blue-900/10">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-sm font-black">
                            AF
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-black">
                                Ahmad Fauzi
                            </p>
                            <p class="mt-1 truncate text-xs text-blue-100">
                                PMB20240982
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex justify-between gap-3 text-xs font-bold text-blue-100">
                            <span>Progress</span>
                            <span>60%</span>
                        </div>

                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/20">
                            <div class="h-full w-[60%] rounded-full bg-polmind-yellow"></div>
                        </div>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-blue-100">
                        Lengkapi biodata, berkas, dan pembayaran untuk melanjutkan proses seleksi.
                    </p>
                </div>
            </div>

            {{-- Menu --}}
            <div class="flex-1 overflow-y-auto px-4 py-5">
                <nav class="space-y-1">
                    @foreach($menus as $menu)
                        <a href="{{ url($menu['url']) }}"
                           class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition
                           {{ $menu['active']
                                ? 'bg-polmind-blue text-white shadow-sm'
                                : 'text-slate-600 hover:bg-blue-50 hover:text-polmind-blue'
                           }}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-base
                                {{ $menu['active'] ? 'bg-white/10' : 'bg-slate-100' }}">
                                {{ $menu['icon'] }}
                            </span>

                            <span class="truncate">
                                {{ $menu['label'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Sidebar Footer --}}
            <div class="shrink-0 border-t border-slate-200 p-4">
                <a href="{{ url('/') }}"
                   class="flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    ← Kembali ke Landing
                </a>
                <form action="{{ route('logout') }}" method="POST" class="mt-3">
                    @csrf

                    <button type="submit"
                            class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-left text-sm font-bold text-red-700 transition hover:bg-red-100">
                        Logout
                    </button>
                </form>
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
                                @yield('page_title', 'Dashboard Camaba')
                            </h1>
                            <p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-500">
                                @yield('page_subtitle', 'Pantau proses pendaftaran mahasiswa baru Anda.')
                            </p>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 sm:flex">
                        <div class="text-right">
                            <p class="text-sm font-black text-slate-900">
                                Ahmad Fauzi
                            </p>
                            <p class="text-xs text-slate-500">
                                Calon Mahasiswa
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-polmind-blue text-sm font-black text-white">
                            AF
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
</body>
</html> 