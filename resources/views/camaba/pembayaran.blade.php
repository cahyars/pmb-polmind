@extends('layouts.camaba')

@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')
@section('page_subtitle', 'Lihat tagihan dan upload bukti pembayaran Anda.')

@section('content')
@php
    $statusLabels = [
        'unpaid' => 'Belum Bayar',
        'waiting_verification' => 'Menunggu Verifikasi',
        'partial' => 'Belum Lunas',
        'paid' => 'Lunas',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    $statusClasses = [
        'unpaid' => 'bg-slate-100 text-slate-600',
        'waiting_verification' => 'bg-yellow-100 text-yellow-700',
        'partial' => 'bg-blue-100 text-polmind-blue',
        'paid' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    $paymentStatusClasses = [
        'waiting_verification' => 'bg-yellow-100 text-yellow-700',
        'valid' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];

    $typeLabels = [
        'registration' => 'Pendaftaran',
        're_registration' => 'Daftar Ulang',
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
                    Silakan lakukan pembayaran sesuai nominal tagihan, lalu upload bukti pembayaran.
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-80">
                <p class="text-sm font-bold text-blue-100">Total Tagihan Aktif</p>
                <p class="mt-2 text-3xl font-black text-polmind-yellow">
                    Rp{{ number_format($invoices->whereNotIn('status', ['paid', 'cancelled'])->sum('total_amount'), 0, ',', '.') }}
                </p>
                <p class="mt-2 text-xs text-blue-100">
                    {{ $invoices->count() }} invoice tersedia
                </p>
            </div>
        </div>
    </div>

    {{-- Informasi Pembayaran --}}
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
        <h2 class="text-lg font-black text-polmind-blue">Informasi Pembayaran</h2>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl bg-white p-5">
                <p class="text-xs font-bold text-slate-500">Bank</p>
                <p class="mt-1 text-lg font-black text-polmind-blue">BCA / Mandiri / BNI</p>
            </div>

            <div class="rounded-2xl bg-white p-5">
                <p class="text-xs font-bold text-slate-500">No. Rekening</p>
                <p class="mt-1 text-lg font-black text-polmind-blue">0000000000</p>
            </div>

            <div class="rounded-2xl bg-white p-5">
                <p class="text-xs font-bold text-slate-500">Atas Nama</p>
                <p class="mt-1 text-lg font-black text-polmind-blue">Politeknik Mitra Industri</p>
            </div>
        </div>

        <p class="mt-4 text-sm leading-6 text-slate-700">
            Pastikan nominal pembayaran sesuai dengan tagihan. Setelah bukti diupload, admin PMB akan melakukan verifikasi.
        </p>
    </div>

    {{-- Invoice List --}}
    <div class="space-y-6">
        @forelse($invoices as $invoice)
            @php
                $latestPayment = $invoice->latestPayment;

                $proofPath = $latestPayment?->proof_file_path;
                $proofUrl = $proofPath ? asset('storage/' . $proofPath) : null;

                $invoiceStatus = $invoice->status ?? 'unpaid';

                $validPaidAmount = $invoice->payments
                    ->where('status', 'valid')
                    ->sum('amount');

                $waitingPaidAmount = $invoice->payments
                    ->where('status', 'waiting_verification')
                    ->sum('amount');

                $remainingAmount = max(0, (float) $invoice->total_amount - (float) $validPaidAmount);

                $hasWaitingPayment = $waitingPaidAmount > 0;
            @endphp

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">

                    {{-- Invoice Detail --}}
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-black text-polmind-blue">
                                {{ $typeLabels[$invoice->type] ?? $invoice->type }}
                            </h2>

                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$invoiceStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $statusLabels[$invoiceStatus] ?? str_replace('_', ' ', ucfirst($invoiceStatus)) }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-5 md:grid-cols-4">
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

                        {{-- Rincian Tagihan --}}
                        <div class="mt-6">
                            <p class="text-sm font-black text-slate-800">Rincian Tagihan</p>

                            <div class="mt-3 space-y-2">
                                @forelse($invoice->items as $item)
                                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                                        <span class="font-semibold text-slate-700">
                                            {{ $item->name }}
                                        </span>
                                        <span class="font-black text-slate-900">
                                            Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">
                                        Rincian tagihan belum tersedia.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Bukti Pembayaran Terakhir --}}
                        @if($latestPayment)
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                                    <div>
                                        <p class="text-sm font-black text-slate-800">
                                            Bukti Pembayaran Terakhir
                                        </p>

                                        <p class="mt-2 text-sm text-slate-600">
                                            {{ $latestPayment->payment_number }}
                                        </p>

                                        <p class="mt-1 text-sm text-slate-600">
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

                                        <p class="mt-2 font-black text-polmind-blue">
                                            Rp{{ number_format($latestPayment->amount, 0, ',', '.') }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $paymentStatusClasses[$latestPayment->status] ?? 'bg-slate-100 text-slate-600' }}">
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
                                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700">
                                        <p class="font-black">Catatan Admin</p>
                                        <p class="mt-1">{{ $latestPayment->admin_note }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Upload Form --}}
                    <div class="w-full lg:w-96">
                        @if($invoice->status === 'paid')
                            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-sm font-bold text-green-700">
                                Tagihan ini sudah lunas.
                            </div>
                        @elseif($invoice->status === 'cancelled')
                            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm font-bold text-red-700">
                                Tagihan ini sudah dibatalkan.
                            </div>
                        @elseif($hasWaitingPayment)
                            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-sm font-bold text-yellow-700">
                                Masih ada pembayaran yang menunggu verifikasi admin. Silakan tunggu validasi terlebih dahulu.
                            </div>
                        @else
                            <form action="{{ route('camaba.payments.upload-proof', $invoice) }}"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                @csrf

                                <h3 class="text-sm font-black text-polmind-blue">
                                    Upload Bukti Pembayaran
                                </h3>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="text-xs font-bold text-slate-600">Nama Pengirim</label>
                                        <input type="text"
                                               name="sender_name"
                                               required
                                               value="{{ old('sender_name', $latestPayment?->sender_name) }}"
                                               placeholder="Nama pemilik rekening"
                                               class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-600">Bank Pengirim</label>
                                        <input type="text"
                                               name="sender_bank"
                                               required
                                               value="{{ old('sender_bank', $latestPayment?->sender_bank) }}"
                                               placeholder="Contoh: BCA, Mandiri, BNI"
                                               class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-600">Tanggal Transfer</label>
                                        <input type="date"
                                               name="transfer_date"
                                               required
                                               value="{{ old('transfer_date', $latestPayment?->transfer_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                               class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-600">Nominal Bayar</label>
                                        <input type="number"
                                            name="amount"
                                            required
                                            min="1"
                                            max="{{ $remainingAmount }}"
                                            value="{{ old('amount', $remainingAmount) }}"
                                            class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <p class="mt-2 text-xs leading-5 text-slate-500">
                                            Sisa tagihan saat ini Rp{{ number_format($remainingAmount, 0, ',', '.') }}. Anda boleh membayar sebagian atau langsung melunasi.
                                        </p>
                                    </div>

                                    <div>
                                        <label class="text-xs font-bold text-slate-600">Bukti Pembayaran</label>
                                        <input type="file"
                                               name="proof_file"
                                               required
                                               class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none file:mr-4 file:rounded-lg file:border-0 file:bg-polmind-blue file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-polmind-blue-dark focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <p class="mt-2 text-xs leading-5 text-slate-500">
                                            Format: jpg, jpeg, png, pdf. Maksimal 4 MB.
                                        </p>
                                    </div>
                                </div>

                                <button type="submit"
                                        class="mt-5 w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                                    {{ $latestPayment ? 'Upload Ulang Bukti' : 'Upload Bukti Bayar' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                <p class="text-sm font-bold text-slate-600">
                    Belum ada tagihan pembayaran.
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Tagihan akan muncul setelah registrasi atau setelah dinyatakan diterima.
                </p>
            </div>
        @endforelse
    </div>

    {{-- Footer Action --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <p class="text-sm font-semibold text-slate-600">
                Setelah upload bukti, admin PMB akan melakukan verifikasi pembayaran.
            </p>

            <a href="{{ route('camaba.dashboard') }}"
               class="rounded-xl bg-polmind-blue px-5 py-3 text-center text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

</div>
@endsection