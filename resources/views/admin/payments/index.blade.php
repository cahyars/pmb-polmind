@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran')
@section('page_title', 'Verifikasi Pembayaran')
@section('page_subtitle', 'Validasi pembayaran pendaftaran dan daftar ulang calon mahasiswa.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Menunggu Verifikasi', 'value' => '18', 'desc' => 'Perlu dicek keuangan', 'class' => 'bg-yellow-100 text-yellow-700'],
            ['label' => 'Pembayaran Valid', 'value' => '72', 'desc' => 'Sudah diterima', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Ditolak/Revisi', 'value' => '6', 'desc' => 'Perlu upload ulang', 'class' => 'bg-red-100 text-red-700'],
            ['label' => 'Total Masuk', 'value' => 'Rp25,2 jt', 'desc' => 'Estimasi pembayaran valid', 'class' => 'bg-blue-100 text-polmind-blue'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        PMB
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
                    Filter Pembayaran
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cari pembayaran berdasarkan nama camaba, nomor pendaftaran, invoice, atau status pembayaran.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Export Pembayaran',
                        text: 'Fitur export pembayaran akan dihubungkan setelah database siap.',
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
                       placeholder="Nama / no. pendaftaran / invoice"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jenis Tagihan</label>
                <select name="bill_type"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Tagihan</option>
                    <option>Biaya Pendaftaran</option>
                    <option>Daftar Ulang</option>
                    <option>SPI</option>
                    <option>SPP Semester 1</option>
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
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    <option>Menunggu Verifikasi</option>
                    <option>Valid</option>
                    <option>Ditolak</option>
                    <option>Belum Bayar</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/payments') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Payment Queue --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">
                        Antrian Pembayaran Masuk
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Daftar pembayaran yang menunggu validasi admin/keuangan.
                    </p>
                </div>

                <span class="rounded-full bg-yellow-100 px-4 py-2 text-xs font-black text-yellow-700">
                    18 Menunggu
                </span>
            </div>

            @php
                $payments = [
                    [
                        'invoice' => 'INV/PMB/2026/0001',
                        'registration' => 'PMB20260001',
                        'name' => 'Ahmad Fauzi',
                        'program' => 'TRPL',
                        'bill' => 'Biaya Pendaftaran',
                        'amount' => 'Rp350.000',
                        'bank' => 'BCA',
                        'sender' => 'Ahmad Fauzi',
                        'date' => '22 Juni 2026',
                        'proof' => 'bukti-transfer-ahmad.jpg',
                        'status' => 'Menunggu Verifikasi',
                        'status_key' => 'waiting',
                    ],
                    [
                        'invoice' => 'INV/PMB/2026/0002',
                        'registration' => 'PMB20260002',
                        'name' => 'Siti Aminah',
                        'program' => 'Bisnis Digital',
                        'bill' => 'Biaya Pendaftaran',
                        'amount' => 'Rp350.000',
                        'bank' => 'BRI',
                        'sender' => 'Siti Aminah',
                        'date' => '22 Juni 2026',
                        'proof' => 'transfer-siti.png',
                        'status' => 'Valid',
                        'status_key' => 'valid',
                    ],
                    [
                        'invoice' => 'INV/DU/2026/0003',
                        'registration' => 'PMB20260003',
                        'name' => 'Budi Santoso',
                        'program' => 'TRM',
                        'bill' => 'Daftar Ulang',
                        'amount' => 'Rp11.500.000',
                        'bank' => 'Mandiri',
                        'sender' => 'Budi Santoso',
                        'date' => '23 Juni 2026',
                        'proof' => 'du-budi.pdf',
                        'status' => 'Menunggu Verifikasi',
                        'status_key' => 'waiting',
                    ],
                    [
                        'invoice' => 'INV/PMB/2026/0004',
                        'registration' => 'PMB20260004',
                        'name' => 'Dewi Lestari',
                        'program' => 'TRPL',
                        'bill' => 'Biaya Pendaftaran',
                        'amount' => 'Rp350.000',
                        'bank' => 'BNI',
                        'sender' => 'Dewi Lestari',
                        'date' => '23 Juni 2026',
                        'proof' => 'transfer-dewi.jpg',
                        'status' => 'Ditolak',
                        'status_key' => 'rejected',
                    ],
                    [
                        'invoice' => 'INV/DU/2026/0005',
                        'registration' => 'PMB20260005',
                        'name' => 'Rizky Pratama',
                        'program' => 'Bisnis Digital',
                        'bill' => 'Daftar Ulang',
                        'amount' => 'Rp11.500.000',
                        'bank' => 'BCA',
                        'sender' => 'Orang Tua Rizky',
                        'date' => '24 Juni 2026',
                        'proof' => 'du-rizky.png',
                        'status' => 'Menunggu Verifikasi',
                        'status_key' => 'waiting',
                    ],
                ];

                $badgeClasses = [
                    'waiting' => 'bg-yellow-100 text-yellow-700',
                    'valid' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Camaba</th>
                            <th class="px-6 py-4">Invoice</th>
                            <th class="px-6 py-4">Tagihan</th>
                            <th class="px-6 py-4">Nominal</th>
                            <th class="px-6 py-4">Pengirim</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($payments as $payment)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                            {{ collect(explode(' ', $payment['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $payment['name'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $payment['registration'] }} · {{ $payment['program'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-polmind-blue">{{ $payment['invoice'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $payment['date'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-bold text-slate-900">{{ $payment['bill'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Bukti: {{ $payment['proof'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-black text-slate-900">{{ $payment['amount'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-semibold text-slate-700">{{ $payment['sender'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Bank {{ $payment['bank'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClasses[$payment['status_key']] }}">
                                        {{ $payment['status'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Preview Bukti Transfer',
                                                    text: 'Preview bukti pembayaran akan dihubungkan ke storage setelah backend siap.',
                                                    icon: 'info',
                                                    confirmButtonColor: '#003B82'
                                                })"
                                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            Preview
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Validasi Pembayaran?',
                                                    text: 'Pembayaran akan ditandai valid dan status tagihan akan diperbarui.',
                                                    icon: 'question',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Validasi',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#16A34A'
                                                })"
                                                class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                                            Valid
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tolak Pembayaran?',
                                                    input: 'textarea',
                                                    inputLabel: 'Catatan Penolakan',
                                                    inputPlaceholder: 'Contoh: Nominal tidak sesuai / bukti transfer kurang jelas.',
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
                    Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">18</span> pembayaran menunggu
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

            {{-- Proof Preview --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Preview Bukti Bayar
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    File bukti transfer akan tampil di panel ini setelah dipilih.
                </p>

                <div class="mt-6 flex aspect-[3/4] items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                    <div>
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                            🧾
                        </div>
                        <p class="mt-4 text-sm font-black text-polmind-blue">
                            Belum ada bukti dipilih
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Klik tombol preview pada salah satu data pembayaran.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Validation Guide --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    ⚠️
                </div>
                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Panduan Validasi
                </h3>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Pastikan nominal pembayaran sesuai tagihan.</li>
                    <li>• Pastikan nama pengirim dan tanggal transfer sesuai.</li>
                    <li>• Cocokkan mutasi rekening sebelum validasi.</li>
                    <li>• Tolak jika bukti tidak jelas atau tidak sesuai.</li>
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
                                title: 'Rekap Pembayaran',
                                text: 'Rekap pembayaran akan dibuat pada modul laporan.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Lihat Rekap
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Data',
                                text: 'Export data pembayaran akan dihubungkan setelah backend siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Pembayaran
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Cek Mutasi',
                                text: 'Integrasi mutasi bank belum tersedia pada versi MVP.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Cek Mutasi Bank
                    </button>
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection