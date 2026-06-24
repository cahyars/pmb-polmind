@extends('layouts.admin')

@section('title', 'Master Data')
@section('page_title', 'Master Data')
@section('page_subtitle', 'Kelola data referensi utama untuk kebutuhan sistem PMB.')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                    Konfigurasi Sistem PMB
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Master Data PMB
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Halaman ini digunakan untuk mengatur data dasar sistem PMB seperti tahun penerimaan,
                    gelombang, program studi, biaya, berkas, wilayah, sekolah, dan template komunikasi.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        Mode: Admin
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Data Referensi
                    </span>
                </div>
            </div>

            <div class="rounded-3xl bg-white/10 p-6">
                <p class="text-sm font-bold text-blue-100">Total Referensi</p>
                <p class="mt-3 text-5xl font-black">8</p>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Master data ini akan menjadi dasar proses registrasi, seleksi, pembayaran, dan sinkronisasi.
                </p>
            </div>
        </div>
    </div>

    {{-- Master Data Menu Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['title' => 'Tahun PMB', 'count' => '2 Data', 'desc' => 'Periode penerimaan mahasiswa baru', 'icon' => '📅'],
            ['title' => 'Gelombang', 'count' => '3 Data', 'desc' => 'Gelombang pendaftaran aktif', 'icon' => '🌊'],
            ['title' => 'Program Studi', 'count' => '3 Data', 'desc' => 'TRPL, TRM, dan Bisnis Digital', 'icon' => '🎓'],
            ['title' => 'Komponen Biaya', 'count' => '5 Data', 'desc' => 'Pendaftaran, daftar ulang, SPI, SPP', 'icon' => '💳'],
            ['title' => 'Jenis Berkas', 'count' => '6 Data', 'desc' => 'Dokumen wajib dan opsional', 'icon' => '📄'],
            ['title' => 'Master Wilayah', 'count' => 'Import', 'desc' => 'Provinsi, kota, kecamatan, desa', 'icon' => '🗺️'],
            ['title' => 'Master Sekolah', 'count' => 'Import', 'desc' => 'Data sekolah berbasis NPSN', 'icon' => '🏫'],
            ['title' => 'Template WA', 'count' => '5 Data', 'desc' => 'Template pesan follow up PMB', 'icon' => '💬'],
        ] as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-2xl">
                        {{ $item['icon'] }}
                    </div>

                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                        {{ $item['count'] }}
                    </span>
                </div>

                <h3 class="mt-5 text-lg font-black text-polmind-blue">
                    {{ $item['title'] }}
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    {{ $item['desc'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Main Master Data --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Academic Year --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Tahun PMB
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Atur tahun penerimaan mahasiswa baru yang tersedia di sistem.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Tambah Tahun PMB',
                                text: 'Form tambah tahun PMB akan dibuat setelah backend siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Tambah Tahun
                    </button>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Tahun</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4">Tanggal Mulai</th>
                                <th class="px-5 py-4">Tanggal Selesai</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr>
                                <td class="px-5 py-4 font-black text-polmind-blue">PMB 2026</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">01 Januari 2026</td>
                                <td class="px-5 py-4 text-slate-600">31 Agustus 2026</td>
                                <td class="px-5 py-4 text-right">
                                    <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-5 py-4 font-black text-polmind-blue">PMB 2025</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                        Arsip
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">01 Januari 2025</td>
                                <td class="px-5 py-4 text-slate-600">31 Agustus 2025</td>
                                <td class="px-5 py-4 text-right">
                                    <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Waves --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Gelombang Pendaftaran
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Kelola jadwal dan status gelombang pendaftaran.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Tambah Gelombang',
                                text: 'Form tambah gelombang akan dihubungkan setelah backend siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Tambah Gelombang
                    </button>
                </div>

                @php
                    $waves = [
                        ['name' => 'Gelombang 1', 'period' => 'Jan - Mar 2026', 'status' => 'Selesai', 'class' => 'bg-slate-100 text-slate-600'],
                        ['name' => 'Gelombang 2', 'period' => 'Apr - Jun 2026', 'status' => 'Aktif', 'class' => 'bg-green-100 text-green-700'],
                        ['name' => 'Gelombang 3', 'period' => 'Jul - Agu 2026', 'status' => 'Belum Dibuka', 'class' => 'bg-yellow-100 text-yellow-700'],
                    ];
                @endphp

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach($waves as $wave)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-black text-slate-900">{{ $wave['name'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $wave['period'] }}</p>
                                </div>

                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $wave['class'] }}">
                                    {{ $wave['status'] }}
                                </span>
                            </div>

                            <button class="mt-5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                Edit
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Study Programs --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Program Studi
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Data program studi yang tersedia pada formulir pendaftaran.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Tambah Program Studi',
                                text: 'Form tambah program studi akan dibuat setelah backend aktif.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Tambah Prodi
                    </button>
                </div>

                @php
                    $programs = [
                        ['code' => 'TRPL', 'name' => 'D4 Teknologi Rekayasa Perangkat Lunak', 'quota' => 40, 'status' => 'Aktif'],
                        ['code' => 'TRM', 'name' => 'D4 Teknologi Rekayasa Manufaktur', 'quota' => 40, 'status' => 'Aktif'],
                        ['code' => 'BD', 'name' => 'D4 Bisnis Digital', 'quota' => 40, 'status' => 'Aktif'],
                    ];
                @endphp

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Kode</th>
                                <th class="px-5 py-4">Program Studi</th>
                                <th class="px-5 py-4">Kuota</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($programs as $program)
                                <tr>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                                            {{ $program['code'] }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 font-bold text-slate-900">
                                        {{ $program['name'] }}
                                    </td>

                                    <td class="px-5 py-4 font-bold text-slate-700">
                                        {{ $program['quota'] }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                            {{ $program['status'] }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Cost Components --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Komponen Biaya
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Data biaya yang digunakan untuk tagihan pendaftaran dan daftar ulang.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Tambah Komponen Biaya',
                                text: 'Form komponen biaya akan dibuat setelah backend siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Tambah Biaya
                    </button>
                </div>

                @php
                    $fees = [
                        ['name' => 'Biaya Pendaftaran', 'type' => 'Pendaftaran', 'amount' => 'Rp350.000', 'status' => 'Aktif'],
                        ['name' => 'Daftar Ulang', 'type' => 'Daftar Ulang', 'amount' => 'Rp2.500.000', 'status' => 'Aktif'],
                        ['name' => 'Angsuran SPI', 'type' => 'Daftar Ulang', 'amount' => 'Rp5.000.000', 'status' => 'Aktif'],
                        ['name' => 'SPP Semester 1', 'type' => 'Daftar Ulang', 'amount' => 'Rp4.000.000', 'status' => 'Aktif'],
                    ];
                @endphp

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach($fees as $fee)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-black text-slate-900">
                                        {{ $fee['name'] }}
                                    </h3>
                                    <p class="mt-1 text-xs font-bold text-slate-500">
                                        {{ $fee['type'] }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                    {{ $fee['status'] }}
                                </span>
                            </div>

                            <p class="mt-4 text-2xl font-black text-polmind-blue">
                                {{ $fee['amount'] }}
                            </p>

                            <button class="mt-5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                Edit Biaya
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Side Panel --}}
        <aside class="space-y-6">

            {{-- Import Master --}}
            <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-2xl">
                    ⬆️
                </div>

                <h3 class="mt-5 text-lg font-black text-polmind-blue">
                    Import Master Data
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Master wilayah dan master sekolah sebaiknya diimpor dari file referensi agar pencarian alamat
                    dan sekolah lebih cepat saat camaba mengisi biodata.
                </p>

                <div class="mt-5 space-y-3">
                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Import Wilayah',
                                text: 'Import provinsi, kabupaten, kecamatan, dan desa akan dibuat setelah struktur database siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Import Wilayah
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Import Sekolah',
                                text: 'Import master sekolah berbasis NPSN akan dibuat setelah database siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                        Import Sekolah
                    </button>
                </div>
            </div>

            {{-- Required Documents --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Jenis Berkas
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Dokumen yang wajib/opsional diunggah camaba.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Tambah Jenis Berkas',
                                text: 'Form jenis berkas akan dibuat pada tahap backend.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-blue px-3 py-2 text-xs font-bold text-white">
                        +
                    </button>
                </div>

                <div class="mt-6 space-y-3">
                    @foreach([
                        ['name' => 'Pas Foto', 'required' => 'Wajib'],
                        ['name' => 'KTP / Kartu Pelajar', 'required' => 'Wajib'],
                        ['name' => 'Kartu Keluarga', 'required' => 'Wajib'],
                        ['name' => 'Ijazah / SKL', 'required' => 'Wajib'],
                        ['name' => 'Rapor', 'required' => 'Opsional'],
                        ['name' => 'Sertifikat Prestasi', 'required' => 'Opsional'],
                    ] as $document)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-900">
                                {{ $document['name'] }}
                            </p>

                            @if($document['required'] === 'Wajib')
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">
                                    Wajib
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                    Opsional
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Classes --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Kelas
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Pilihan kelas yang tersedia saat pendaftaran.
                </p>

                <div class="mt-6 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-black text-slate-900">Reguler A</p>
                                <p class="mt-1 text-xs text-slate-500">Kelas pagi / reguler</p>
                            </div>

                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                Aktif
                            </span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-black text-slate-900">Reguler B</p>
                                <p class="mt-1 text-xs text-slate-500">Kelas karyawan / malam</p>
                            </div>

                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- WhatsApp Template --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Template WhatsApp
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Template pesan untuk follow up camaba.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach([
                        'Reminder Lengkapi Biodata',
                        'Reminder Upload Berkas',
                        'Reminder Pembayaran',
                        'Pengumuman Diterima',
                        'Reminder Daftar Ulang',
                    ] as $template)
                        <button type="button"
                                onclick="Swal.fire({
                                    title: 'Template WA',
                                    text: 'Template ini nanti bisa diedit melalui form master data.',
                                    icon: 'info',
                                    confirmButtonColor: '#003B82'
                                })"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-polmind-blue">
                            {{ $template }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Notes --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Catatan Master Data
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Tahun PMB aktif hanya boleh satu.</li>
                    <li>• Gelombang aktif menentukan periode pendaftaran.</li>
                    <li>• Biaya aktif akan muncul pada tagihan camaba.</li>
                    <li>• Master sekolah dan wilayah sebaiknya diimport lokal.</li>
                </ul>
            </div>

        </aside>
    </div>

</div>
@endsection