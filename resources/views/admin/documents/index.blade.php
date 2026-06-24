@extends('layouts.admin')

@section('title', 'Verifikasi Berkas')
@section('page_title', 'Verifikasi Berkas')
@section('page_subtitle', 'Periksa dan validasi dokumen persyaratan calon mahasiswa.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Menunggu Verifikasi', 'value' => '34', 'desc' => 'Berkas perlu dicek', 'class' => 'text-yellow-700 bg-yellow-100'],
            ['label' => 'Diterima', 'value' => '118', 'desc' => 'Berkas sudah valid', 'class' => 'text-green-700 bg-green-100'],
            ['label' => 'Ditolak/Revisi', 'value' => '12', 'desc' => 'Perlu upload ulang', 'class' => 'text-red-700 bg-red-100'],
            ['label' => 'Belum Upload', 'value' => '47', 'desc' => 'Belum dilengkapi', 'class' => 'text-slate-700 bg-slate-100'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
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
                    Filter Berkas
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cari dokumen berdasarkan nama camaba, nomor pendaftaran, jenis dokumen, atau status verifikasi.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Refresh Data',
                        text: 'Data berkas akan diambil ulang setelah backend aktif.',
                        icon: 'info',
                        confirmButtonColor: '#003B82'
                    })"
                    class="rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                Refresh
            </button>
        </div>

        <form action="#" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       placeholder="Nama camaba / no. pendaftaran"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jenis Berkas</label>
                <select name="document_type"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jenis</option>
                    <option>Pas Foto</option>
                    <option>KTP / Kartu Pelajar</option>
                    <option>Kartu Keluarga</option>
                    <option>Ijazah / SKL</option>
                    <option>Rapor</option>
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
                    <option>Diterima</option>
                    <option>Ditolak</option>
                    <option>Belum Upload</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/documents') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Verification Queue --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">
                        Antrian Verifikasi
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Daftar berkas yang masuk dan menunggu pengecekan admin.
                    </p>
                </div>

                <span class="rounded-full bg-yellow-100 px-4 py-2 text-xs font-black text-yellow-700">
                    34 Menunggu
                </span>
            </div>

            @php
                $documents = [
                    [
                        'registration' => 'PMB20260001',
                        'name' => 'Ahmad Fauzi',
                        'program' => 'TRPL',
                        'document' => 'Kartu Keluarga',
                        'file' => 'kk-ahmad.jpg',
                        'uploaded_at' => '22 Juni 2026 09:12',
                        'status' => 'Menunggu Verifikasi',
                        'status_key' => 'waiting',
                    ],
                    [
                        'registration' => 'PMB20260002',
                        'name' => 'Siti Aminah',
                        'program' => 'Bisnis Digital',
                        'document' => 'Pas Foto',
                        'file' => 'pas-foto-siti.png',
                        'uploaded_at' => '22 Juni 2026 10:35',
                        'status' => 'Menunggu Verifikasi',
                        'status_key' => 'waiting',
                    ],
                    [
                        'registration' => 'PMB20260003',
                        'name' => 'Budi Santoso',
                        'program' => 'TRM',
                        'document' => 'Ijazah / SKL',
                        'file' => 'skl-budi.pdf',
                        'uploaded_at' => '22 Juni 2026 11:20',
                        'status' => 'Diterima',
                        'status_key' => 'accepted',
                    ],
                    [
                        'registration' => 'PMB20260004',
                        'name' => 'Dewi Lestari',
                        'program' => 'TRPL',
                        'document' => 'KTP / Kartu Pelajar',
                        'file' => 'ktp-dewi.jpg',
                        'uploaded_at' => '22 Juni 2026 13:02',
                        'status' => 'Ditolak',
                        'status_key' => 'rejected',
                    ],
                    [
                        'registration' => 'PMB20260005',
                        'name' => 'Rizky Pratama',
                        'program' => 'Bisnis Digital',
                        'document' => 'Rapor',
                        'file' => 'rapor-rizky.pdf',
                        'uploaded_at' => '22 Juni 2026 14:15',
                        'status' => 'Menunggu Verifikasi',
                        'status_key' => 'waiting',
                    ],
                ];

                $badgeClasses = [
                    'waiting' => 'bg-yellow-100 text-yellow-700',
                    'accepted' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Camaba</th>
                            <th class="px-6 py-4">Berkas</th>
                            <th class="px-6 py-4">File</th>
                            <th class="px-6 py-4">Upload</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($documents as $document)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                            {{ collect(explode(' ', $document['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $document['name'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $document['registration'] }} · {{ $document['program'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-bold text-slate-900">{{ $document['document'] }}</p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-semibold text-polmind-blue">{{ $document['file'] }}</p>
                                </td>

                                <td class="px-6 py-5 text-slate-600">
                                    {{ $document['uploaded_at'] }}
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClasses[$document['status_key']] }}">
                                        {{ $document['status'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Preview Berkas',
                                                    text: 'Preview file akan dihubungkan ke storage setelah backend siap.',
                                                    icon: 'info',
                                                    confirmButtonColor: '#003B82'
                                                })"
                                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            Preview
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Terima Berkas?',
                                                    text: 'Berkas akan ditandai valid.',
                                                    icon: 'question',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Terima',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#16A34A'
                                                })"
                                                class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                                            Terima
                                        </button>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tolak Berkas?',
                                                    input: 'textarea',
                                                    inputLabel: 'Catatan Revisi',
                                                    inputPlaceholder: 'Contoh: Foto kurang jelas, mohon upload ulang.',
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
                    Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">34</span> berkas menunggu
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

        {{-- Preview / Guidance Panel --}}
        <aside class="space-y-6">

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Panel Preview
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Pada tahap backend nanti, file yang dipilih dari tabel akan tampil di panel ini.
                </p>

                <div class="mt-6 flex aspect-[3/4] items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                    <div>
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                            📄
                        </div>
                        <p class="mt-4 text-sm font-black text-polmind-blue">
                            Belum ada file dipilih
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Klik tombol preview pada salah satu berkas untuk melihat detail dokumen.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    ⚠️
                </div>
                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Panduan Verifikasi
                </h3>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Pastikan nama dokumen sesuai dengan data camaba.</li>
                    <li>• Pastikan file tidak blur dan terbaca jelas.</li>
                    <li>• Tolak berkas jika tidak sesuai atau perlu upload ulang.</li>
                    <li>• Berikan catatan revisi yang jelas saat menolak.</li>
                </ul>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Aksi Cepat
                </h2>

                <div class="mt-5 space-y-3">
                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Bulk Approve',
                                text: 'Fitur validasi massal akan dibuat setelah database siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Validasi Massal
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Data',
                                text: 'Export data verifikasi akan dihubungkan pada tahap laporan.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Data Berkas
                    </button>
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection