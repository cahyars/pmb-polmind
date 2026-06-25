@extends('layouts.camaba')

@section('title', 'Pengumuman Seleksi')
@section('page_title', 'Pengumuman Seleksi')
@section('page_subtitle', 'Informasi resmi hasil seleksi dan daftar ulang.')

@section('content')
@php
    $status = $applicant->selection_status ?? 'belum_diseleksi';

    $title = match ($status) {
        'diterima' => 'Selamat, Anda Dinyatakan Diterima',
        'cadangan' => 'Anda Dinyatakan Sebagai Cadangan',
        'ditolak' => 'Anda Belum Dinyatakan Lolos Seleksi',
        default => 'Pengumuman Belum Tersedia',
    };

    $message = match ($status) {
        'diterima' => 'Berdasarkan hasil seleksi PMB Politeknik Mitra Industri, Anda dinyatakan diterima sebagai calon mahasiswa baru. Silakan melanjutkan proses daftar ulang sesuai jadwal dan ketentuan.',
        'cadangan' => 'Berdasarkan hasil seleksi PMB Politeknik Mitra Industri, Anda masuk daftar cadangan. Informasi lanjutan akan disampaikan oleh panitia PMB.',
        'ditolak' => 'Berdasarkan hasil seleksi PMB Politeknik Mitra Industri, Anda belum dinyatakan lolos seleksi pada periode ini. Terima kasih atas partisipasi Anda.',
        default => 'Hasil seleksi belum diumumkan. Silakan pantau dashboard secara berkala.',
    };

    $heroClass = match ($status) {
        'diterima' => 'from-green-600 to-green-800 shadow-green-900/20',
        'cadangan' => 'from-yellow-500 to-yellow-700 shadow-yellow-900/20',
        'ditolak' => 'from-red-600 to-red-800 shadow-red-900/20',
        default => 'from-polmind-blue to-blue-900 shadow-blue-900/20',
    };

    $statusBadgeClass = match ($status) {
        'diterima' => 'bg-green-100 text-green-700',
        'cadangan' => 'bg-yellow-100 text-yellow-700',
        'ditolak' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };

    $statusLabel = match ($status) {
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'ditolak' => 'Ditolak',
        default => 'Belum Diumumkan',
    };

    $reRegistrationInvoice = $applicant->reRegistrationInvoice;
@endphp

<div class="space-y-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-3xl bg-gradient-to-br {{ $heroClass }} shadow-xl">
        <div class="grid gap-8 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-white/80">
                    Hasil Seleksi PMB
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    {{ $title }}
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/85">
                    {{ $message }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-white">
                        {{ $applicant->registration_number }}
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-center">
                <div class="rounded-3xl bg-white/10 p-8 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 text-5xl">
                        @if($status === 'diterima')
                            🎉
                        @elseif($status === 'cadangan')
                            ⏳
                        @elseif($status === 'ditolak')
                            📌
                        @else
                            🔔
                        @endif
                    </div>

                    <p class="mt-5 text-sm font-bold text-white/80">
                        Status
                    </p>
                    <p class="mt-2 text-2xl font-black">
                        {{ $statusLabel }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-xl font-black text-polmind-blue">Ringkasan Hasil</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Informasi hasil seleksi dan data pendaftaran Anda.
                </p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Nama Camaba</p>
                    <p class="mt-2 text-sm font-bold text-slate-900">{{ $applicant->full_name }}</p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Nomor Pendaftaran</p>
                    <p class="mt-2 text-sm font-bold text-slate-900">{{ $applicant->registration_number }}</p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Program Studi</p>
                    <p class="mt-2 text-sm font-bold text-slate-900">{{ $applicant->studyProgram?->name ?? '-' }}</p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Kelas</p>
                    <p class="mt-2 text-sm font-bold text-slate-900">{{ $applicant->classType?->name ?? '-' }}</p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Gelombang</p>
                    <p class="mt-2 text-sm font-bold text-slate-900">{{ $applicant->admissionWave?->name ?? '-' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Status Seleksi</p>
                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $statusBadgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            @if($applicant->selection?->note)
                <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <p class="text-sm font-black text-polmind-blue">Catatan Panitia</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">
                        {{ $applicant->selection->note }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Action --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-polmind-blue">Aksi Selanjutnya</h2>

            @if($status === 'diterima')
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Silakan lanjutkan proses daftar ulang melalui pembayaran tagihan daftar ulang.
                </p>

                <a href="{{ route('camaba.payments.index') }}"
                   class="mt-5 inline-flex w-full justify-center rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                    Lanjut Daftar Ulang
                </a>
            @elseif($status === 'cadangan')
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Silakan menunggu informasi lanjutan dari panitia PMB.
                </p>
            @elseif($status === 'ditolak')
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Terima kasih telah mengikuti proses seleksi PMB Politeknik Mitra Industri.
                </p>
            @else
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Pengumuman belum tersedia. Silakan cek dashboard secara berkala.
                </p>
            @endif

            <a href="{{ route('camaba.status-seleksi') }}"
               class="mt-3 inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                Kembali ke Status Seleksi
            </a>
        </div>
    </div>

    {{-- Daftar Ulang --}}
    @if($status === 'diterima')
        <div class="grid gap-6 lg:grid-cols-3">

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">Tagihan Daftar Ulang</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Tagihan daftar ulang otomatis dibuat setelah camaba dinyatakan diterima.
                    </p>
                </div>

                @if($reRegistrationInvoice)
                    <div class="mt-6">
                        <div class="flex flex-col justify-between gap-4 rounded-2xl bg-slate-50 p-5 md:flex-row md:items-center">
                            <div>
                                <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                                <p class="mt-1 font-black text-polmind-blue">{{ $reRegistrationInvoice->invoice_number }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                                <p class="mt-1 text-2xl font-black text-green-700">
                                    Rp{{ number_format($reRegistrationInvoice->total_amount, 0, ',', '.') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-bold text-slate-500">Status</p>
                                <p class="mt-1 font-black text-slate-800">
                                    {{ str_replace('_', ' ', ucfirst($reRegistrationInvoice->status)) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse($reRegistrationInvoice->items as $item)
                                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-4">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $item->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Qty {{ $item->quantity }}</p>
                                    </div>

                                    <p class="font-black text-polmind-blue">
                                        Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                    </p>
                                </div>
                            @empty
                                <p class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">
                                    Rincian daftar ulang belum tersedia.
                                </p>
                            @endforelse
                        </div>

                        <a href="{{ route('camaba.payments.index') }}"
                           class="mt-6 inline-flex rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                            Upload Bukti Daftar Ulang
                        </a>
                    </div>
                @else
                    <p class="mt-6 rounded-2xl bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                        Tagihan daftar ulang belum tersedia. Silakan hubungi admin PMB jika status Anda sudah diterima.
                    </p>
                @endif
            </div>

            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">Catatan Penting</h3>
                <p class="mt-2 text-sm leading-6 text-yellow-800">
                    Daftar ulang wajib diselesaikan sesuai batas waktu yang ditentukan oleh panitia PMB.
                </p>
            </div>

        </div>
    @endif

</div>
@endsection