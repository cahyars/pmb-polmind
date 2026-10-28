@extends('layouts.admin')

@section('title', 'Verifikasi Berkas')
@section('page_title', 'Verifikasi Berkas')
@section('page_subtitle', 'Periksa dan tentukan tindakan untuk berkas camaba.')

@section('content')
@php
    $statuses = [
        'belum_upload' => ['Belum Upload', 'bg-slate-100 text-slate-600'],
        'menunggu_verifikasi' => ['Menunggu Verifikasi', 'bg-yellow-100 text-yellow-700'],
        'diterima' => ['Diterima', 'bg-green-100 text-green-700'],
        'ditolak' => ['Ditolak', 'bg-red-100 text-red-700'],
    ];

    $formatSize = fn (int $kb) => $kb >= 1024 ? rtrim(rtrim(number_format($kb / 1024, 1, ',', '.'), '0'), ',') . ' MB' : number_format($kb, 0, ',', '.') . ' KB';

    $documentProgress = $requiredCount > 0 ? round(($acceptedRequiredCount / $requiredCount) * 100) : 0;
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <a href="{{ route('admin.documents.index') }}"
                   class="inline-flex items-center rounded-lg bg-white/10 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                    ← Kembali ke Antrian Berkas
                </a>

                <div class="mt-6 flex items-center gap-5">
                    @if($applicant->photoDocument)
                        <button type="button"
                                @click="$store.preview.show(@js(route('admin.documents.file', $applicant->photoDocument)), @js(basename($applicant->photoDocument->file_path)))"
                                class="shrink-0" title="Lihat pas foto">
                            <img src="{{ route('admin.documents.file', $applicant->photoDocument) }}"
                                 alt="Foto {{ $applicant->full_name }}"
                                 class="h-24 w-20 rounded-lg bg-white object-cover ring-4 ring-white/20">
                        </button>
                    @endif

                    <div>
                        <p class="text-sm font-semibold text-blue-100">{{ $applicant->registration_number }}</p>
                        <h1 class="mt-2 text-2xl font-bold">{{ $applicant->full_name }}</h1>
                        <p class="mt-2 text-sm text-blue-100">
                            {{ $applicant->education?->school_name ?? '-' }} · {{ $applicant->phone ?? '-' }}
                        </p>
                        <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}"
                           class="mt-3 inline-flex text-xs font-semibold text-white underline underline-offset-4 hover:text-blue-100">
                            Lihat data lengkap camaba
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Program Studi</p>
                    <p class="mt-2 text-lg font-bold">{{ $applicant->studyProgram?->code ?? '-' }}</p>
                    <p class="text-xs text-blue-100">{{ $applicant->classType?->name ?? '-' }}</p>
                </div>

                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Jalur</p>
                    <p class="mt-2 text-lg font-bold">{{ $applicant->registration_path_label }}</p>
                    <p class="text-xs text-blue-100">{{ $applicant->admissionWave?->name ?? '-' }}</p>
                </div>

                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Menunggu Verifikasi</p>
                    <p class="mt-2 text-lg font-bold">{{ $waitingCount }} Berkas</p>
                    <p class="text-xs text-blue-100">Wajib diterima {{ $acceptedRequiredCount }}/{{ $requiredCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Berkas --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="berkas-title">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 id="berkas-title" class="text-base font-semibold text-slate-900">Berkas Pendaftaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Hanya berkas berstatus menunggu verifikasi yang dapat diterima atau ditolak.
                </p>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="sm:w-56">
                    <div class="flex items-baseline justify-between text-xs font-semibold text-slate-500">
                        <span>Berkas wajib diterima</span>
                        <span class="text-sm font-bold text-polmind-blue">{{ $acceptedRequiredCount }}/{{ $requiredCount }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-green-600" style="width: {{ min($documentProgress, 100) }}%"></div>
                    </div>
                </div>

                @if($waitingCount > 1)
                    <form action="{{ route('admin.documents.accept-all', $applicant->registration_number) }}"
                          method="POST"
                          onsubmit="return confirm('Terima semua {{ $waitingCount }} berkas yang menunggu verifikasi?')">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                                class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 sm:w-auto">
                            Terima Semua ({{ $waitingCount }})
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <ul class="divide-y divide-slate-100">
            @forelse($documentTypes as $documentType)
                @php
                    $document = $documents->get($documentType->id);
                    $hasFile = filled($document?->file_path);
                    $status = $hasFile ? ($document->status ?? 'menunggu_verifikasi') : 'belum_upload';
                    [$statusLabel, $statusClass] = $statuses[$status] ?? $statuses['belum_upload'];
                    $isWaiting = $status === 'menunggu_verifikasi';
                @endphp

                <li x-data="{ rejecting: false }"
                    class="grid gap-4 px-6 py-5 md:grid-cols-[minmax(0,1fr)_10rem_auto] md:items-center {{ $isWaiting ? 'bg-yellow-50/40' : '' }}">
                    {{-- Nama & file --}}
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">
                            {{ $documentType->name }}
                            <span class="ml-1 align-middle text-[11px] font-bold {{ $documentType->is_required ? 'text-red-600' : 'text-slate-400' }}">
                                {{ $documentType->is_required ? 'Wajib' : 'Opsional' }}
                            </span>
                            @unless($documentType->is_active)
                                <span class="ml-1 align-middle text-[11px] font-bold text-slate-400">Nonaktif</span>
                            @endunless
                        </p>

                        @if($hasFile)
                            <p class="mt-1 truncate text-sm text-slate-600">{{ $document->file_name ?? basename($document->file_path) }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ strtoupper($document->file_extension ?? '-') }} · {{ $formatSize((int) $document->file_size_kb) }}
                                · Diunggah {{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}
                            </p>
                        @else
                            <p class="mt-1 text-sm text-slate-400">Camaba belum mengunggah berkas ini.</p>
                        @endif

                        @if($document?->verified_at && ! $isWaiting)
                            <p class="mt-1 text-xs text-slate-500">
                                Diverifikasi {{ $document->verified_at->format('d M Y H:i') }}
                                @if($document->verified_by_name) oleh {{ $document->verified_by_name }} @endif
                            </p>
                        @endif

                        @if($status === 'ditolak' && $document?->admin_note)
                            <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold leading-5 text-red-700">
                                Catatan admin: {{ $document->admin_note }}
                            </p>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    {{-- Aksi --}}
                    <div class="flex flex-wrap gap-2 md:justify-end">
                        @if($hasFile)
                            <a href="{{ route('admin.documents.file', $document) }}"
                               @click.prevent="$store.preview.show($el.href, @js($document->file_name ?? basename($document->file_path)))"
                               class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue transition hover:bg-blue-100">
                                Lihat File
                            </a>
                        @endif

                        @if($isWaiting)
                            <form action="{{ route('admin.documents.accept', $document) }}"
                                  method="POST"
                                  onsubmit="return confirm(@js('Terima berkas ' . $documentType->name . '?'))">
                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-700">
                                    Terima
                                </button>
                            </form>

                            <button type="button"
                                    @click="rejecting = ! rejecting"
                                    class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                Tolak
                            </button>
                        @endif
                    </div>

                    @if($isWaiting)
                        <form x-show="rejecting"
                              x-cloak
                              action="{{ route('admin.documents.reject', $document) }}"
                              method="POST"
                              class="rounded-lg border border-red-200 bg-red-50 p-4 md:col-span-3">
                            @csrf
                            @method('PATCH')

                            <label class="text-xs font-semibold text-red-700" for="admin-note-{{ $document->id }}">
                                Alasan penolakan {{ $documentType->name }} (akan dibaca camaba)
                            </label>
                            <textarea id="admin-note-{{ $document->id }}"
                                      name="admin_note"
                                      rows="3"
                                      required
                                      maxlength="1000"
                                      placeholder="Contoh: Foto buram, mohon unggah ulang dengan hasil scan yang jelas."
                                      class="mt-2 w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100"></textarea>

                            <div class="mt-2 flex justify-end gap-2">
                                <button type="button"
                                        @click="rejecting = false"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                    Batal
                                </button>

                                <button type="submit"
                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white">
                                    Simpan Tolak
                                </button>
                            </div>
                        </form>
                    @endif
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                    Belum ada jenis berkas yang diatur.
                </li>
            @endforelse
        </ul>
    </section>

</div>
@endsection
