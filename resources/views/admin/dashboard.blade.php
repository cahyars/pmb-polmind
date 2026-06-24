@extends('layouts.admin')

@section('title', 'Dashboard Admin PMB')
@section('page_title', 'Dashboard PMB')
@section('page_subtitle', 'Ringkasan penerimaan mahasiswa baru Politeknik Mitra Industri.')

@section('content')
<div class="space-y-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                    Admin PMB Polmind
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Monitoring Penerimaan Mahasiswa Baru
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Pantau progres pendaftaran, kelengkapan biodata, verifikasi berkas,
                    pembayaran, seleksi, daftar ulang, dan kesiapan sinkronisasi ke SIAKAD.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        Tahun PMB: 2026
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Gelombang 2 Aktif
                    </span>
                </div>
            </div>

            <div class="rounded-3xl bg-white/10 p-6">
                <p class="text-sm font-bold text-blue-100">Total Pendaftar</p>
                <p class="mt-3 text-5xl font-black">{{ $totalApplicants }}</p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ min($targetProgress, 100) }}%"></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Target sementara {{ $targetApplicants }} pendaftar. Progress sekitar {{ $targetProgress }}%.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Stats --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Total Camaba',
                'value' => $totalApplicants,
                'desc' => 'Semua pendaftar',
                'url' => '/admin/applicants',
                'class' => 'bg-blue-100 text-polmind-blue',
            ],
            [
                'label' => 'Berkas Menunggu',
                'value' => $pendingDocuments,
                'desc' => 'Perlu diverifikasi',
                'url' => '/admin/documents',
                'class' => 'bg-yellow-100 text-yellow-700',
            ],
            [
                'label' => 'Pembayaran Masuk',
                'value' => $pendingPayments,
                'desc' => 'Menunggu validasi',
                'url' => '/admin/payments',
                'class' => 'bg-purple-100 text-purple-700',
            ],
            [
                'label' => 'Daftar Ulang Valid',
                'value' => $validReRegistrations,
                'desc' => 'Siap sinkron SIAKAD',
                'url' => '/admin/re-registrations',
                'class' => 'bg-green-100 text-green-700',
            ],
        ] as $card)
            <a href="{{ url($card['url']) }}"
               class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        Detail
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Module Shortcuts --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Shortcut Modul PMB
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Akses cepat ke seluruh modul utama admin PMB.
                </p>
            </div>

            <a href="{{ url('/admin/reports') }}"
               class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Lihat Laporan
            </a>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['label' => 'Data Camaba', 'desc' => 'Kelola data pendaftar', 'url' => '/admin/applicants', 'icon' => '👤'],
                ['label' => 'Berkas', 'desc' => 'Validasi dokumen', 'url' => '/admin/documents', 'icon' => '📄'],
                ['label' => 'Pembayaran', 'desc' => 'Verifikasi bukti bayar', 'url' => '/admin/payments', 'icon' => '💳'],
                ['label' => 'Seleksi', 'desc' => 'Tetapkan hasil seleksi', 'url' => '/admin/selections', 'icon' => '✅'],
                ['label' => 'Daftar Ulang', 'desc' => 'Validasi DU camaba', 'url' => '/admin/re-registrations', 'icon' => '🎓'],
                ['label' => 'Follow Up', 'desc' => 'CRM dan reminder WA', 'url' => '/admin/follow-ups', 'icon' => '💬'],
                ['label' => 'Laporan', 'desc' => 'Rekap pimpinan', 'url' => '/admin/reports', 'icon' => '📊'],
                ['label' => 'Master Data', 'desc' => 'Konfigurasi PMB', 'url' => '/admin/master-data', 'icon' => '⚙️'],
                ['label' => 'Integrasi', 'desc' => 'Sinkron ke SIAKAD', 'url' => '/admin/integrations', 'icon' => '🔄'],
                ['label' => 'Landing PMB', 'desc' => 'Lihat halaman publik', 'url' => '/', 'icon' => '🌐'],
            ] as $module)
                <a href="{{ url($module['url']) }}"
                   class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-polmind-blue hover:bg-blue-50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                        {{ $module['icon'] }}
                    </div>

                    <h3 class="mt-4 text-sm font-black text-polmind-blue">
                        {{ $module['label'] }}
                    </h3>

                    <p class="mt-2 text-xs leading-5 text-slate-600">
                        {{ $module['desc'] }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Main Column --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Funnel --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Funnel PMB
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Progres camaba dari registrasi awal sampai siap sinkron akademik.
                    </p>
                </div>

                <div class="mt-6 space-y-5">
                    @foreach($funnel as $item)
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-slate-900">
                                        {{ $item['label'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $item['value'] }} camaba
                                    </p>
                                </div>

                                <p class="text-sm font-black text-polmind-blue">
                                    {{ $item['percent'] }}%
                                </p>
                            </div>

                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $item['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Prodi Distribution --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Sebaran Program Studi
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Ringkasan pendaftar dan daftar ulang berdasarkan program studi.
                        </p>
                    </div>

                    <a href="{{ url('/admin/reports') }}"
                       class="rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                        Detail Laporan
                    </a>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    @foreach($programs as $program)
                        @php
                            $target = max($program->quota, 1);
                            $percent = round(($program->re_registered_count / $target) * 100);
                        @endphp

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-xl font-black text-polmind-blue">
                                        {{ $program->code }}
                                    </h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        {{ $program->name }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $program->re_registered_count }}/{{ $program->quota }}
                                </span>
                            </div>

                            <div class="mt-5">
                                <div class="flex justify-between text-xs font-bold text-slate-600">
                                    <span>Pendaftar: {{ $program->registrants_count }}</span>
                                    <span>{{ $percent }}%</span>
                                </div>

                                <div class="mt-2 h-3 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($percent, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Aktivitas Terbaru
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Log aktivitas PMB yang perlu dipantau admin.
                        </p>
                    </div>

                    <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600">
                        UI Dummy
                    </span>
                </div>

                <div class="mt-6 space-y-5">
                    @foreach([
                        ['title' => 'Bukti pembayaran Ahmad Fauzi masuk', 'desc' => 'Menunggu verifikasi admin keuangan.', 'time' => '5 menit lalu', 'icon' => '💳'],
                        ['title' => 'Siti Aminah melakukan daftar ulang', 'desc' => 'Status pembayaran daftar ulang menunggu validasi.', 'time' => '20 menit lalu', 'icon' => '🎓'],
                        ['title' => 'Kartu Keluarga Dewi Lestari ditolak', 'desc' => 'Camaba perlu upload ulang dokumen.', 'time' => '1 jam lalu', 'icon' => '📄'],
                        ['title' => 'Data Nabila Putri siap sinkron', 'desc' => 'Masuk ke antrian integrasi SIAKAD.', 'time' => '2 jam lalu', 'icon' => '🔄'],
                    ] as $activity)
                        <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-xl shadow-sm">
                                {{ $activity['icon'] }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                                    <div>
                                        <p class="font-black text-slate-900">
                                            {{ $activity['title'] }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            {{ $activity['desc'] }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 text-xs font-bold text-slate-500">
                                        {{ $activity['time'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Side Column --}}
        <aside class="space-y-6">

            {{-- Attention Needed --}}
            <div class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-2xl">
                    🔔
                </div>

                <h3 class="mt-5 text-lg font-black text-red-800">
                    Perlu Tindak Lanjut
                </h3>

                <p class="mt-3 text-sm leading-6 text-red-800">
                    Ada beberapa data yang perlu diproses agar flow PMB tidak tertahan.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach([
                        ['label' => 'Berkas menunggu', 'value' => $pendingDocuments, 'url' => '/admin/documents'],
                        ['label' => 'Pembayaran menunggu', 'value' => $pendingPayments, 'url' => '/admin/payments'],
                        ['label' => 'Siap sinkron SIAKAD', 'value' => $readySyncApplicants, 'url' => '/admin/integrations'],
                    ] as $item)
                        <a href="{{ url($item['url']) }}"
                           class="flex items-center justify-between rounded-2xl bg-white/70 p-4 transition hover:bg-white">
                            <span class="text-sm font-bold text-red-800">{{ $item['label'] }}</span>
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">
                                {{ $item['value'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Sync Status --}}
            <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-2xl">
                    🔄
                </div>

                <h3 class="mt-5 text-lg font-black text-polmind-blue">
                    Status Integrasi
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Data daftar ulang valid akan disiapkan untuk sinkronisasi ke SIAKAD.
                </p>

                <div class="mt-5 rounded-2xl bg-white p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                        Siap Sinkron
                    </p>
                    <p class="mt-2 text-3xl font-black text-polmind-blue">
                        {{ $readySyncApplicants }} Data
                    </p>
                </div>

                <a href="{{ url('/admin/integrations') }}"
                   class="mt-5 inline-flex w-full justify-center rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                    Buka Integrasi
                </a>
            </div>

            {{-- Quick Report --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Laporan Singkat
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Ringkasan sementara untuk bahan laporan pimpinan.
                </p>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm leading-6 text-slate-700">
                        Total pendaftar sementara <span class="font-black text-polmind-blue">{{ $totalApplicants }} camaba</span>,
                        dengan <span class="font-black text-green-700">{{ $acceptedApplicants }} diterima</span> dan
                        <span class="font-black text-polmind-blue">{{ $validReRegistrations }} sudah daftar ulang valid</span>.
                    </p>
                </div>

                <button type="button"
                        onclick="Swal.fire({
                            title: 'Generate Laporan',
                            text: 'Fitur generate laporan otomatis akan dibuat setelah backend laporan aktif.',
                            icon: 'info',
                            confirmButtonColor: '#003B82'
                        })"
                        class="mt-5 w-full rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                    Generate Narasi
                </button>
            </div>

            {{-- Admin Notes --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Catatan Pengembangan
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Dashboard masih memakai data dummy.</li>
                    <li>• Selanjutnya data akan ditarik dari database.</li>
                    <li>• Shortcut sudah diarahkan ke modul admin.</li>
                    <li>• Bug minor UI bisa masuk tahap polishing.</li>
                </ul>
            </div>

        </aside>
    </div>

</div>
@endsection