<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Camaba') - PMB Polmind</title>
    @include('partials.favicon')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-polmind-bg text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
    @php
        $authApplicant = auth('applicant')->user();

        $displayName = $authApplicant?->full_name ?? 'Camaba';
        $registrationNumber = $authApplicant?->registration_number ?? '-';

        $nameParts = collect(explode(' ', trim($displayName)))->filter()->values();

        $initials = $nameParts->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('');

        $initials = $initials ?: 'C';

        $journey = $authApplicant ? \App\Services\ApplicantJourney::for($authApplicant) : null;
        $registrationPaid = $journey?->registrationPaid() ?? false;
        $progressPercent = $journey?->progressPercent() ?? 0;

        $photoDocument = $authApplicant?->photoDocument;
        $photoUrl = $photoDocument ? route('camaba.documents.file', $photoDocument) : null;
        $isTemporaryNumber = \App\Services\RegistrationNumber::isTemporary($registrationNumber);

        // Pengumuman admin untuk lonceng: 5 terbaru dan jumlah yang belum dibaca.
        $latestAnnouncements = $authApplicant?->announcements()->latest('announcements.created_at')->take(5)->get() ?? collect();
        $unreadAnnouncements = $authApplicant?->announcements()->wherePivotNull('read_at')->count() ?? 0;
    @endphp

        @php
            $menus = [
                [
                    'label' => 'Dashboard',
                    'url' => '/camaba/dashboard',
                    'icon' => 'dashboard',
                    'active' => request()->is('camaba/dashboard'),
                ],
                [
                    'label' => 'Biodata',
                    'locked' => ! $registrationPaid,
                    'url' => '/camaba/biodata',
                    'icon' => 'user',
                    'active' => request()->is('camaba/biodata*'),
                ],
                [
                    'label' => 'Berkas',
                    'locked' => ! $registrationPaid,
                    'url' => '/camaba/upload-berkas',
                    'icon' => 'document-text',
                    'active' => request()->is('camaba/upload-berkas*'),
                ],
                [
                    'label' => 'Pembayaran',
                    'url' => '/camaba/pembayaran',
                    'icon' => 'card',
                    'active' => request()->is('camaba/pembayaran*'),
                ],
                [
                    'label' => 'Status Seleksi',
                    'locked' => ! $registrationPaid,
                    'url' => '/camaba/status-seleksi',
                    'icon' => 'clipboard-check',
                    'active' => request()->is('camaba/status-seleksi*'),
                ],
                [
                    'label' => 'Hasil Seleksi',
                    'locked' => ! $registrationPaid,
                    'url' => '/camaba/hasil-seleksi',
                    'icon' => 'badge-check',
                    'active' => request()->is('camaba/hasil-seleksi*'),
                ],
                [
                    'label' => 'Pengumuman',
                    'url' => '/camaba/pengumuman',
                    'icon' => 'megaphone',
                    'badge' => $unreadAnnouncements,
                    'active' => request()->is('camaba/pengumuman*'),
                ],
                [
                    'label' => 'Daftar Ulang',
                    'locked' => ! $registrationPaid,
                    'url' => '/camaba/daftar-ulang',
                    'icon' => 'cap',
                    'active' => request()->is('camaba/daftar-ulang*'),
                ],
                [
                    'label' => 'Ganti Password',
                    'url' => '/camaba/akun',
                    'icon' => 'key',
                    'active' => request()->is('camaba/akun*'),
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
                    <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri" class="h-12 w-auto">
                    <span class="border-l border-slate-200 pl-3 text-xs font-bold text-slate-500">Portal Camaba</span>
                </a>

                <button type="button"
                        @click="sidebarOpen = false"
                        aria-label="Tutup menu"
                        class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 lg:hidden">
                    <x-icon name="close" class="h-5 w-5" />
                </button>
            </div>

            {{-- Applicant Card --}}
            <div class="shrink-0 border-b border-slate-200 p-5">
                <div class="rounded-3xl bg-polmind-blue p-5 text-white shadow-lg shadow-blue-900/10">
                    <div class="flex items-center gap-3">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Foto {{ $displayName }}" class="h-12 w-12 shrink-0 rounded-2xl bg-white object-cover ring-2 ring-white/30">
                        @else
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-sm font-black">
                                {{ $initials }}
                            </div>
                        @endif

                        <div class="min-w-0">
                            <p class="truncate text-sm font-black">
                                {{ $displayName }}
                            </p>
                            <p class="mt-1 truncate text-xs text-blue-100">
                                {{ $registrationNumber }}{{ $isTemporaryNumber ? ' (sementara)' : '' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex justify-between gap-3 text-xs font-bold text-blue-100">
                            <span>Progress</span>
                            <span>{{ $progressPercent }}%</span>
                        </div>

                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/20">
                            <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ $progressPercent }}%"></div>
                        </div>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-blue-100">
                        {{ $registrationPaid ? 'Lengkapi biodata dan berkas untuk mengikuti seleksi.' : 'Bayar formulir terlebih dahulu untuk membuka pengisian biodata dan berkas.' }}
                    </p>
                </div>
            </div>

            {{-- Menu --}}
            <div class="scrollbar-none flex-1 overflow-y-auto px-4 py-5">
                <nav class="space-y-1">
                    @foreach($menus as $menu)
                        @php
                            $locked = $menu['locked'] ?? false;
                        @endphp
                        <a href="{{ url($menu['url']) }}"
                           @if($locked) @click.prevent="window.showPaymentRequired()" aria-disabled="true" title="Bayar formulir terlebih dahulu" @endif
                           class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition
                           {{ $menu['active']
                                ? 'bg-polmind-blue text-white shadow-sm'
                                : ($locked ? 'text-slate-400 hover:bg-slate-50' : 'text-slate-600 hover:bg-blue-50 hover:text-polmind-blue')
                           }}">
                            <x-icon :name="$menu['icon']" :class="$locked ? 'h-5 w-5 shrink-0 opacity-60' : 'h-5 w-5 shrink-0'" />

                            <span class="flex-1 truncate">
                                {{ $menu['label'] }}
                            </span>

                            @if($locked)
                                <x-icon name="lock" class="h-4 w-4 shrink-0" />
                            @elseif(($menu['badge'] ?? 0) > 0)
                                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[11px] font-bold text-white">
                                    {{ $menu['badge'] > 99 ? '99+' : $menu['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Sidebar Footer --}}
            <div class="shrink-0 border-t border-slate-200 p-4">
                <a href="{{ url('/') }}"
                   class="flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Kembali ke Landing
                </a>
                <form action="{{ route('logout') }}" method="POST" class="mt-3">
                    @csrf

                    <button type="submit"
                            class="flex w-full items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-left text-sm font-bold text-red-700 transition hover:bg-red-100">
                        <x-icon name="logout" class="h-4 w-4" />
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
                                aria-label="Buka menu"
                                class="rounded-xl border border-slate-200 bg-white p-2 text-polmind-blue shadow-sm lg:hidden">
                            <x-icon name="menu" class="h-5 w-5" />
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

                    <div class="flex shrink-0 items-center gap-4">
                    {{-- Lonceng pengumuman admin --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                        <button type="button"
                                @click="open = ! open"
                                :aria-expanded="open"
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-polmind-blue shadow-sm transition hover:bg-blue-50">
                            <span class="sr-only">Pengumuman</span>
                            <x-icon name="bell" class="h-5 w-5" />
                            @if($unreadAnnouncements > 0)
                                <span class="absolute -right-1.5 -top-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 py-0.5 text-[10px] font-bold leading-none text-white ring-2 ring-white">
                                    {{ $unreadAnnouncements > 99 ? '99+' : $unreadAnnouncements }}
                                </span>
                            @endif
                        </button>

                        <div x-show="open"
                             x-transition.origin.top.right
                             x-cloak
                             class="absolute right-0 z-40 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <p class="text-sm font-black text-polmind-blue">Pengumuman</p>
                                @if($unreadAnnouncements > 0)
                                    <form action="{{ route('camaba.announcements.read-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold text-slate-500 hover:text-polmind-blue">Tandai semua dibaca</button>
                                    </form>
                                @endif
                            </div>

                            @forelse($latestAnnouncements as $bellAnnouncement)
                                <a href="{{ route('camaba.announcements.show', $bellAnnouncement->id) }}"
                                   class="flex gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $bellAnnouncement->pivot->read_at ? 'bg-transparent' : 'bg-red-600' }}"></span>
                                    <span class="min-w-0">
                                        <span class="line-clamp-2 text-sm {{ $bellAnnouncement->pivot->read_at ? 'font-semibold text-slate-600' : 'font-black text-slate-900' }}">{{ $bellAnnouncement->title }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $bellAnnouncement->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @empty
                                <p class="px-3 py-4 text-sm text-slate-500">Belum ada pengumuman.</p>
                            @endforelse

                            <a href="{{ route('camaba.announcements.index') }}"
                               class="mt-1 block rounded-xl border-t border-slate-100 px-3 py-2.5 text-center text-sm font-bold text-polmind-blue hover:bg-blue-50">
                                Lihat semua pengumuman
                            </a>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 sm:flex">
                        <div class="text-right">
                            <p class="text-sm font-black text-slate-900">
                                {{ $displayName }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Calon Mahasiswa
                            </p>
                        </div>

                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Foto {{ $displayName }}" class="h-11 w-11 rounded-2xl object-cover ring-2 ring-slate-200">
                        @else
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-polmind-blue text-sm font-black text-white">
                                {{ $initials }}
                            </div>
                        @endif
                    </div>
                    </div>
                </div>
            </header>

            @if($loginAs = \App\Http\Controllers\Admin\LoginAsController::impersonator())
                <div class="flex flex-wrap items-center justify-between gap-2 bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-900 sm:px-6 lg:px-8">
                    <span>Login As oleh {{ $loginAs['admin_name'] }}: tampilan ini sama dengan yang dilihat camaba.</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="underline hover:no-underline">Keluar dari akun camaba</button>
                    </form>
                </div>
            @endif

            {{-- Page Content --}}
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.file-preview-modal')

    <script>
        window.showPaymentRequired = () => window.Swal.fire({
            icon: 'info',
            title: 'Bayar Formulir Terlebih Dahulu',
            html: 'Silakan melakukan pembayaran formulir pendaftaran terlebih dahulu. Pengisian biodata, berkas, dan tahapan berikutnya akan terbuka setelah pembayaran <b>diverifikasi admin</b>.',
            confirmButtonText: 'Ke Halaman Pembayaran',
            showCancelButton: true,
            cancelButtonText: 'Nanti',
            confirmButtonColor: '#003B82',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = @js(route('camaba.payments.index'));
            }
        });

        @if(session('payment_required'))
            document.addEventListener('DOMContentLoaded', () => window.showPaymentRequired());
        @endif
    </script>

    @include('partials.whatsapp-float', [
        'message' => $authApplicant
            ? 'Halo Admin PMB, saya ' . $authApplicant->full_name . ' (' . $authApplicant->registration_number . ') ingin bertanya.'
            : null,
        'offset' => trim($__env->yieldContent('whatsapp_offset')) ?: null,
    ])
</body>
</html> 