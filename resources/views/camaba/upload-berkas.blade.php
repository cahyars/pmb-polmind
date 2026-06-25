@extends('layouts.camaba')

@section('title', 'Upload Berkas')
@section('page_title', 'Upload Berkas')
@section('page_subtitle', 'Upload dokumen persyaratan pendaftaran mahasiswa baru.')

@section('content')
@php
    $statusLabels = [
        'belum_upload' => 'Belum Upload',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
    ];

    $statusClasses = [
        'belum_upload' => 'bg-slate-100 text-slate-600',
        'menunggu_verifikasi' => 'bg-yellow-100 text-yellow-700',
        'diterima' => 'bg-green-100 text-green-700',
        'ditolak' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Nomor Pendaftaran</p>
                <h1 class="mt-2 text-3xl font-black">{{ $applicant->registration_number }}</h1>
                <p class="mt-3 text-sm leading-6 text-blue-100">
                    Upload seluruh dokumen yang diwajibkan. Berkas akan diverifikasi oleh admin PMB.
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-80">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-blue-100">Progress Berkas</p>
                    <p class="text-2xl font-black text-polmind-yellow">{{ $documentProgress }}%</p>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ min($documentProgress, 100) }}%"></div>
                </div>

                <p class="mt-3 text-xs text-blue-100">
                    {{ $acceptedRequiredCount }} dari {{ $requiredCount }} berkas wajib diterima
                </p>
            </div>
        </div>
    </div>

    {{-- Info --}}
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
        <h2 class="text-lg font-black text-polmind-blue">Ketentuan Upload</h2>
        <div class="mt-3 grid gap-3 text-sm leading-6 text-slate-700 md:grid-cols-3">
            <p>Pastikan file jelas dan terbaca.</p>
            <p>Format mengikuti ketentuan masing-masing berkas.</p>
            <p>Jika ditolak admin, upload ulang file yang sesuai.</p>
        </div>
    </div>

    {{-- Document List --}}
    <div class="grid gap-6">
        @forelse($documentTypes as $documentType)
            @php
                $document = $documents->get($documentType->id);
                $status = $document?->status ?? 'belum_upload';
                $extensions = implode(', ', $documentType->allowed_extensions ?? []);
                $fileUrl = $document?->file_path ? asset('storage/' . $document->file_path) : null;
            @endphp

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-black text-polmind-blue">
                                {{ $documentType->name }}
                            </h2>

                            @if($documentType->is_required)
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">
                                    Wajib
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                    Opsional
                                </span>
                            @endif

                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $statusLabels[$status] ?? $status }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-3">
                            <p>
                                <span class="font-bold text-slate-700">Kode:</span>
                                {{ $documentType->code }}
                            </p>
                            <p>
                                <span class="font-bold text-slate-700">Format:</span>
                                {{ $extensions ?: '-' }}
                            </p>
                            <p>
                                <span class="font-bold text-slate-700">Maks:</span>
                                {{ number_format($documentType->max_size_kb) }} KB
                            </p>
                        </div>

                        @if($document?->file_path)
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-black text-slate-800">File Saat Ini</p>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $document->file_name ?? basename($document->file_path) }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-3">
                                    @if($fileUrl)
                                        <a href="{{ $fileUrl }}"
                                           target="_blank"
                                           class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-polmind-blue transition hover:bg-blue-100">
                                            Lihat File
                                        </a>
                                    @endif

                                    @if($status !== 'diterima')
                                        <form action="{{ route('camaba.documents.destroy', $document) }}"
                                              method="POST"
                                              onsubmit="return confirm('Hapus berkas ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs font-bold text-red-700 transition hover:bg-red-100">
                                                Hapus File
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($document?->admin_note)
                            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                                <p class="text-sm font-black text-red-700">Catatan Admin</p>
                                <p class="mt-1 text-sm leading-6 text-red-700">
                                    {{ $document->admin_note }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="w-full lg:w-96">
                        @if($status === 'diterima')
                            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-sm font-bold text-green-700">
                                Berkas sudah diterima admin. Upload ulang tidak diperlukan.
                            </div>
                        @else
                            <form action="{{ route('camaba.documents.store') }}"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                @csrf

                                <input type="hidden" name="document_type_id" value="{{ $documentType->id }}">

                                <label class="text-sm font-bold text-slate-700">
                                    Upload File
                                </label>

                                <input type="file"
                                       name="file"
                                       required
                                       class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition file:mr-4 file:rounded-lg file:border-0 file:bg-polmind-blue file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-polmind-blue-dark focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                <p class="mt-3 text-xs leading-5 text-slate-500">
                                    Format: {{ $extensions ?: 'sesuai ketentuan' }}.
                                    Maksimal {{ number_format($documentType->max_size_kb) }} KB.
                                </p>

                                <button type="submit"
                                        class="mt-4 w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                                    {{ $document?->file_path ? 'Upload Ulang' : 'Upload Berkas' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                <p class="text-sm font-bold text-slate-600">
                    Belum ada master jenis berkas aktif.
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Silakan hubungi admin PMB.
                </p>
            </div>
        @endforelse
    </div>

    {{-- Bottom Action --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <p class="text-sm font-semibold text-slate-600">
                Setelah upload, tunggu verifikasi dari admin PMB.
            </p>

            <a href="{{ route('camaba.dashboard') }}"
               class="rounded-xl bg-polmind-blue px-5 py-3 text-center text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

</div>
@endsection