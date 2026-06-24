@extends('layouts.admin')

@section('title', 'Seleksi Camaba')
@section('page_title', 'Seleksi Camaba')
@section('page_subtitle', 'Kelola hasil seleksi calon mahasiswa baru Politeknik Mitra Industri.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Siap Seleksi', 'value' => '42', 'desc' => 'Berkas & pembayaran siap dicek', 'class' => 'bg-blue-100 text-polmind-blue'],
            ['label' => 'Diterima', 'value' => '28', 'desc' => 'Camaba lolos seleksi', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Cadangan', 'value' => '6', 'desc' => 'Menunggu keputusan lanjutan', 'class' => 'bg-yellow-100 text-yellow-700'],
            ['label' => 'Ditolak', 'value' => '8', 'desc' => 'Tidak memenuhi kriteria', 'class' => 'bg-red-100 text-red-700'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        Seleksi
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
                    Filter Seleksi
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cari camaba berdasarkan nama, nomor pendaftaran, program studi, gelombang, atau status seleksi.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Export Hasil Seleksi',
                        text: 'Fitur export hasil seleksi akan dihubungkan setelah database siap.',
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
                <label class="text-sm font-bold text-slate-700">Status Seleksi</label>
                <select name="selection_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    <option>Belum Diseleksi</option>
                    <option>Diterima</option>
                    <option>Cadangan</option>
                    <option>Ditolak</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/selections') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Selection Table --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">
                        Daftar Camaba Siap Seleksi
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data camaba yang sudah masuk tahap seleksi berdasarkan kelengkapan berkas dan pembayaran.
                    </p>
                </div>

                <span class="rounded-full bg-blue-100 px-4 py-2 text-xs font-black text-polmind-blue">
                    42 Siap Seleksi
                </span>
            </div>

            @php
                $applicants = [
                    [
                        'registration' => 'PMB20260001',
                        'name' => 'Ahmad Fauzi',
                        'school' => 'SMKN 1 Subang',
                        'program' => 'TRPL',
                        'wave' => 'Gel. 2',
                        'document' => 'Valid',
                        'payment' => 'Valid',
                        'score' => '85',
                        'selection' => 'Belum Diseleksi',
                        'selection_key' => 'waiting',
                    ],
                    [
                        'registration' => 'PMB20260002',
                        'name' => 'Siti Aminah',
                        'school' => 'SMAN 1 Cikarang Barat',
                        'program' => 'Bisnis Digital',
                        'wave' => 'Gel. 2',
                        'document' => 'Valid',
                        'payment' => 'Valid',
                        'score' => '88',
                        'selection' => 'Diterima',
                        'selection_key' => 'accepted',
                    ],
                    [
                        'registration' => 'PMB20260003',
                        'name' => 'Budi Santoso',
                        'school' => 'SMK Mitra Industri MM2100',
                        'program' => 'TRM',
                        'wave' => 'Gel. 2',
                        'document' => 'Valid',
                        'payment' => 'Valid',
                        'score' => '78',
                        'selection' => 'Cadangan',
                        'selection_key' => 'reserve',
                    ],
                    [
                        'registration' => 'PMB20260004',
                        'name' => 'Dewi Lestari',
                        'school' => 'SMKN 2 Bekasi',
                        'program' => 'TRPL',
                        'wave' => 'Gel. 1',
                        'document' => 'Valid',
                        'payment' => 'Valid',
                        'score' => '91',
                        'selection' => 'Diterima',
                        'selection_key' => 'accepted',
                    ],
                    [
                        'registration' => 'PMB20260005',
                        'name' => 'Rizky Pratama',
                        'school' => 'SMAN 1 Tambun Selatan',
                        'program' => 'Bisnis Digital',
                        'wave' => 'Gel. 1',
                        'document' => 'Valid',
                        'payment' => 'Valid',
                        'score' => '65',
                        'selection' => 'Ditolak',
                        'selection_key' => 'rejected',
                    ],
                ];

                $selectionBadgeClasses = [
                    'waiting' => 'bg-slate-100 text-slate-600',
                    'accepted' => 'bg-green-100 text-green-700',
                    'reserve' => 'bg-yellow-100 text-yellow-700',
                    'rejected' => 'bg-red-100 text-red-700',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Camaba</th>
                            <th class="px-6 py-4">Sekolah</th>
                            <th class="px-6 py-4">Prodi</th>
                            <th class="px-6 py-4">Syarat</th>
                            <th class="px-6 py-4">Skor</th>
                            <th class="px-6 py-4">Status Seleksi</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($applicants as $applicant)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                            {{ collect(explode(' ', $applicant['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $applicant['name'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $applicant['registration'] }} · {{ $applicant['wave'] }}</p>
                                        </div>
                                    </div>
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
                                    <div class="space-y-1">
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                            Berkas {{ $applicant['document'] }}
                                        </span>
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                            Bayar {{ $applicant['payment'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="text-lg font-black text-polmind-blue">{{ $applicant['score'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $selectionBadgeClasses[$applicant['selection_key']] }}">
                                        {{ $applicant['selection'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ url('/admin/applicants/' . $applicant['registration']) }}"
                                           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            Detail
                                        </a>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tetapkan Diterima?',
                                                    text: 'Camaba akan dinyatakan diterima dan dapat melanjutkan daftar ulang.',
                                                    icon: 'question',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Diterima',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#16A34A'
                                                })"
                                                class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                                            Terima
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tetapkan Cadangan?',
                                                    text: 'Camaba akan ditandai sebagai cadangan.',
                                                    icon: 'question',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Cadangan',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#F59E0B'
                                                })"
                                                class="rounded-xl bg-yellow-500 px-3 py-2 text-xs font-bold text-white hover:bg-yellow-600">
                                            Cadangan
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tolak Camaba?',
                                                    input: 'textarea',
                                                    inputLabel: 'Catatan Penolakan',
                                                    inputPlaceholder: 'Tuliskan alasan atau catatan seleksi.',
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
                    Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">42</span> camaba siap seleksi
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

            {{-- Selection Guide --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Panduan Seleksi
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Pastikan biodata camaba sudah lengkap.</li>
                    <li>• Pastikan berkas wajib sudah valid.</li>
                    <li>• Pastikan pembayaran pendaftaran sudah valid.</li>
                    <li>• Tetapkan diterima hanya jika camaba memenuhi syarat.</li>
                    <li>• Gunakan status cadangan jika kuota prodi belum final.</li>
                </ul>
            </div>

            {{-- Quota Summary --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Kuota Program Studi
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Ringkasan kuota dan jumlah diterima sementara.
                </p>

                <div class="mt-6 space-y-4">
                    @foreach([
                        ['prodi' => 'TRPL', 'accepted' => 12, 'quota' => 40],
                        ['prodi' => 'Bisnis Digital', 'accepted' => 8, 'quota' => 40],
                        ['prodi' => 'TRM', 'accepted' => 8, 'quota' => 40],
                    ] as $quota)
                        @php
                            $percentage = round(($quota['accepted'] / $quota['quota']) * 100);
                        @endphp

                        <div>
                            <div class="flex justify-between gap-3">
                                <p class="text-sm font-bold text-slate-900">{{ $quota['prodi'] }}</p>
                                <p class="text-sm font-black text-polmind-blue">
                                    {{ $quota['accepted'] }}/{{ $quota['quota'] }}
                                </p>
                            </div>

                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $percentage }}%"></div>
                            </div>
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
                                title: 'Publikasi Hasil Seleksi',
                                text: 'Fitur publikasi massal hasil seleksi akan dihubungkan setelah database siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Publikasikan Hasil
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Seleksi',
                                text: 'Export hasil seleksi akan masuk ke modul laporan.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Hasil Seleksi
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Generate Tagihan Daftar Ulang',
                                text: 'Tagihan daftar ulang akan otomatis dibuat untuk camaba yang diterima setelah backend siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Generate Daftar Ulang
                    </button>
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection