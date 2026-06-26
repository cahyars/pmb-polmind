@extends('layouts.camaba')

@section('title', 'Dashboard Camaba')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Pantau progres pendaftaran mahasiswa baru Anda.')

@section('content')
@php
    $statusLabels = [
        'registrasi_awal' => 'Registrasi Awal',
        'biodata_lengkap' => 'Biodata Lengkap',
        'belum_upload' => 'Belum Upload',
        'sebagian_upload' => 'Sebagian Upload',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
        'belum_bayar' => 'Belum Bayar',
        'unpaid' => 'Belum Bayar',
        'waiting_verification' => 'Menunggu Verifikasi',
        'paid' => 'Lunas',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'belum_diseleksi' => 'Belum Diseleksi',
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'belum_daftar_ulang' => 'Belum Daftar Ulang',
        'daftar_ulang_valid' => 'Daftar Ulang Valid',
        'belum_siap' => 'Belum Siap',
        'siap_sinkron' => 'Siap Sinkron',
        'proses_sinkron' => 'Proses Sinkron',
        'sudah_sinkron' => 'Sudah Sinkron',
        'gagal_sinkron' => 'Gagal Sinkron',
    ];

    $badgeClass = function ($status) {
        return match ($status) {
            'valid', 'paid', 'diterima', 'daftar_ulang_valid', 'siap_sinkron', 'sudah_sinkron' => 'bg-green-100 text-green-700',
            'menunggu_verifikasi', 'waiting_verification', 'cadangan', 'proses_sinkron', 'sebagian_upload' => 'bg-yellow-100 text-yellow-700',
            'ditolak', 'rejected', 'cancelled', 'gagal_sinkron' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    };

    $formatStatus = function ($status) use ($statusLabels) {
        return $statusLabels[$status] ?? str_replace('_', ' ', ucfirst($status ?? '-'));
    };
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Selamat datang,</p>
                <h1 class="mt-2 text-3xl font-black">{{ $applicant->full_name }}</h1>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black text-white">
                        {{ $applicant->registration_number }}
                    </span>

                    <span class="rounded-full bg-polmind-yellow px-3 py-1 text-xs font-black text-polmind-blue-dark">
                        {{ $applicant->studyProgram?->code ?? '-' }}
                    </span>

                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black text-white">
                        {{ $applicant->classType?->name ?? '-' }}
                    </span>
                </div>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-80">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-blue-100">Progress Pendaftaran</p>
                    <p class="text-2xl font-black text-polmind-yellow">{{ $overallProgress }}%</p>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ min($overallProgress, 100) }}%"></div>
                </div>

                <p class="mt-3 text-xs text-blue-100">
                    {{ $completedSteps }} dari {{ $steps->count() }} tahapan selesai
                </p>
            </div>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Biodata',
                'status' => $applicant->registration_status,
                'desc' => 'Status kelengkapan data diri',
            ],
            [
                'label' => 'Berkas',
                'status' => $applicant->document_status,
                'desc' => $acceptedRequiredDocumentCount . '/' . $requiredDocumentCount . ' berkas wajib valid',
            ],
            [
                'label' => 'Pembayaran',
                'status' => $applicant->payment_status,
                'desc' => 'Status pembayaran pendaftaran',
            ],
            [
                'label' => 'Seleksi',
                'status' => $applicant->selection_status,
                'desc' => 'Status hasil seleksi',
            ],
            [
                'label' => 'Daftar Ulang',
                'status' => $applicant->re_registration_status,
                'desc' => 'Status daftar ulang',
            ],
            [
                'label' => 'Sinkron SIAKAD',
                'status' => $applicant->sync_status,
                'desc' => $applicant->nim ? 'NIM: ' . $applicant->nim : 'NIM belum tersedia',
            ],
            [
                'label' => 'Gelombang',
                'status' => $applicant->admissionWave?->status ?? '-',
                'desc' => $applicant->admissionWave?->name ?? '-',
            ],
            [
                'label' => 'Tahun PMB',
                'status' => $applicant->pmbYear?->status ?? '-',
                'desc' => $applicant->pmbYear?->name ?? '-',
            ],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>

                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($card['status']) }}">
                    {{ $formatStatus($card['status']) }}
                </span>

                <p class="mt-3 text-xs leading-5 text-slate-500">
                    {{ $card['desc'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

        {{-- Main --}}
        <div class="space-y-8">

            {{-- Progress Tahapan --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">Tahapan PMB</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Berikut progres tahapan pendaftaran Anda.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                        {{ $overallProgress }}% selesai
                    </span>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach($steps as $index => $step)
                        <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $step['status'] ? 'bg-green-600 text-white' : 'bg-slate-200 text-slate-500' }} text-sm font-black">
                                {{ $step['status'] ? '✓' : $index + 1 }}
                            </div>

                            <div>
                                <p class="font-black text-slate-900">{{ $step['label'] }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    {{ $step['description'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Invoice Pendaftaran --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">Tagihan Pendaftaran</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Tagihan biaya pendaftaran yang dibuat otomatis setelah registrasi.
                        </p>
                    </div>

                    @if($registrationInvoice)
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($registrationInvoice->status) }}">
                            {{ $formatStatus($registrationInvoice->status) }}
                        </span>
                    @endif
                </div>

                @if($registrationInvoice)
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                                <p class="mt-1 font-black text-polmind-blue">{{ $registrationInvoice->invoice_number }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                                <p class="mt-1 text-xl font-black text-green-700">
                                    Rp{{ number_format($registrationInvoice->total_amount, 0, ',', '.') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-bold text-slate-500">Jatuh Tempo</p>
                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $registrationInvoice->due_date?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-slate-200 pt-5">
                            <p class="text-sm font-black text-slate-800">Rincian Tagihan</p>

                            <div class="mt-3 space-y-2">
                                @foreach($registrationInvoice->items as $item)
                                    <div class="flex items-center justify-between rounded-xl bg-white px-4 py-3 text-sm">
                                        <span class="font-semibold text-slate-700">{{ $item->name }}</span>
                                        <span class="font-black text-slate-900">
                                            Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ url('/camaba/tagihan-pembayaran') }}"
                               class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                                Lihat Tagihan
                            </a>

                            @if($registrationInvoice->status !== 'paid')
                                <a href="{{ url('/camaba/tagihan-pembayaran') }}"
                                   class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                    Upload Bukti Bayar
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-2xl bg-slate-50 p-6 text-sm font-semibold text-slate-500">
                        Invoice pendaftaran belum tersedia.
                    </div>
                @endif
            </div>

            {{-- Invoice Daftar Ulang --}}
            @if($reRegistrationInvoice)
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                        <div>
                            <h2 class="text-xl font-black text-polmind-blue">Tagihan Daftar Ulang</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Tagihan daftar ulang muncul setelah Anda dinyatakan diterima.
                            </p>
                        </div>

                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($reRegistrationInvoice->status) }}">
                            {{ $formatStatus($reRegistrationInvoice->status) }}
                        </span>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                                <p class="mt-1 font-black text-polmind-blue">{{ $reRegistrationInvoice->invoice_number }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                                <p class="mt-1 text-xl font-black text-green-700">
                                    Rp{{ number_format($reRegistrationInvoice->total_amount, 0, ',', '.') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-bold text-slate-500">Jatuh Tempo</p>
                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $reRegistrationInvoice->due_date?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ url('/camaba/tagihan-pembayaran') }}"
                               class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                                Lihat Tagihan Daftar Ulang
                            </a>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            {{-- Profil Singkat --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Profil Pendaftaran</h2>

                <div class="mt-5 space-y-4 text-sm">
                    <div>
                        <p class="text-xs font-bold text-slate-500">Nama</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->full_name }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Email</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->email }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">WhatsApp</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->phone ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Pilihan Prodi</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $applicant->studyProgram?->name ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Jalur Pendaftaran</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $applicant->registration_path_label }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Jenis Kelas</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $applicant->classType?->name ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Progress Berkas --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Progress Berkas</h2>

                <div class="mt-5 flex items-end justify-between">
                    <div>
                        <p class="text-4xl font-black text-polmind-blue">{{ $documentProgress }}%</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            {{ $acceptedRequiredDocumentCount }} dari {{ $requiredDocumentCount }} berkas wajib valid
                        </p>
                    </div>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($documentProgress, 100) }}%"></div>
                </div>

                <a href="{{ route('camaba.documents.index') }}" class="mt-5 inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Kelola Berkas
                </a>
            </div>

            {{-- Hasil Seleksi --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Hasil Seleksi</h2>

                <div class="mt-5">
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($applicant->selection_status) }}">
                        {{ $formatStatus($applicant->selection_status) }}
                    </span>

                    @if($applicant->selection)
                        <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">Tes</p>
                                <p class="mt-1 font-black text-slate-800">
                                    {{ $applicant->selection->test_score ?? '-' }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">Interview</p>
                                <p class="mt-1 font-black text-slate-800">
                                    {{ $applicant->selection->interview_score ?? '-' }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">Final</p>
                                <p class="mt-1 font-black text-polmind-blue">
                                    {{ $applicant->selection->final_score ?? '-' }}
                                </p>
                            </div>
                        </div>

                        @if($applicant->selection->note)
                            <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                                {{ $applicant->selection->note }}
                            </p>
                        @endif
                    @else
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            Hasil seleksi belum tersedia. Silakan pantau dashboard secara berkala.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Bantuan --}}
            <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
                <h2 class="text-lg font-black text-polmind-blue">Butuh Bantuan?</h2>
                <p class="mt-3 text-sm leading-6 text-slate-700">
                    Jika mengalami kendala pendaftaran, silakan hubungi admin PMB Politeknik Mitra Industri.
                </p>

                <a href="https://wa.me/6280000000000"
                   target="_blank"
                   class="mt-5 inline-flex w-full justify-center rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-700">
                    Hubungi Admin PMB
                </a>
            </div>

        </aside>
    </div>

</div>
@endsection