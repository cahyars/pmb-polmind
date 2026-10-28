<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin PMB') - PMB Polmind</title>
    @include('partials.favicon')

    {{-- Terapkan pilihan sidebar ciut sebelum halaman tampil agar tidak berkedip. --}}
    <script>
        try {
            if (localStorage.getItem('pmb.admin.sidebar') === 'collapsed') {
                document.documentElement.dataset.sidebar = 'collapsed';
            }
        } catch (error) {}
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-polmind-bg font-jakarta text-slate-800 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">

        @php
            $adminUser = auth('web')->user();

            // Badge pekerjaan yang perlu ditindaklanjuti (mengikuti Tahun Akademik terpilih).
            $badges = [
                'applicants' => \App\Models\Applicant::query()
                    ->when($adminUser?->applicants_seen_at, fn ($query, $seenAt) => $query->where('created_at', '>', $seenAt))
                    ->count(),
                'documents' => \App\Models\ApplicantDocument::where('status', 'menunggu_verifikasi')->count(),
                'payments' => \App\Models\Payment::where('status', 'waiting_verification')->count(),
            ];

            $notifications = collect([
                ['count' => $badges['applicants'], 'label' => 'camaba baru sejak terakhir dibuka', 'url' => '/admin/applicants'],
                ['count' => $badges['documents'], 'label' => 'berkas menunggu verifikasi', 'url' => '/admin/documents'],
                ['count' => $badges['payments'], 'label' => 'pembayaran menunggu verifikasi', 'url' => '/admin/payments'],
            ])->where('count', '>', 0);

            $menuGroups = [
                'Menu' => [
                    ['label' => 'Dashboard', 'url' => '/admin/dashboard', 'icon' => 'dashboard', 'active' => request()->is('admin/dashboard')],
                    ['label' => 'Data Camaba', 'url' => '/admin/applicants', 'icon' => 'users', 'badge' => $badges['applicants'], 'badgeTitle' => 'camaba baru sejak terakhir dibuka', 'active' => request()->is('admin/applicants*')],
                    ['label' => 'Verifikasi Berkas', 'url' => '/admin/documents', 'icon' => 'document', 'badge' => $badges['documents'], 'badgeTitle' => 'berkas menunggu verifikasi', 'active' => request()->is('admin/documents*')],
                    ['label' => 'Verifikasi Pembayaran', 'url' => '/admin/payments', 'icon' => 'card', 'badge' => $badges['payments'], 'badgeTitle' => 'pembayaran menunggu verifikasi', 'active' => request()->is('admin/payments*')],
                    ['label' => 'Seleksi', 'url' => '/admin/selections', 'icon' => 'check', 'active' => request()->is('admin/selections*')],
                    ['label' => 'Daftar Ulang', 'url' => '/admin/re-registrations', 'icon' => 'cap', 'active' => request()->is('admin/re-registrations*')],
                    ['label' => 'Follow Up', 'url' => '/admin/follow-ups', 'icon' => 'chat', 'active' => request()->is('admin/follow-ups*')],
                    [
                        'label' => 'Laporan',
                        'icon' => 'chart',
                        'active' => request()->is('admin/reports*'),
                        'children' => collect(\App\Http\Controllers\Admin\ReportController::SECTIONS)->map(fn ($item, $key) => [
                            'label' => $item['label'],
                            'url' => route('admin.reports.show', $key),
                            'active' => request()->routeIs('admin.reports.show') && request()->route('section') === $key,
                        ])->values()->all(),
                    ],
                ],
                'Pengaturan' => [
                    [
                        'label' => 'Master Data',
                        'url' => '/admin/master-data',
                        'icon' => 'cog',
                        'admin_only' => true,
                        'active' => request()->is('admin/master-data*'),
                        'children' => collect(\App\Http\Controllers\Admin\MasterDataController::SECTIONS)
                            ->map(fn (array $item, string $key) => [
                                'label' => $item['label'],
                                'url' => route('admin.master-data.show', $key),
                                'active' => request()->routeIs('admin.master-data.show') && request()->route('section') === $key,
                            ])
                            ->values()
                            ->all(),
                    ],
                    ['label' => 'Tujuan Pembayaran', 'url' => '/admin/payment-methods', 'icon' => 'bank', 'admin_only' => true, 'active' => request()->is('admin/payment-methods*')],
                    ['label' => 'Integrasi SIAKAD', 'url' => '/admin/integrations', 'icon' => 'sync', 'active' => request()->is('admin/integrations*')],
                    ['label' => 'Kelola Akun', 'url' => '/admin/users', 'icon' => 'key', 'admin_only' => true, 'active' => request()->is('admin/users*')],
                ],
            ];

            // Operator PMB tidak melihat menu khusus Admin PMB.
            $menuGroups = collect($menuGroups)
                ->map(fn ($menus) => array_values(array_filter($menus, fn ($menu) => empty($menu['admin_only']) || $adminUser?->isAdmin())))
                ->filter()
                ->all();

            $adminName = $adminUser?->name ?: 'Admin PMB';
            $adminInitials = collect(preg_split('/\s+/', trim($adminName)))
                ->filter()
                ->take(2)
                ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                ->implode('');
        @endphp

        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen"
             x-transition.opacity
             x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden">
        </div>

        {{-- Sidebar --}}
        <aside class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col overflow-hidden border-r border-slate-200 bg-white transition-[translate,width,box-shadow] duration-200 lg:translate-x-0"
               :class="sidebarOpen && 'translate-x-0!'"
               x-data="{
                   collapsed: document.documentElement.dataset.sidebar === 'collapsed',
                   toggleCollapsed() {
                       this.collapsed = ! this.collapsed;
                       document.documentElement.dataset.sidebar = this.collapsed ? 'collapsed' : 'expanded';
                       try { localStorage.setItem('pmb.admin.sidebar', this.collapsed ? 'collapsed' : 'expanded'); } catch (error) {}

                       // Lepas fokus tombol agar sidebar langsung menciut saat kursor keluar.
                       if (this.collapsed) {
                           document.activeElement?.blur();
                       }
                   },
               }">

            {{-- Brand --}}
            <div class="flex h-[72px] shrink-0 items-center justify-between gap-2 px-5">
                <a href="{{ url('/admin/dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri" class="sidebar-logo h-9 w-auto shrink-0 object-contain object-left">
                    <span class="sidebar-label whitespace-nowrap border-l border-slate-200 pl-3 text-sm font-bold leading-tight text-slate-900">
                        PMB<br><span class="text-xs font-medium text-slate-400">Admin Panel</span>
                    </span>
                </a>

                {{-- Desktop: ciutkan sidebar jadi ikon, atau kunci tetap lebar --}}
                <button type="button"
                        @click="toggleCollapsed()"
                        :title="collapsed ? 'Kunci sidebar tetap lebar' : 'Ciutkan sidebar'"
                        :aria-pressed="(! collapsed).toString()"
                        class="sidebar-label hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:flex">
                    <span class="sr-only" x-text="collapsed ? 'Kunci sidebar tetap lebar' : 'Ciutkan sidebar'">Ciutkan sidebar</span>
                    <x-icon name="collapse" x-show="! collapsed" class="h-4 w-4" stroke-width="2" />
                    <x-icon name="pin" x-show="collapsed" x-cloak class="h-4 w-4" stroke-width="2" />
                </button>

                <button type="button"
                        @click="sidebarOpen = false"
                        aria-label="Tutup menu"
                        class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden">
                    <x-icon name="close" class="h-5 w-5" stroke-width="1.5" />
                </button>
            </div>

            {{-- Menu --}}
            <nav class="scrollbar-none flex-1 overflow-y-auto px-4 pb-4 pt-2" aria-label="Menu admin">
                @foreach($menuGroups as $group => $menus)
                    <div @class(['mt-6 border-t border-slate-100 pt-6' => ! $loop->first])>
                        <p class="sidebar-label whitespace-nowrap px-3 text-xs font-semibold uppercase tracking-wider text-slate-900">{{ $group }}</p>

                        <div class="mt-3 space-y-1">
                            @foreach($menus as $menu)
                                @if(! empty($menu['children']))
                                    {{-- Menu induk dengan submenu (contoh: Master Data) --}}
                                    <div x-data="{ open: @js($menu['active']) }">
                                        <button type="button"
                                                @click="open = ! open"
                                                :aria-expanded="open.toString()"
                                                title="{{ $menu['label'] }}"
                                                class="flex w-full items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm transition
                                                {{ $menu['active'] ? 'bg-blue-50 font-semibold text-polmind-blue' : 'font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                            <x-icon :name="$menu['icon']" class="h-5 w-5 shrink-0" stroke-width="1.5" />
                                            <span class="sidebar-label line-clamp-1 flex-1 text-left">{{ $menu['label'] }}</span>
                                            <x-icon name="chevron" class="sidebar-label h-4 w-4 shrink-0 transition" ::class="open && 'rotate-180'" stroke-width="2" />
                                        </button>

                                        <div x-show="open" @if(! $menu['active']) x-cloak @endif x-transition.opacity
                                             class="sidebar-label ml-5 mt-1 space-y-0.5 border-l border-slate-200 pl-3">
                                            @foreach($menu['children'] as $child)
                                                <a href="{{ $child['url'] }}"
                                                   @if($child['active']) aria-current="page" @endif
                                                   class="block whitespace-nowrap rounded-md px-3 py-2 text-[13px] transition
                                                   {{ $child['active'] ? 'bg-polmind-blue font-semibold text-white' : 'font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                                    {{ $child['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    @continue
                                @endif

                                <a href="{{ url($menu['url']) }}"
                                   @if($menu['active']) aria-current="page" @endif
                                   title="{{ $menu['label'] }}"
                                   class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm transition
                                   {{ $menu['active']
                                        ? 'bg-polmind-blue font-semibold text-white shadow-sm shadow-blue-900/20'
                                        : 'font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                                   }}">
                                    <span class="relative shrink-0">
                                        <x-icon :name="$menu['icon']" class="h-5 w-5" stroke-width="1.5" />
                                        @if(($menu['badge'] ?? 0) > 0)
                                            {{-- Penanda badge saat sidebar diciutkan --}}
                                            <span class="sidebar-dot absolute -right-1 -top-1 hidden h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
                                        @endif
                                    </span>

                                    <span class="sidebar-label line-clamp-1 flex-1">{{ $menu['label'] }}</span>

                                    @if(($menu['badge'] ?? 0) > 0)
                                        <span class="sidebar-label ml-auto inline-flex min-w-6 items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-semibold
                                              {{ $menu['active'] ? 'bg-white text-polmind-blue' : 'bg-polmind-blue text-white' }}"
                                              title="{{ $menu['badge'] }} {{ $menu['badgeTitle'] }}">
                                            {{ $menu['badge'] > 99 ? '99+' : $menu['badge'] }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            {{-- Sidebar Footer --}}
            <div class="shrink-0 border-t border-slate-100 p-4">
                <div class="sidebar-label rounded-lg bg-slate-50 px-3 py-2.5">
                    <p class="text-[11px] font-medium text-slate-400">Tahun PMB</p>
                    <p @class(['text-sm font-semibold text-slate-800', 'md:block hidden' => isset($adminPmbYears) && $adminPmbYears->isNotEmpty()])>
                        {{ $selectedPmbYear?->name ?? 'Belum ada Tahun PMB' }}
                    </p>

                    @if(isset($adminPmbYears) && $adminPmbYears->isNotEmpty())
                        <form action="{{ route('admin.pmb-year.select') }}" method="POST" class="mt-1 md:hidden">
                            @csrf
                            <label for="admin-pmb-year-mobile" class="sr-only">Tahun Akademik</label>
                            <select id="admin-pmb-year-mobile"
                                    name="pmb_year_id"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm font-semibold text-slate-800 outline-none focus:border-polmind-blue">
                                @foreach($adminPmbYears as $pmbYear)
                                    <option value="{{ $pmbYear->id }}" @selected($selectedPmbYear?->id === $pmbYear->id)>
                                        {{ $pmbYear->name }}{{ $pmbYear->is_active ? ' (Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>

                <form action="{{ route('admin.logout') }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit"
                            title="Log Out"
                            class="flex w-full items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium text-red-500 transition hover:bg-red-50">
                        <x-icon name="logout" class="h-5 w-5 shrink-0" stroke-width="1.5" />
                        <span class="sidebar-label">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Area --}}
        <div class="admin-main min-h-screen transition-[padding] duration-200 lg:pl-64">

            {{-- Topbar --}}
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
                <div class="flex min-h-[72px] items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <button type="button"
                            @click="sidebarOpen = true"
                            aria-label="Buka menu"
                            class="rounded-lg border border-slate-200 p-2 text-slate-600 lg:hidden">
                        <x-icon name="menu" class="h-5 w-5" stroke-width="1.5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-xl font-bold text-slate-900 sm:text-2xl">
                            @yield('page_title', 'Dashboard')
                        </h1>
                        <p class="hidden truncate text-xs text-slate-400 sm:block">
                            @yield('page_subtitle', 'Kelola sistem PMB Polmind.')
                        </p>
                    </div>

                    {{-- Pencarian cepat camaba --}}
                    <form action="{{ route('admin.applicants.index') }}" method="GET" role="search" class="hidden w-56 shrink xl:block 2xl:w-80">
                        <label for="admin-search" class="sr-only">Cari camaba</label>
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" stroke-width="1.8" />
                            <input id="admin-search"
                                   type="search"
                                   name="keyword"
                                   value="{{ request()->is('admin/applicants') ? request('keyword') : '' }}"
                                   placeholder="Cari camaba..."
                                   title="Cari nama, NIK, NISN, atau nomor pendaftaran"
                                   class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-polmind-blue focus:bg-white focus:ring-4 focus:ring-blue-100">
                        </div>
                    </form>

                    @if(isset($adminPmbYears) && $adminPmbYears->isNotEmpty())
                        <form action="{{ route('admin.pmb-year.select') }}" method="POST" class="hidden shrink-0 md:block">
                            @csrf
                            <label for="admin-pmb-year" class="sr-only">Tahun Akademik</label>
                            <select id="admin-pmb-year"
                                    name="pmb_year_id"
                                    onchange="this.form.submit()"
                                    title="Tahun Akademik"
                                    class="max-w-44 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 xl:max-w-none">
                                @foreach($adminPmbYears as $pmbYear)
                                    <option value="{{ $pmbYear->id }}" @selected($selectedPmbYear?->id === $pmbYear->id)>
                                        {{ $pmbYear->name }}{{ $pmbYear->is_active ? ' (Aktif)' : '' }} · TA {{ $pmbYear->year }}/{{ $pmbYear->year + 1 }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ url('/admin/follow-ups') }}"
                           title="Follow Up camaba"
                           class="hidden h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 sm:flex">
                            <span class="sr-only">Follow Up</span>
                            <x-icon name="mail" class="h-5 w-5" stroke-width="1.5" />
                        </a>

                        {{-- Notifikasi pekerjaan tertunda --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button"
                                    @click="open = ! open"
                                    :aria-expanded="open"
                                    class="relative flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">
                                <span class="sr-only">Notifikasi</span>
                                <x-icon name="bell" class="h-5 w-5" stroke-width="1.5" />
                                @if($notifications->isNotEmpty())
                                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                                @endif
                            </button>

                            <div x-show="open"
                                 x-transition.origin.top.right
                                 x-cloak
                                 class="absolute right-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-lg shadow-slate-200/60">
                                <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Perlu ditindaklanjuti</p>
                                @forelse($notifications as $notification)
                                    <a href="{{ url($notification['url']) }}"
                                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                                        <span class="inline-flex min-w-7 justify-center rounded-full bg-polmind-blue px-2 py-0.5 text-xs font-semibold text-white">{{ $notification['count'] }}</span>
                                        {{ $notification['label'] }}
                                    </a>
                                @empty
                                    <p class="px-3 py-4 text-sm text-slate-500">Tidak ada pekerjaan tertunda.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="ml-2 hidden items-center gap-3 border-l border-slate-200 pl-4 sm:flex">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600">
                                {{ $adminInitials ?: 'AD' }}
                            </div>
                            <div class="hidden 2xl:block">
                                <p class="text-sm font-semibold text-slate-900">{{ $adminName }}</p>
                                <p class="text-xs text-slate-400">{{ $adminUser?->role_label ?? 'Administrator' }}</p>
                            </div>
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

    @include('partials.file-preview-modal')

    @stack('scripts')
</body>
</html>
