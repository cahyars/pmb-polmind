@extends('layouts.admin')

@section('title', 'Daftar Ulang')
@section('page_title', 'Daftar Ulang')
@section('page_subtitle', 'Kelola validasi daftar ulang calon mahasiswa yang telah diterima.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Diterima', 'value' => '28', 'desc' => 'Camaba lolos seleksi', 'class' => 'bg-blue-100 text-polmind-blue'],
            ['label' => 'Menunggu Daftar Ulang', 'value' => '16', 'desc' => 'Belum valid pembayaran DU', 'class' => 'bg-yellow-100 text-yellow-700'],
            ['label' => 'Daftar Ulang Valid', 'value' => '12', 'desc' => 'Siap sinkron akademik', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Gagal / Lewat Batas', 'value' => '0', 'desc' => 'Perlu tindak lanjut', 'class' => 'bg-red-100 text-red-700'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        DU
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Filter Daftar Ulang
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cari data daftar ulang berdasarkan nama, nomor pendaftaran, prodi, gelombang, atau status daftar ulang.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Export Daftar Ulang',
                        text: 'Fitur export daftar ulang akan dihubungkan setelah database siap.',
                        icon: 'info',
                        confirmButtonColor: '#003B82'
                    })"
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Export Excel
            </button>
        </div>

        <form action="#" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       placeholder="Nama / no. pendaftaran / NISN"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Gelombang</label>
                <select name="wave"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Gelombang</option>
                    <option>Gelombang 1</option>
                    <option>Gelombang 2</option>
                    <option>Gelombang 3</option>
                </select>
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
                <label class="text-sm font-bold text-slate-700">Status DU</label>
                <select name="re_registration_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    <option>Belum Daftar Ulang</option>
                    <option>Menunggu Verifikasi</option>
                    <option>Daftar Ulang Valid</option>
                    <option>Siap Sinkron SIAKAD</option>
                    <option>Gagal / Lewat Batas</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/re-registrations') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Re-registration Table --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">
                        Data Daftar Ulang
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data camaba diterima yang sedang atau sudah melakukan daftar ulang.
                    </p>
                </div>

                <span class="rounded-full bg-green-100 px-4 py-2 text-xs font-black text-green-700">
                    12 Valid
                </span>
            </div>

            @php
                $students = [
                    [
                        'registration' => 'PMB20260001',
                        'name' => 'Ahmad Fauzi',
                        'program' => 'TRPL',
                        'wave' => 'Gel. 2',
                        'invoice' => 'INV/DU/2026/0001',
                        'amount' => 'Rp11.500.000',
                        'payment' => 'Menunggu Verifikasi',
                        'du_status' => 'Menunggu Verifikasi',
                        'sync' => 'Belum Siap',
                        'status_key' => 'waiting',
                    ],
                    [
                        'registration' => 'PMB20260002',
                        'name' => 'Siti Aminah',
                        'program' => 'Bisnis Digital',
                        'wave' => 'Gel. 2',
                        'invoice' => 'INV/DU/2026/0002',
                        'amount' => 'Rp11.500.000',
                        'payment' => 'Valid',
                        'du_status' => 'Daftar Ulang Valid',
                        'sync' => 'Siap Sinkron',
                        'status_key' => 'valid',
                    ],
                    [
                        'registration' => 'PMB20260003',
                        'name' => 'Budi Santoso',
                        'program' => 'TRM',
                        'wave' => 'Gel. 2',
                        'invoice' => 'INV/DU/2026/0003',
                        'amount' => 'Rp11.500.000',
                        'payment' => 'Belum Bayar',
                        'du_status' => 'Belum Daftar Ulang',
                        'sync' => 'Belum Siap',
                        'status_key' => 'not_paid',
                    ],
                    [
                        'registration' => 'PMB20260004',
                        'name' => 'Dewi Lestari',
                        'program' => 'TRPL',
                        'wave' => 'Gel. 1',
                        'invoice' => 'INV/DU/2026/0004',
                        'amount' => 'Rp11.500.000',
                        'payment' => 'Valid',
                        'du_status' => 'Daftar Ulang Valid',
                        'sync' => 'Siap Sinkron',
                        'status_key' => 'valid',
                    ],
                    [
                        'registration' => 'PMB20260005',
                        'name' => 'Rizky Pratama',
                        'program' => 'Bisnis Digital',
                        'wave' => 'Gel. 1',
                        'invoice' => 'INV/DU/2026/0005',
                        'amount' => 'Rp11.500.000',
                        'payment' => 'Ditolak',
                        'du_status' => 'Perlu Revisi',
                        'sync' => 'Belum Siap',
                        'status_key' => 'rejected',
                    ],
                ];

                $badgeClasses = [
                    'waiting' => 'bg-yellow-100 text-yellow-700',
                    'valid' => 'bg-green-100 text-green-700',
                    'not_paid' => 'bg-slate-100 text-slate-600',
                    'rejected' => 'bg-red-100 text-red-700',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Camaba</th>
                            <th class="px-6 py-4">Invoice DU</th>
                            <th class="px-6 py-4">Nominal</th>
                            <th class="px-6 py-4">Pembayaran</th>
                            <th class="px-6 py-4">Status DU</th>
                            <th class="px-6 py-4">Sinkron</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($students as $student)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                            {{ collect(explode(' ', $student['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $student['name'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $student['registration'] }} · {{ $student['program'] }} · {{ $student['wave'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-polmind-blue">{{ $student['invoice'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-slate-900">{{ $student['amount'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClasses[$student['status_key']] }}">
                                        {{ $student['payment'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-bold text-slate-900">{{ $student['du_status'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    @if($student['sync'] === 'Siap Sinkron')
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                                            {{ $student['sync'] }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                            {{ $student['sync'] }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ url('/admin/applicants/' . $student['registration']) }}"
                                           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            Detail
                                        </a>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Validasi Daftar Ulang?',
                                                    text: 'Status camaba akan menjadi daftar ulang valid dan siap sinkron akademik.',
                                                    icon: 'question',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Validasi',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#16A34A'
                                                })"
                                                class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                                            Validasi
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tolak Daftar Ulang?',
                                                    input: 'textarea',
                                                    inputLabel: 'Catatan Penolakan',
                                                    inputPlaceholder: 'Contoh: Bukti pembayaran tidak sesuai / nominal kurang.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Tolak',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#DC2626'
                                                })"
                                                class="rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-700">
                                            Tolak
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
                    Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">28</span> camaba diterima
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
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        {{-- Side Panel --}}
        <aside class="space-y-6">

            {{-- Sync Preparation --}}
            <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-2xl">
                    🔄
                </div>

                <h3 class="mt-5 text-lg font-black text-polmind-blue">
                    Persiapan Sinkron SIAKAD
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Camaba dengan status daftar ulang valid akan masuk ke daftar
                    <span class="font-bold text-polmind-blue">siap sinkron SIAKAD</span> pada tahap integrasi bulan Juli.
                </p>

                <div class="mt-5 rounded-2xl bg-white p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                        Siap Sinkron
                    </p>
                    <p class="mt-2 text-3xl font-black text-polmind-blue">
                        12 Data
                    </p>
                </div>
            </div>

            {{-- Validation Guide --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Panduan Validasi DU
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Pastikan camaba sudah dinyatakan diterima.</li>
                    <li>• Pastikan pembayaran daftar ulang valid.</li>
                    <li>• Pastikan nominal sesuai tagihan.</li>
                    <li>• Tandai valid hanya jika data sudah lengkap.</li>
                    <li>• Status valid akan menjadi dasar sinkron akademik.</li>
                </ul>
            </div>

            {{-- Quick Actions --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Aksi Cepat
                </h2>

                <div class="mt-5 space-y-3">
                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Generate Data Siap Sinkron',
                                text: 'Fitur ini akan menghasilkan daftar camaba daftar ulang valid untuk integrasi SIAKAD.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Generate Siap Sinkron
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Daftar Ulang',
                                text: 'Export data daftar ulang akan dihubungkan setelah backend siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Data DU
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Reminder Camaba',
                                text: 'Fitur reminder WhatsApp manual akan dibuat pada modul follow up.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Reminder Belum DU
                    </button>
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection