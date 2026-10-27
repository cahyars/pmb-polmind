@extends('layouts.camaba')

@section('title', 'Upload Berkas')
@section('page_title', 'Upload Berkas')
@section('page_subtitle', 'Upload dokumen persyaratan pendaftaran mahasiswa baru.')

@section('content')
@php
    $statuses = [
        'belum_upload' => ['Belum', 'bg-slate-100 text-slate-600'],
        'menunggu_verifikasi' => ['Menunggu', 'bg-yellow-100 text-yellow-800'],
        'diterima' => ['Diterima', 'bg-green-600 text-white'],
        'ditolak' => ['Ditolak', 'bg-red-600 text-white'],
    ];

    $formatSize = fn (int $kb) => $kb >= 1024 ? rtrim(rtrim(number_format($kb / 1024, 1, ',', '.'), '0'), ',') . ' MB' : number_format($kb, 0, ',', '.') . ' KB';

    $toast = session('success')
        ? ['type' => 'success', 'message' => session('success')]
        : ($errors->any() ? ['type' => 'error', 'message' => $errors->first()] : null);
@endphp

{{-- Notifikasi hasil unggah (kanan atas) --}}
@if($toast)
    <div x-data="{ open: true }"
         x-init="setTimeout(() => open = false, 8000)"
         x-show="open"
         x-transition.opacity.duration.300ms
         role="status"
         aria-live="polite"
         class="fixed right-4 top-20 z-50 w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-xl text-white shadow-2xl sm:right-6 {{ $toast['type'] === 'success' ? 'bg-green-600 shadow-green-900/30' : 'bg-red-600 shadow-red-900/30' }}">
        <div class="flex items-start gap-4 px-5 py-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/20 text-lg font-black" aria-hidden="true">
                {{ $toast['type'] === 'success' ? '✓' : '!' }}
            </span>
            <p class="pt-1.5 text-sm font-bold leading-6">{{ $toast['message'] }}</p>
        </div>
        <div class="flex justify-end border-t border-white/20 px-5 py-2.5">
            <button type="button" @click="open = false" class="text-xs font-black uppercase tracking-wider hover:underline">Tutup</button>
        </div>
    </div>
@endif

<div class="space-y-6">

    {{-- Berkas Pendukung --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="berkas-title">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h2 id="berkas-title" class="text-xl font-black text-slate-900">Berkas Pendukung</h2>
                <p class="mt-1 text-sm text-slate-500">Dokumen yang harus dilengkapi.</p>
            </div>

            <div class="sm:w-64">
                <div class="flex items-baseline justify-between text-xs font-bold text-slate-500">
                    <span>Berkas wajib diterima</span>
                    <span class="text-sm font-black text-polmind-blue">{{ $acceptedRequiredCount }}/{{ $requiredCount }}</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-green-600" style="width: {{ min($documentProgress, 100) }}%"></div>
                </div>
            </div>
        </div>

        <ul class="divide-y divide-slate-100">
            @forelse($documentTypes as $documentType)
                @php
                    $document = $documents->get($documentType->id);
                    $hasFile = filled($document?->file_path);
                    $status = $hasFile ? ($document->status ?? 'menunggu_verifikasi') : 'belum_upload';
                    [$statusLabel, $statusClass] = $statuses[$status] ?? $statuses['belum_upload'];
                    $extensions = array_map('strtolower', $documentType->allowed_extensions ?? []);
                    $locked = $status === 'diterima';
                @endphp

                <li class="grid gap-4 px-5 py-5 sm:px-6 md:grid-cols-[minmax(0,1fr)_6.5rem_auto] md:items-center"
                    x-data="documentUpload(@js($extensions), {{ (int) $documentType->max_size_kb }}, @js($documentType->name))">
                    {{-- Nama & keterangan --}}
                    <div class="min-w-0">
                        <p class="text-base font-black text-slate-900">
                            {{ $documentType->name }}
                            <span class="ml-1 align-middle text-[11px] font-bold {{ $documentType->is_required ? 'text-red-600' : 'text-slate-400' }}">
                                {{ $documentType->is_required ? 'Wajib' : 'Opsional' }}
                            </span>
                        </p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            {{ $documentType->description ?: ($documentType->code === 'PAS_FOTO'
                                ? 'Foto formal terbaru, latar polos, wajah terlihat jelas. Otomatis menjadi foto profil Anda.'
                                : 'Scan berkas dan unggah di sini.') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Format {{ $extensions ? strtoupper(implode(', ', $extensions)) : 'bebas' }} · Maks. {{ $formatSize((int) $documentType->max_size_kb) }}
                            @if($hasFile)
                                · <span class="text-slate-500">{{ $document->file_name ?? basename($document->file_path) }}</span>
                            @endif
                        </p>

                        @if($status === 'ditolak' && $document?->admin_note)
                            <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold leading-5 text-red-700">
                                Catatan admin: {{ $document->admin_note }}
                            </p>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-2 md:block">
                        <p class="text-xs text-slate-500">Status</p>
                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-bold md:mt-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    {{-- Aksi --}}
                    <div class="flex flex-wrap gap-2 md:justify-end">
                        <form x-ref="form" action="{{ route('camaba.documents.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="document_type_id" value="{{ $documentType->id }}">
                            <input x-ref="file"
                                   type="file"
                                   name="file"
                                   class="sr-only"
                                   tabindex="-1"
                                   @if($extensions) accept="{{ collect($extensions)->map(fn ($ext) => '.' . $ext)->implode(',') }}" @endif
                                   @change="submit($event)">

                            <button type="button"
                                    @click="$refs.file.click()"
                                    :disabled="uploading || @js($locked)"
                                    @disabled($locked)
                                    title="{{ $locked ? 'Berkas sudah diterima admin dan tidak dapat diubah' : '' }}"
                                    class="inline-flex min-w-36 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-sm transition disabled:cursor-not-allowed
                                    {{ $locked ? 'bg-slate-300' : 'bg-emerald-500 hover:bg-emerald-600 disabled:opacity-70' }}">
                                <svg x-show="! uploading" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $hasFile ? 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z' : 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5' }}"/>
                                </svg>
                                <svg x-show="uploading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-30"/>
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                                <span x-text="uploading ? 'Mengunggah...' : @js($hasFile ? 'Ubah' : 'Unggah')">{{ $hasFile ? 'Ubah' : 'Unggah' }}</span>
                            </button>
                        </form>

                        @if($hasFile)
                            <a href="{{ route('camaba.documents.file', $document) }}"
                               @click.prevent="$store.preview.show($el.href, @js($document->file_name ?? basename($document->file_path)))"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-blue-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                Lihat
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-slate-100 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-slate-400" title="Belum ada berkas">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                Lihat
                            </span>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center">
                    <p class="text-sm font-bold text-slate-600">Belum ada jenis berkas yang perlu diunggah.</p>
                    <p class="mt-1 text-xs text-slate-500">Silakan hubungi admin PMB.</p>
                </li>
            @endforelse
        </ul>
    </section>

    {{-- Ketentuan Upload --}}
    <section class="rounded-3xl border border-blue-100 bg-blue-50/60 p-5 sm:p-6" aria-labelledby="ketentuan-title">
        <h2 id="ketentuan-title" class="text-lg font-black text-polmind-blue">Ketentuan Upload</h2>

        <ol class="mt-4 grid gap-x-8 gap-y-3 text-sm leading-6 text-slate-700 md:grid-cols-2">
            @foreach([
                'Pastikan file jelas, tidak buram, dan seluruh bagian dokumen terlihat.',
                'Format dan ukuran file mengikuti ketentuan yang tertulis di bawah nama setiap berkas.',
                'Berkas bertanda Wajib harus diunggah agar dapat mengikuti proses seleksi.',
                'Setelah diunggah, berkas akan diverifikasi oleh admin PMB.',
                'Jika berkas ditolak, baca catatan admin lalu klik Ubah untuk mengunggah ulang.',
                'Berkas yang sudah diterima tidak dapat diubah. Hubungi admin PMB jika perlu perubahan.',
            ] as $rule)
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">{{ $loop->iteration }}</span>
                    <span>{{ $rule }}</span>
                </li>
            @endforeach
        </ol>
    </section>
</div>

<script>
    // Unggah langsung setelah file dipilih; cek format & ukuran dulu di browser.
    window.documentUpload = (extensions, maxSizeKb, name) => ({
        uploading: false,

        submit(event) {
            const file = event.target.files[0];

            if (! file) {
                return;
            }

            const extension = file.name.split('.').pop().toLowerCase();
            const problem = extensions.length && ! extensions.includes(extension)
                ? `Format file ${name} harus ${extensions.join(', ').toUpperCase()}.`
                : (file.size > maxSizeKb * 1024 ? `Ukuran file ${name} maksimal ${maxSizeKb.toLocaleString('id-ID')} KB.` : null);

            if (problem) {
                event.target.value = '';
                window.Swal.fire({ icon: 'error', title: 'File tidak sesuai', text: problem, confirmButtonColor: '#003B82' });

                return;
            }

            this.uploading = true;
            this.$refs.form.submit();
        },
    });
</script>
@endsection
