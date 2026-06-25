@extends('layouts.camaba')

@section('title', 'Daftar Ulang')
@section('page_title', 'Daftar Ulang')
@section('page_subtitle', 'Pantau proses daftar ulang dan validasi pembayaran Anda.')

@section('content')
@php
    $selectionStatus = $applicant->selection_status ?? 'belum_diseleksi';
    $reRegistrationStatus = $applicant->re_registration_status ?? 'belum_daftar_ulang';
    $invoiceStatus = $invoice?->status ?? 'unpaid';

    $reRegistrationLabels = [
        'belum_daftar_ulang' => 'Belum Daftar Ulang',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'daftar_ulang_valid' => 'Daftar Ulang Valid',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
        'lewat_batas' => 'Lewat Batas',
    ];

    $invoiceLabels = [
        'unpaid' => 'Belum Bayar',
        'waiting_verification' => 'Menunggu Verifikasi',
        'partial' => 'Belum Lunas',
        'paid' => 'Lunas',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    $badgeClass = function ($status) {
        return match ($status) {
            'valid', 'daftar_ulang_valid', 'paid', 'siap_sinkron', 'sudah_sinkron' => 'bg-green-100 text-green-700',
            'menunggu_verifikasi', 'waiting_verification', 'belum_daftar_ulang', 'unpaid' => 'bg-yellow-100 text-yellow-700',
            'ditolak', 'rejected', 'cancelled', 'lewat_batas' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
            'partial' => 'bg-blue-100 text-polmind-blue',
        };
    };

    $deadline = $reRegistration?->deadline_date ?? $invoice?->due_date;

    $proofPath = $latestPayment?->proof_file_path;
    $proofUrl = $proofPath ? asset('storage/' . $proofPath) : null;

    $validPaidAmount = $invoice?->payments?->where('status', 'valid')->sum('amount') ?? 0;
    $waitingPaidAmount = $invoice?->payments?->where('status', 'waiting_verification')->sum('amount') ?? 0;
    $remainingAmount = $invoice ? max(0, (float) $invoice->total_amount - (float) $validPaidAmount) : 0;

    $canReRegister = $selectionStatus === 'diterima';
    $hasInvoice = (bool) $invoice;

    $steps = [
        [
            'title' => 'Dinyatakan Diterima',
            'desc' => 'Camaba sudah dinyatakan diterima oleh panitia PMB.',
            'done' => $selectionStatus === 'diterima',
        ],
        [
            'title' => 'Invoice Daftar Ulang Dibuat',
            'desc' => 'Tagihan daftar ulang tersedia di sistem.',
            'done' => $hasInvoice,
        ],
        [
            'title' => 'Upload Bukti Pembayaran',
            'desc' => 'Camaba mengupload bukti pembayaran daftar ulang.',
            'done' => in_array($invoiceStatus, ['waiting_verification', 'paid']) || in_array($reRegistrationStatus, ['menunggu_verifikasi', 'daftar_ulang_valid']),
        ],
        [
            'title' => 'Verifikasi Admin',
            'desc' => 'Admin PMB memvalidasi pembayaran daftar ulang.',
            'done' => in_array($reRegistrationStatus, ['daftar_ulang_valid', 'valid']) || $invoiceStatus === 'paid',
        ],
        [
            'title' => 'Siap Sinkron SIAKAD',
            'desc' => 'Data camaba siap disinkronkan ke SIAKAD.',
            'done' => in_array($applicant->sync_status, ['siap_sinkron', 'proses_sinkron', 'sudah_sinkron']),
        ],
        [
            'title' => 'NIM Diterbitkan',
            'desc' => 'NIM akan tampil setelah proses sinkronisasi SIAKAD selesai.',
            'done' => $applicant->sync_status === 'sudah_sinkron' || filled($applicant->nim),
        ],
    ];
@endphp

<div class="space-y-8">

    {{-- Header --}}
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
                <p class="text-sm font-bold text-blue-100">Status Daftar Ulang</p>
                <p class="mt-2 text-2xl font-black text-polmind-yellow">
                    {{ $reRegistrationLabels[$reRegistrationStatus] ?? str_replace('_', ' ', ucfirst($reRegistrationStatus)) }}
                </p>
                <p class="mt-2 text-xs text-blue-100">
                    {{ $deadline ? 'Batas: ' . $deadline->format('d M Y') : 'Batas waktu belum tersedia' }}
                </p>
            </div>
        </div>
    </div>

    @if(! $canReRegister)
        <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
            <h2 class="text-xl font-black text-yellow-800">Daftar Ulang Belum Tersedia</h2>
            <p class="mt-3 text-sm leading-6 text-yellow-800">
                Proses daftar ulang hanya dapat dilakukan setelah Anda dinyatakan diterima oleh panitia PMB.
                Silakan pantau status seleksi terlebih dahulu.
            </p>

            <a href="{{ route('camaba.status-seleksi') }}"
               class="mt-5 inline-flex rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                Cek Status Seleksi
            </a>
        </div>
    @else
        {{-- Summary Cards --}}
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Status Seleksi</p>
                <span class="mt-3 inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                    Diterima
                </span>
                <p class="mt-3 text-xs leading-5 text-slate-500">
                    Anda dapat melanjutkan proses daftar ulang.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Status Invoice</p>
                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($invoiceStatus) }}">
                    {{ $invoiceLabels[$invoiceStatus] ?? str_replace('_', ' ', ucfirst($invoiceStatus)) }}
                </span>
                <p class="mt-3 text-xs leading-5 text-slate-500">
                    {{ $invoice?->invoice_number ?? 'Invoice belum tersedia' }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Status Daftar Ulang</p>
                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($reRegistrationStatus) }}">
                    {{ $reRegistrationLabels[$reRegistrationStatus] ?? str_replace('_', ' ', ucfirst($reRegistrationStatus)) }}
                </span>
                <p class="mt-3 text-xs leading-5 text-slate-500">
                    {{ $reRegistration?->validated_at ? 'Divalidasi: ' . $reRegistration->validated_at->format('d M Y H:i') : 'Belum divalidasi admin' }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Status SIAKAD</p>
                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($applicant->sync_status) }}">
                    {{ str_replace('_', ' ', ucfirst($applicant->sync_status ?? 'belum_siap')) }}
                </span>
                <p class="mt-3 text-xs leading-5 text-slate-500">
                    {{ $applicant->nim ? 'NIM: ' . $applicant->nim : 'NIM belum tersedia' }}
                </p>
            </div>
        </div>

        <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

            {{-- Main --}}
            <div class="space-y-8">

                {{-- Invoice --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-start">
                        <div>
                            <h2 class="text-xl font-black text-polmind-blue">Tagihan Daftar Ulang</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Rincian tagihan daftar ulang yang harus diselesaikan.
                            </p>
                        </div>

                        @if($invoice)
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($invoiceStatus) }}">
                                {{ $invoiceLabels[$invoiceStatus] ?? str_replace('_', ' ', ucfirst($invoiceStatus)) }}
                            </span>
                        @endif
                    </div>

                    @if($invoice)
                        <div class="mt-6 rounded-2xl bg-slate-50 p-5">
                            <div class="grid gap-5 md:grid-cols-4">
                                <div>
                                    <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                                    <p class="mt-1 font-black text-polmind-blue">
                                        {{ $invoice->invoice_number }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                                    <p class="mt-1 text-2xl font-black text-green-700">
                                        Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-slate-500">Sudah Dibayar</p>
                                    <p class="mt-1 text-2xl font-black text-polmind-blue">
                                        Rp{{ number_format($validPaidAmount, 0, ',', '.') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-slate-500">Sisa Tagihan</p>
                                    <p class="mt-1 text-2xl font-black text-red-600">
                                        Rp{{ number_format($remainingAmount, 0, ',', '.') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-slate-500">Jatuh Tempo</p>
                                    <p class="mt-1 font-bold text-slate-800">
                                        {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse($invoice->items as $item)
                                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-4">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $item->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Qty {{ $item->quantity }} × Rp{{ number_format($item->amount, 0, ',', '.') }}
                                        </p>
                                    </div>

                                    <p class="font-black text-polmind-blue">
                                        Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                    </p>
                                </div>
                            @empty
                                <p class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">
                                    Rincian tagihan belum tersedia.
                                </p>
                            @endforelse
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            @if($invoice->status !== 'paid')
                                <a href="{{ route('camaba.payments.index') }}"
                                   class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                                    Upload Bukti Pembayaran
                                </a>
                            @endif

                            <a href="{{ route('camaba.payments.index') }}"
                               class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                Lihat Halaman Pembayaran
                            </a>
                        </div>
                    @else
                        <div class="mt-6 rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-sm leading-6 text-yellow-800">
                            Invoice daftar ulang belum tersedia. Coba kembali lagi nanti atau hubungi admin PMB.
                        </div>
                    @endif
                </div>

                {{-- Payment Proof --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-200 pb-5">
                        <h2 class="text-xl font-black text-polmind-blue">Bukti Pembayaran Terakhir</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Bukti pembayaran terakhir yang diupload untuk tagihan daftar ulang.
                        </p>
                    </div>

                    @if($latestPayment)
                        <div class="mt-6 rounded-2xl bg-slate-50 p-5">
                            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-start">
                                <div>
                                    <p class="text-sm font-black text-polmind-blue">
                                        {{ $latestPayment->payment_number }}
                                    </p>

                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $latestPayment->sender_name ?? '-' }} · {{ $latestPayment->sender_bank ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-600">
                                        Tanggal Transfer:
                                        <span class="font-bold">
                                            {{ $latestPayment->transfer_date?->format('d M Y') ?? '-' }}
                                        </span>
                                    </p>

                                    @if($latestPayment->proof_file_name)
                                        <p class="mt-1 text-xs text-slate-500">
                                            File: {{ $latestPayment->proof_file_name }}
                                        </p>
                                    @endif

                                    <p class="mt-3 text-2xl font-black text-green-700">
                                        Rp{{ number_format($latestPayment->amount, 0, ',', '.') }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($latestPayment->status) }}">
                                        {{ str_replace('_', ' ', ucfirst($latestPayment->status)) }}
                                    </span>

                                    @if($proofUrl)
                                        <a href="{{ $proofUrl }}"
                                           target="_blank"
                                           class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                                            Lihat Bukti
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if($latestPayment->admin_note)
                                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700">
                                    <p class="font-black">Catatan Admin</p>
                                    <p class="mt-1">{{ $latestPayment->admin_note }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="mt-6 rounded-2xl bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                            Belum ada bukti pembayaran daftar ulang yang diupload.
                        </div>
                    @endif
                </div>

                {{-- Timeline --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-200 pb-5">
                        <h2 class="text-xl font-black text-polmind-blue">Timeline Daftar Ulang</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Progress daftar ulang hingga sinkronisasi SIAKAD.
                        </p>
                    </div>

                    <div class="mt-6 space-y-4">
                        @foreach($steps as $index => $step)
                            <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-green-600 text-white' : 'bg-slate-200 text-slate-500' }} text-sm font-black">
                                    {{ $step['done'] ? '✓' : $index + 1 }}
                                </div>

                                <div>
                                    <p class="font-black text-slate-900">{{ $step['title'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        {{ $step['desc'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <aside class="space-y-6">

                <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
                    <h2 class="text-lg font-black text-polmind-blue">Informasi Penting</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-700">
                        Daftar ulang dianggap selesai setelah bukti pembayaran divalidasi oleh admin PMB.
                    </p>

                    @if($deadline)
                        <div class="mt-5 rounded-2xl bg-white p-5">
                            <p class="text-xs font-bold text-slate-500">Batas Daftar Ulang</p>
                            <p class="mt-1 text-xl font-black text-polmind-blue">
                                {{ $deadline->format('d M Y') }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-black text-polmind-blue">Status Validasi</h2>

                    <div class="mt-5 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-bold text-slate-500">Status</p>
                            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($reRegistrationStatus) }}">
                                {{ $reRegistrationLabels[$reRegistrationStatus] ?? str_replace('_', ' ', ucfirst($reRegistrationStatus)) }}
                            </span>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-slate-500">Divalidasi Oleh</p>
                            <p class="mt-1 font-bold text-slate-800">
                                {{ $reRegistration?->validated_by_name ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-slate-500">Waktu Validasi</p>
                            <p class="mt-1 font-bold text-slate-800">
                                {{ $reRegistration?->validated_at?->format('d M Y H:i') ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-slate-500">Siap Sinkron</p>
                            <p class="mt-1 font-bold text-slate-800">
                                {{ $reRegistration?->ready_sync_at?->format('d M Y H:i') ?? '-' }}
                            </p>
                        </div>
                    </div>

                    @if($reRegistration?->admin_note)
                        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700">
                            <p class="font-black">Catatan Admin</p>
                            <p class="mt-1">{{ $reRegistration->admin_note }}</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-black text-polmind-blue">Navigasi</h2>

                    <div class="mt-5 space-y-3">
                        <a href="{{ route('camaba.pengumuman') }}"
                           class="block rounded-xl border border-slate-300 bg-white px-5 py-3 text-center text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            Pengumuman Seleksi
                        </a>

                        <a href="{{ route('camaba.payments.index') }}"
                           class="block rounded-xl border border-slate-300 bg-white px-5 py-3 text-center text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            Pembayaran
                        </a>

                        <a href="{{ route('camaba.dashboard') }}"
                           class="block rounded-xl bg-polmind-blue px-5 py-3 text-center text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                            Dashboard
                        </a>
                    </div>
                </div>

            </aside>
        </div>
    @endif

</div>
@endsection