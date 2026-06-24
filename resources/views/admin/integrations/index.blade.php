@extends('layouts.admin')

@section('title', 'Integrasi SIAKAD')
@section('page_title', 'Integrasi SIAKAD')
@section('page_subtitle', 'Pantau data camaba yang siap disinkronkan ke sistem akademik.')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                    Integrasi PMB → SIAKAD
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Sinkronisasi Data Mahasiswa Baru
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Data camaba yang sudah daftar ulang valid akan masuk ke antrian siap sinkron.
                    Pada tahap integrasi, SIAKAD akan mengambil data dari PMB dan mengembalikan NIM setelah data berhasil dibuat.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        Status: Mode Persiapan
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Target Integrasi Juli 2026
                    </span>
                </div>
            </div>

            <div class="rounded-3xl bg-white/10 p-6">
                <p class="text-sm font-bold text-blue-100">Siap Sinkron</p>
                <p class="mt-3 text-5xl font-black">12</p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full w-[43%] rounded-full bg-polmind-yellow"></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Dari 28 camaba yang sudah dinyatakan diterima.
                </p>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Siap Sinkron', 'value' => '12', 'desc' => 'Daftar ulang valid', 'class' => 'bg-blue-100 text-polmind-blue'],
            ['label' => 'Berhasil Sinkron', 'value' => '0', 'desc' => 'Sudah masuk SIAKAD', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Gagal Sinkron', 'value' => '0', 'desc' => 'Perlu pengecekan ulang', 'class' => 'bg-red-100 text-red-700'],
            ['label' => 'Menunggu NIM', 'value' => '12', 'desc' => 'NIM dibuat oleh SIAKAD', 'class' => 'bg-yellow-100 text-yellow-700'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        API
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Main Content --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Integration Flow --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Alur Integrasi Data
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Flow sementara yang akan digunakan untuk integrasi PMB dengan SIAKAD.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-4">
                    @foreach([
                        ['title' => 'DU Valid', 'desc' => 'Camaba sudah daftar ulang valid.', 'icon' => '✅'],
                        ['title' => 'Siap Sinkron', 'desc' => 'Data masuk antrian sinkron.', 'icon' => '📦'],
                        ['title' => 'SIAKAD Pull', 'desc' => 'SIAKAD mengambil data PMB.', 'icon' => '🔄'],
                        ['title' => 'NIM Dibuat', 'desc' => 'SIAKAD mengembalikan NIM.', 'icon' => '🎓'],
                    ] as $index => $flow)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                                {{ $flow['icon'] }}
                            </div>

                            <p class="mt-4 text-xs font-black text-polmind-blue">
                                {{ $index + 1 }}. {{ $flow['title'] }}
                            </p>

                            <p class="mt-2 text-xs leading-5 text-slate-600">
                                {{ $flow['desc'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Filter --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Filter Data Sinkron
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Cari data berdasarkan nama, nomor pendaftaran, prodi, status sinkron, atau status NIM.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Data Siap Sinkron',
                                text: 'Export data siap sinkron akan dihubungkan setelah backend aktif.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                        Export
                    </button>
                </div>

                <form action="#" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <label class="text-sm font-bold text-slate-700">Pencarian</label>
                        <input type="text"
                               name="keyword"
                               placeholder="Nama / no. pendaftaran / NISN / NIM"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Program Studi</label>
                        <select name="study_program"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Semua Prodi</option>
                            <option>TRPL</option>
                            <option>Bisnis Digital</option>
                            <option>TRM</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Status Sinkron</label>
                        <select name="sync_status"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Semua Status</option>
                            <option>Belum Siap</option>
                            <option>Siap Sinkron</option>
                            <option>Proses Sinkron</option>
                            <option>Berhasil Sinkron</option>
                            <option>Gagal Sinkron</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Status NIM</label>
                        <select name="nim_status"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Semua Status</option>
                            <option>Belum Ada NIM</option>
                            <option>Sudah Ada NIM</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3 xl:col-span-5">
                        <button type="submit"
                                class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                            Terapkan Filter
                        </button>

                        <a href="{{ url('/admin/integrations') }}"
                           class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Sync Queue --}}
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Antrian Sinkronisasi
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Data camaba yang sudah daftar ulang valid dan siap masuk ke SIAKAD.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-100 px-4 py-2 text-xs font-black text-polmind-blue">
                        12 Siap Sinkron
                    </span>
                </div>

                @php
                    $syncData = [
                        [
                            'registration' => 'PMB20260002',
                            'name' => 'Siti Aminah',
                            'program' => 'Bisnis Digital',
                            'class' => 'Reguler A',
                            'du_status' => 'Daftar Ulang Valid',
                            'sync_status' => 'Siap Sinkron',
                            'sync_key' => 'ready',
                            'nim' => '-',
                            'updated_at' => '24 Juni 2026 08:30',
                        ],
                        [
                            'registration' => 'PMB20260004',
                            'name' => 'Dewi Lestari',
                            'program' => 'TRPL',
                            'class' => 'Reguler A',
                            'du_status' => 'Daftar Ulang Valid',
                            'sync_status' => 'Siap Sinkron',
                            'sync_key' => 'ready',
                            'nim' => '-',
                            'updated_at' => '24 Juni 2026 09:15',
                        ],
                        [
                            'registration' => 'PMB20260006',
                            'name' => 'Fajar Nugraha',
                            'program' => 'TRM',
                            'class' => 'Reguler B',
                            'du_status' => 'Daftar Ulang Valid',
                            'sync_status' => 'Proses Sinkron',
                            'sync_key' => 'process',
                            'nim' => '-',
                            'updated_at' => '24 Juni 2026 10:02',
                        ],
                        [
                            'registration' => 'PMB20260007',
                            'name' => 'Nabila Putri',
                            'program' => 'TRPL',
                            'class' => 'Reguler A',
                            'du_status' => 'Daftar Ulang Valid',
                            'sync_status' => 'Berhasil Sinkron',
                            'sync_key' => 'success',
                            'nim' => '26010001',
                            'updated_at' => '24 Juni 2026 10:45',
                        ],
                        [
                            'registration' => 'PMB20260008',
                            'name' => 'Raka Maulana',
                            'program' => 'Bisnis Digital',
                            'class' => 'Reguler B',
                            'du_status' => 'Daftar Ulang Valid',
                            'sync_status' => 'Gagal Sinkron',
                            'sync_key' => 'failed',
                            'nim' => '-',
                            'updated_at' => '24 Juni 2026 11:20',
                        ],
                    ];

                    $badgeClasses = [
                        'ready' => 'bg-blue-100 text-polmind-blue',
                        'process' => 'bg-yellow-100 text-yellow-700',
                        'success' => 'bg-green-100 text-green-700',
                        'failed' => 'bg-red-100 text-red-700',
                    ];
                @endphp

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Camaba</th>
                                <th class="px-6 py-4">Prodi</th>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4">Status DU</th>
                                <th class="px-6 py-4">Status Sinkron</th>
                                <th class="px-6 py-4">NIM</th>
                                <th class="px-6 py-4">Update</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($syncData as $item)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                                {{ collect(explode(' ', $item['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                            </div>

                                            <div>
                                                <p class="font-bold text-slate-900">{{ $item['name'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $item['registration'] }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                            {{ $item['program'] }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 font-semibold text-slate-700">
                                        {{ $item['class'] }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                            {{ $item['du_status'] }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClasses[$item['sync_key']] }}">
                                            {{ $item['sync_status'] }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5">
                                        @if($item['nim'] !== '-')
                                            <span class="font-black text-polmind-blue">{{ $item['nim'] }}</span>
                                        @else
                                            <span class="text-slate-500">Belum ada</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-5 text-slate-600">
                                        {{ $item['updated_at'] }}
                                    </td>

                                    <td class="px-6 py-5 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ url('/admin/applicants/' . $item['registration']) }}"
                                               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                                Detail
                                            </a>

                                            <button type="button"
                                                    onclick="Swal.fire({
                                                        title: 'Sinkron Ulang?',
                                                        text: 'Data akan dikirim ulang ke antrian sinkron SIAKAD.',
                                                        icon: 'question',
                                                        showCancelButton: true,
                                                        confirmButtonText: 'Ya, Sinkron',
                                                        cancelButtonText: 'Batal',
                                                        confirmButtonColor: '#003B82'
                                                    })"
                                                    class="rounded-xl bg-polmind-blue px-3 py-2 text-xs font-bold text-white hover:bg-polmind-blue-dark">
                                                Sinkron
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col justify-between gap-4 border-t border-slate-200 p-6 md:flex-row md:items-center">
                    <p class="text-sm text-slate-500">
                        Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">12</span> data siap sinkron
                    </p>

                    <div class="flex gap-2">
                        <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-500">
                            Sebelumnya
                        </button>
                        <button class="rounded-xl bg-polmind-blue px-4 py-2 text-sm font-bold text-white">
                            1
                        </button>
                        <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                            2
                        </button>
                    </div>
                </div>
            </div>

            {{-- API Endpoint --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Endpoint Integrasi
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Draft endpoint yang akan digunakan SIAKAD untuk komunikasi dengan PMB.
                    </p>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach([
                        ['method' => 'GET', 'url' => '/api/pmb/applicants/ready-sync', 'desc' => 'Mengambil daftar camaba siap sinkron.'],
                        ['method' => 'GET', 'url' => '/api/pmb/applicants/{id}', 'desc' => 'Mengambil detail data camaba.'],
                        ['method' => 'POST', 'url' => '/api/pmb/applicants/{id}/mark-synced', 'desc' => 'Menandai data berhasil diambil SIAKAD.'],
                        ['method' => 'POST', 'url' => '/api/pmb/applicants/{id}/receive-nim', 'desc' => 'Menerima NIM dari SIAKAD.'],
                    ] as $endpoint)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-full bg-polmind-blue px-3 py-1 text-xs font-black text-white">
                                            {{ $endpoint['method'] }}
                                        </span>
                                        <code class="text-sm font-black text-slate-900">{{ $endpoint['url'] }}</code>
                                    </div>

                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        {{ $endpoint['desc'] }}
                                    </p>
                                </div>

                                <button type="button"
                                        onclick="Swal.fire({
                                            title: 'Endpoint API',
                                            text: 'Endpoint ini akan dibuat pada tahap backend API.',
                                            icon: 'info',
                                            confirmButtonColor: '#003B82'
                                        })"
                                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                    Detail
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Side Panel --}}
        <aside class="space-y-6">

            {{-- Integration Status --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    ⚠️
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Status Integrasi
                </h3>

                <p class="mt-3 text-sm leading-6 text-yellow-800">
                    Saat ini halaman masih berupa UI persiapan. Sinkronisasi aktual akan aktif setelah API PMB dan endpoint SIAKAD selesai dibuat.
                </p>

                <div class="mt-5 rounded-2xl bg-white/70 p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-yellow-800">
                        Mode Saat Ini
                    </p>
                    <p class="mt-2 text-lg font-black text-yellow-900">
                        Simulasi / UI Only
                    </p>
                </div>
            </div>

            {{-- Sync Requirements --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Syarat Siap Sinkron
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data hanya boleh dikirim ke SIAKAD jika memenuhi syarat berikut.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach([
                        'Biodata camaba lengkap',
                        'Berkas wajib valid',
                        'Pembayaran pendaftaran valid',
                        'Dinyatakan diterima',
                        'Daftar ulang valid',
                        'Belum memiliki NIM',
                    ] as $requirement)
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100 text-xs font-black text-green-700">
                                ✓
                            </div>

                            <p class="text-sm font-bold leading-6 text-slate-700">
                                {{ $requirement }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Aksi Cepat
                </h2>

                <div class="mt-5 space-y-3">
                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Sinkron Semua Data?',
                                text: 'Semua data yang sudah siap akan masuk ke antrian sinkron.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Sinkron',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Sinkron Semua
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Cek Status API',
                                text: 'Health check API akan dibuat setelah endpoint backend aktif.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Cek Status API
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Log Sinkron',
                                text: 'Export log sinkronisasi akan dibuat pada tahap backend.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Log
                    </button>
                </div>
            </div>

            {{-- Sync Log --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Log Terbaru
                </h2>

                <div class="mt-5 space-y-4">
                    @foreach([
                        ['title' => 'Data siap sinkron dibuat', 'time' => '24 Juni 2026 08:30'],
                        ['title' => 'Daftar ulang valid diterima', 'time' => '24 Juni 2026 09:15'],
                        ['title' => 'Simulasi endpoint API', 'time' => '24 Juni 2026 10:00'],
                    ] as $log)
                        <div class="flex gap-3">
                            <div class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                i
                            </div>

                            <div>
                                <p class="text-sm font-black text-slate-900">
                                    {{ $log['title'] }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $log['time'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection