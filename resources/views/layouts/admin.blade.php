<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin PMB Polmind')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 antialiased">

<div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">

    <aside class="fixed inset-y-0 left-0 z-50 w-72 flex-col -translate-x-full border-r border-slate-200 bg-polmind-blue-dark text-white transition-transform duration-300 lg:static lg:translate-x-0"
           :class="{ 'translate-x-0': sidebarOpen }">

        <div class="flex h-full flex-col">
            <div class="px-6 py-7">
                <a href="{{ url('/admin/dashboard') }}" class="text-3xl font-bold">
                    Polmind
                </a>
                <p class="mt-1 text-sm text-blue-100">Admin PMB</p>
            </div>

            <nav class="flex-1 space-y-1 px-4">
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

                @foreach($menus as $menu)
                    <a href="{{ url($menu['url']) }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition
                    {{ $menu['active']
                            ? 'bg-polmind-yellow text-polmind-blue-dark shadow-sm'
                            : 'text-blue-100 hover:bg-white/10 hover:text-white'
                    }}">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg
                            {{ $menu['active'] ? 'bg-white/60' : 'bg-white/10' }}">
                            {{ $menu['icon'] }}
                        </span>

                        <span>{{ $menu['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="px-4 pb-6">
                <a href="{{ url('/') }}"
                   class="block rounded-xl bg-polmind-yellow px-4 py-3 text-center text-sm font-bold text-polmind-blue-dark">
                    Lihat Website PMB
                </a>
            </div>
        </div>
    </aside>
    

    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/40 lg:hidden"></div>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button"
                            class="rounded-lg border border-slate-200 bg-white p-2 lg:hidden"
                            @click="sidebarOpen = true">
                        ☰
                    </button>

                    <div>
                        <h1 class="text-lg font-bold text-polmind-blue sm:text-xl">
                            @yield('page_title', 'Dashboard Admin')
                        </h1>
                        <p class="hidden text-sm text-slate-500 sm:block">
                            @yield('page_subtitle', 'Manajemen Pendaftaran Mahasiswa Baru')
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Notifikasi
                    </button>

                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-bold text-slate-900">Admin PMB</p>
                            <p class="text-xs text-slate-500">Super Admin</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-polmind-blue text-sm font-bold text-white">
                            AD
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-8 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>
    
</div>

</body>
</html>