@extends('layouts.camaba')

@section('title', 'Status Seleksi')
@section('page_title', 'Status Seleksi')
@section('page_subtitle', 'Pantau status seleksi dan hasil PMB Anda.')

@section('content')
@php
    $selectionStatus = $applicant->selection_status ?? 'belum_diseleksi';

    $statusLabels = [
        'belum_diseleksi' => 'Belum Diseleksi',
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'ditolak' => 'Ditolak',
    ];

    $statusClasses = [
        'belum_diseleksi' => 'bg-slate-100 text-slate-600',
        'diterima' => 'bg-green-100 text-green-700',
        'cadangan' => 'bg-yellow-100 text-yellow-700',
        'ditolak' => 'bg-red-100 text-red-700',
    ];

    $heroTitle = match ($selectionStatus) {
        'diterima' => 'Selamat, Anda Dinyatakan Diterima',
        'cadangan' => 'Anda Masuk Daftar Cadangan',
        'ditolak' => 'Mohon Maaf, Anda Belum Dinyatakan Lolos',
        default => 'Status Seleksi Masih Dalam Proses',
    };

    $heroDesc = match ($selectionStatus) {
        'diterima' => 'Silakan lanjutkan proses daftar ulang melalui menu pembayaran atau halaman pengumuman.',
        'cadangan' => 'Status Anda masih menunggu keputusan lanjutan dari panitia PMB.',
        'ditolak' => 'Terima kasih telah mengikuti proses seleksi PMB Politeknik Mitra Industri.',
        default => 'Data Anda sedang diproses oleh panitia PMB. Silakan pantau halaman ini secara berkala.',
    };

    $selectionDone = in_array($selectionStatus, ['diterima', 'cadangan', 'ditolak']);
    $hasReRegistrationInvoice = (bool) $applicant->reRegistrationInvoice;
@endphp

<div class="space-y-8">

    {{-- Hero --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Nomor Pendaftaran</p>
                <h1 class="mt-2 text-3xl font-black">{{ $applicant->registration_number }}</h1>
                <p class="mt-3 text-sm leading-6 text-blue-100">
                    {{ $applicant->full_name }} · {{ $applicant->studyProgram?->name ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-80">
                <p class="text-sm font-bold text-blue-100">Status Seleksi</p>
                <p class="mt-2 text-3xl font-black text-polmind-yellow">
                    {{ $statusLabels[$selectionStatus] ?? $selectionStatus }}
                </p>
                <p class="mt-2 text-xs text-blue-100">
                    {{ $applicant->admissionWave?->name ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Main Result --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <span class="inline-flex rounded-full px-4 py-2 text-sm font-black {{ $statusClasses[$selectionStatus] ?? 'bg-slate-100 text-slate-600' }}">
            {{ $statusLabels[$selectionStatus] ?? $selectionStatus }}
        </span>

        <h2 class="mt-5 text-3xl font-black text-polmind-blue">
            {{ $heroTitle }}
        </h2>

        <p class="mx-auto mt-4 max-w-3xl text-sm leading-7 text-slate-600">
            {{ $heroDesc }}
        </p>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('camaba.pengumuman') }}"
               class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Lihat Pengumuman
            </a>

            @if($selectionStatus === 'diterima')
                <a href="{{ route('camaba.re-registration.index') }}"
                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Lanjut Daftar Ulang
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

        {{-- Main --}}
        <div class="space-y-8">

            {{-- Nilai Seleksi --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">Nilai Seleksi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Nilai akan tampil setelah panitia PMB melakukan input hasil seleksi.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-5 text-center">
                        <p class="text-sm font-bold text-slate-500">Nilai Tes</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">
                            {{ $applicant->selection?->test_score ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-5 text-center">
                        <p class="text-sm font-bold text-slate-500">Nilai Interview</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">
                            {{ $applicant->selection?->interview_score ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-blue-50 p-5 text-center">
                        <p class="text-sm font-bold text-polmind-blue">Nilai Akhir</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">
                            {{ $applicant->selection?->final_score ?? '-' }}
                        </p>
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

            {{-- Timeline --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">Timeline Seleksi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Tahapan proses seleksi dan daftar ulang Anda.
                    </p>
                </div>

                @php
                    $timelines = [
                        [
                            'title' => 'Registrasi Akun',
                            'desc' => 'Akun pendaftaran berhasil dibuat.',
                            'done' => true,
                        ],
                        [
                            'title' => 'Lengkapi Biodata',
                            'desc' => 'Data diri, alamat, pendidikan, dan orang tua dilengkapi.',
                            'done' => $applicant->registration_status === 'biodata_lengkap',
                        ],
                        [
                            'title' => 'Upload Berkas',
                            'desc' => 'Dokumen persyaratan diupload dan diverifikasi admin.',
                            'done' => $applicant->document_status === 'valid',
                        ],
                        [
                            'title' => 'Pembayaran Pendaftaran',
                            'desc' => 'Pembayaran pendaftaran divalidasi admin.',
                            'done' => $applicant->payment_status === 'valid',
                        ],
                        [
                            'title' => 'Keputusan Seleksi',
                            'desc' => 'Panitia menetapkan status diterima, cadangan, atau ditolak.',
                            'done' => $selectionDone,
                        ],
                        [
                            'title' => 'Daftar Ulang',
                            'desc' => 'Tagihan daftar ulang muncul jika camaba diterima.',
                            'done' => $hasReRegistrationInvoice,
                        ],
                    ];
                @endphp

                <div class="mt-6 space-y-4">
                    @foreach($timelines as $index => $timeline)
                        <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $timeline['done'] ? 'bg-green-600 text-white' : 'bg-slate-200 text-slate-500' }} text-sm font-black">
                                {{ $timeline['done'] ? '✓' : $index + 1 }}
                            </div>

                            <div>
                                <p class="font-black text-slate-900">{{ $timeline['title'] }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    {{ $timeline['desc'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Data Pendaftaran</h2>

                <div class="mt-5 space-y-4 text-sm">
                    <div>
                        <p class="text-xs font-bold text-slate-500">Nama</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->full_name }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Prodi Pilihan</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->studyProgram?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Kelas</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->classType?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Gelombang</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $applicant->admissionWave?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-500">Jalur Pendaftaran</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $applicant->registration_path_label }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Daftar Ulang</h2>

                @if($applicant->reRegistrationInvoice)
                    <div class="mt-5 rounded-2xl bg-slate-50 p-5">
                        <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                        <p class="mt-1 font-black text-polmind-blue">
                            {{ $applicant->reRegistrationInvoice->invoice_number }}
                        </p>

                        <p class="mt-4 text-xs font-bold text-slate-500">Total Tagihan</p>
                        <p class="mt-1 text-2xl font-black text-green-700">
                            Rp{{ number_format($applicant->reRegistrationInvoice->total_amount, 0, ',', '.') }}
                        </p>

                        <a href="{{ route('camaba.payments.index') }}"
                           class="mt-5 inline-flex w-full justify-center rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                            Lihat Pembayaran
                        </a>
                    </div>
                @else
                    <p class="mt-5 rounded-2xl bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                        Tagihan daftar ulang akan muncul jika Anda dinyatakan diterima.
                    </p>
                @endif
            </div>

        </aside>
    </div>
</div>
@endsection