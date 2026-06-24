@extends('layouts.admin')

@section('title', 'Data Camaba')
@section('page_title', 'Data Camaba')
@section('page_subtitle', 'Kelola data calon mahasiswa baru Politeknik Mitra Industri.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Camaba', 'value' => '128', 'desc' => 'Semua pendaftar', 'color' => 'blue'],
            ['label' => 'Biodata Lengkap', 'value' => '86', 'desc' => 'Siap verifikasi', 'color' => 'green'],
            ['label' => 'Belum Lengkap', 'value' => '24', 'desc' => 'Perlu follow up', 'color' => 'yellow'],
            ['label' => 'Daftar Ulang', 'value' => '18', 'desc' => 'Sudah validasi awal', 'color' => 'purple'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter Section --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Filter Data Camaba
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Gunakan pencarian dan filter untuk menemukan data pendaftar berdasarkan kebutuhan PMB.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Export Excel',
                        text: 'Fitur export akan dihubungkan setelah database siap.',
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
                       placeholder="Nama, NIK, NISN, atau No. Pendaftaran"
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
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    <option>Registrasi Awal</option>
                    <option>Biodata Lengkap</option>
                    <option>Berkas Menunggu</option>
                    <option>Pembayaran Valid</option>
                    <option>Diterima</option>
                    <option>Daftar Ulang Valid</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/applicants') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Daftar Calon Mahasiswa
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data sementara untuk tampilan UI. Nanti akan diambil dari database PMB.
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs font-bold">
                <span class="rounded-full bg-blue-100 px-3 py-1 text-polmind-blue">128 Data</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Update hari ini</span>
            </div>
        </div>

        @php
            $applicants = [
                [
                    'registration' => 'PMB20260001',
                    'name' => 'Ahmad Fauzi',
                    'nik' => '3216xxxxxxxx0001',
                    'nisn' => '006xxxxxxx',
                    'school' => 'SMKN 1 Subang',
                    'program' => 'TRPL',
                    'wave' => 'Gel. 2',
                    'status' => 'Biodata Lengkap',
                    'status_key' => 'blue',
                    'date' => '20 Juni 2026',
                ],
                [
                    'registration' => 'PMB20260002',
                    'name' => 'Siti Aminah',
                    'nik' => '3216xxxxxxxx0002',
                    'nisn' => '006xxxxxxx',
                    'school' => 'SMAN 1 Cikarang Barat',
                    'program' => 'Bisnis Digital',
                    'wave' => 'Gel. 2',
                    'status' => 'Berkas Menunggu',
                    'status_key' => 'yellow',
                    'date' => '20 Juni 2026',
                ],
                [
                    'registration' => 'PMB20260003',
                    'name' => 'Budi Santoso',
                    'nik' => '3216xxxxxxxx0003',
                    'nisn' => '006xxxxxxx',
                    'school' => 'SMK Mitra Industri MM2100',
                    'program' => 'TRM',
                    'wave' => 'Gel. 2',
                    'status' => 'Pembayaran Valid',
                    'status_key' => 'green',
                    'date' => '21 Juni 2026',
                ],
                [
                    'registration' => 'PMB20260004',
                    'name' => 'Dewi Lestari',
                    'nik' => '3216xxxxxxxx0004',
                    'nisn' => '006xxxxxxx',
                    'school' => 'SMKN 2 Bekasi',
                    'program' => 'TRPL',
                    'wave' => 'Gel. 1',
                    'status' => 'Diterima',
                    'status_key' => 'purple',
                    'date' => '21 Juni 2026',
                ],
                [
                    'registration' => 'PMB20260005',
                    'name' => 'Rizky Pratama',
                    'nik' => '3216xxxxxxxx0005',
                    'nisn' => '006xxxxxxx',
                    'school' => 'SMAN 1 Tambun Selatan',
                    'program' => 'Bisnis Digital',
                    'wave' => 'Gel. 1',
                    'status' => 'Daftar Ulang Valid',
                    'status_key' => 'emerald',
                    'date' => '22 Juni 2026',
                ],
            ];

            $badgeClasses = [
                'blue' => 'bg-blue-100 text-polmind-blue',
                'yellow' => 'bg-yellow-100 text-yellow-700',
                'green' => 'bg-green-100 text-green-700',
                'purple' => 'bg-purple-100 text-purple-700',
                'emerald' => 'bg-emerald-100 text-emerald-700',
            ];
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">No. Pendaftaran</th>
                        <th class="px-6 py-4">Nama Camaba</th>
                        <th class="px-6 py-4">NIK / NISN</th>
                        <th class="px-6 py-4">Asal Sekolah</th>
                        <th class="px-6 py-4">Prodi</th>
                        <th class="px-6 py-4">Gelombang</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($applicants as $applicant)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $applicant['registration'] }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                        {{ collect(explode(' ', $applicant['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $applicant['name'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Camaba PMB 2026</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-700">{{ $applicant['nik'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">NISN: {{ $applicant['nisn'] }}</p>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-700">{{ $applicant['school'] }}</p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $applicant['program'] }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <span class="font-semibold text-slate-700">{{ $applicant['wave'] }}</span>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClasses[$applicant['status_key']] }}">
                                    {{ $applicant['status'] }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-slate-600">
                                {{ $applicant['date'] }}
                            </td>

                            <td class="px-6 py-5 text-right">
                                <a href="{{ url('/admin/applicants/' . $applicant['registration']) }}"
                                   class="rounded-xl bg-polmind-blue px-4 py-2 text-xs font-bold text-white transition hover:bg-polmind-blue-dark">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination Dummy --}}
        <div class="flex flex-col justify-between gap-4 border-t border-slate-200 p-6 md:flex-row md:items-center">
            <p class="text-sm text-slate-500">
                Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">128</span> data
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
                    3
                </button>
                <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Berikutnya
                </button>
            </div>
        </div>
    </div>

</div>
@endsection