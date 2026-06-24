@extends('layouts.camaba')

@section('title', 'Upload Berkas Pendaftaran')
@section('page_title', 'Upload Berkas')
@section('page_subtitle', 'Unggah dokumen persyaratan pendaftaran mahasiswa baru.')

@section('content')
<div class="space-y-8">

    {{-- Summary --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-xl shadow-blue-900/20 lg:col-span-2">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                        Kelengkapan Berkas
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        3 dari 5 Berkas Terunggah
                    </h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-blue-100">
                        Lengkapi seluruh berkas wajib agar admin PMB dapat melakukan verifikasi
                        dan melanjutkan proses seleksi pendaftaran Anda.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 p-5 text-center">
                    <p class="text-4xl font-black">60%</p>
                    <p class="mt-1 text-xs font-semibold text-blue-100">Progress Upload</p>
                </div>
            </div>

            <div class="mt-8 h-3 overflow-hidden rounded-full bg-white/20">
                <div class="h-full w-[60%] rounded-full bg-polmind-yellow"></div>
            </div>
        </div>

        <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                ⚠️
            </div>
            <h3 class="mt-5 text-lg font-black text-yellow-800">
                Perhatian
            </h3>
            <p class="mt-2 text-sm leading-6 text-yellow-800">
                Pastikan file terlihat jelas, tidak blur, dan ukuran file tidak melebihi ketentuan.
                Format yang disarankan: PDF, JPG, atau PNG.
            </p>
        </div>
    </div>

    {{-- Info Requirements --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Dokumen Persyaratan
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Unggah dokumen sesuai daftar berikut. Berkas bertanda wajib harus dilengkapi.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Belum Upload</span>
                <span class="rounded-full bg-yellow-100 px-3 py-1 text-yellow-700">Menunggu Verifikasi</span>
                <span class="rounded-full bg-green-100 px-3 py-1 text-green-700">Diterima</span>
                <span class="rounded-full bg-red-100 px-3 py-1 text-red-700">Ditolak</span>
            </div>
        </div>
    </div>

    {{-- Document Cards --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @php
            $documents = [
                [
                    'title' => 'Pas Foto',
                    'desc' => 'Pas foto terbaru dengan latar belakang polos.',
                    'required' => true,
                    'status' => 'diterima',
                    'file' => 'pas-foto-ahmad.jpg',
                    'note' => null,
                    'icon' => '🖼️',
                ],
                [
                    'title' => 'KTP / Kartu Pelajar',
                    'desc' => 'KTP, kartu pelajar, atau identitas resmi lainnya.',
                    'required' => true,
                    'status' => 'menunggu',
                    'file' => 'ktp-ahmad.pdf',
                    'note' => null,
                    'icon' => '🪪',
                ],
                [
                    'title' => 'Kartu Keluarga',
                    'desc' => 'Scan atau foto Kartu Keluarga yang masih jelas terbaca.',
                    'required' => true,
                    'status' => 'ditolak',
                    'file' => 'kk-ahmad.jpg',
                    'note' => 'Foto kurang jelas. Mohon upload ulang dengan resolusi lebih baik.',
                    'icon' => '👨‍👩‍👧',
                ],
                [
                    'title' => 'Ijazah / SKL',
                    'desc' => 'Ijazah terakhir atau Surat Keterangan Lulus.',
                    'required' => true,
                    'status' => 'belum',
                    'file' => null,
                    'note' => null,
                    'icon' => '🎓',
                ],
                [
                    'title' => 'Rapor',
                    'desc' => 'Rapor atau transkrip nilai sesuai ketentuan pendaftaran.',
                    'required' => false,
                    'status' => 'belum',
                    'file' => null,
                    'note' => null,
                    'icon' => '📄',
                ],
            ];

            $statusClasses = [
                'belum' => 'bg-slate-100 text-slate-600',
                'menunggu' => 'bg-yellow-100 text-yellow-700',
                'diterima' => 'bg-green-100 text-green-700',
                'ditolak' => 'bg-red-100 text-red-700',
            ];

            $statusLabels = [
                'belum' => 'Belum Upload',
                'menunggu' => 'Menunggu Verifikasi',
                'diterima' => 'Diterima',
                'ditolak' => 'Ditolak',
            ];
        @endphp

        @foreach($documents as $document)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-2xl">
                            {{ $document['icon'] }}
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-black text-polmind-blue">
                                    {{ $document['title'] }}
                                </h3>

                                @if($document['required'])
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-red-600">
                                        Wajib
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">
                                        Opsional
                                    </span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $document['desc'] }}
                            </p>
                        </div>
                    </div>

                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$document['status']] }}">
                        {{ $statusLabels[$document['status']] }}
                    </span>
                </div>

                {{-- File Info --}}
                @if($document['file'])
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                    File Terunggah
                                </p>
                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    {{ $document['file'] }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <a href="#"
                                   class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                    Lihat
                                </a>

                                @if($document['status'] === 'ditolak')
                                    <button type="button"
                                            class="rounded-xl bg-polmind-blue px-4 py-2 text-xs font-bold text-white transition hover:bg-polmind-blue-dark">
                                        Upload Ulang
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Rejection Note --}}
                @if($document['note'])
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-red-700">
                            Catatan Admin
                        </p>
                        <p class="mt-2 text-sm leading-6 text-red-700">
                            {{ $document['note'] }}
                        </p>
                    </div>
                @endif

                {{-- Upload Area --}}
                @if($document['status'] === 'belum')
                    <div class="mt-6">
                        <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-polmind-blue hover:bg-blue-50">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                                ⬆️
                            </div>
                            <p class="mt-4 text-sm font-black text-polmind-blue">
                                Klik untuk upload file
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                PDF, JPG, PNG. Maksimal 2MB.
                            </p>
                            <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                        </label>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Submit Verification --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div>
                <h3 class="text-lg font-black text-polmind-blue">
                    Ajukan Verifikasi Berkas
                </h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Setelah semua berkas wajib diunggah, klik tombol ajukan verifikasi agar admin PMB dapat memeriksa dokumen Anda.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Ajukan Verifikasi?',
                        text: 'Pastikan seluruh berkas yang diunggah sudah benar.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Ajukan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#003B82'
                    })"
                    class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Ajukan Verifikasi
            </button>
        </div>
    </div>

    {{-- Next Step --}}
    <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h3 class="text-lg font-black text-polmind-blue">
                    Langkah Berikutnya
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Setelah berkas diverifikasi, Anda dapat melanjutkan proses pembayaran dan menunggu hasil seleksi.
                </p>
            </div>

            <a href="{{ url('/camaba/pembayaran') }}"
               class="inline-flex items-center justify-center rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                Lihat Tagihan →
            </a>
        </div>
    </div>

</div>
@endsection