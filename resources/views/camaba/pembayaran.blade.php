@extends('layouts.camaba')

@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')
@section('page_subtitle', 'Lihat tagihan dan upload bukti pembayaran Anda.')

@section('content')
@php
    $invoiceTypeLabels = [
        'registration' => 'Pendaftaran',
        're_registration' => 'Daftar Ulang',
    ];

    $invoiceStatusLabels = [
        'unpaid' => 'Belum Bayar',
        'waiting_verification' => 'Menunggu Verifikasi',
        'partial' => 'Belum Lunas',
        'paid' => 'Lunas',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    $paymentStatusLabels = [
        'waiting_verification' => 'Menunggu Verifikasi',
        'valid' => 'Valid',
        'rejected' => 'Ditolak',
    ];

    $badgeClass = function ($status) {
        return match ($status) {
            'valid', 'paid' => 'bg-green-100 text-green-700',
            'waiting_verification', 'unpaid' => 'bg-yellow-100 text-yellow-700',
            'partial' => 'bg-blue-100 text-polmind-blue',
            'rejected', 'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    };

    $totalActiveAmount = $invoices->sum(function ($invoice) {
        $validPaid = $invoice->payments->where('status', 'valid')->sum('amount');
        return max(0, (float) $invoice->total_amount - (float) $validPaid);
    });

    $invoiceCount = $invoices->count();
@endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Nomor Pendaftaran</p>
                <h1 class="mt-2 text-3xl font-black">{{ $applicant->registration_number }}</h1>
                <p class="mt-3 text-sm leading-6 text-blue-100">
                    Silakan lakukan pembayaran sesuai tagihan, lalu upload bukti pembayaran.
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-72">
                <p class="text-sm font-bold text-blue-100">Total Tagihan Aktif</p>
                <p class="mt-2 text-3xl font-black text-polmind-yellow">
                    Rp{{ number_format($totalActiveAmount, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-xs text-blue-100">
                    {{ $invoiceCount }} invoice tersedia
                </p>
            </div>
        </div>
    </div>

    {{-- Informasi Pembayaran --}}
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
        <div class="border-b border-blue-100 pb-4">
            <h2 class="text-xl font-black text-polmind-blue">Informasi Pembayaran</h2>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-slate-500">Bank</p>
                <p class="mt-2 text-xl font-black text-polmind-blue">BCA / Mandiri / BNI</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-slate-500">No. Rekening</p>
                <p class="mt-2 text-xl font-black text-polmind-blue">0000000000</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-slate-500">Atas Nama</p>
                <p class="mt-2 text-xl font-black text-polmind-blue">Politeknik Mitra Industri</p>
            </div>
        </div>

        <p class="mt-4 text-sm leading-6 text-slate-600">
            Pastikan nominal pembayaran sesuai tagihan. Setelah bukti diupload, admin PMB akan melakukan verifikasi.
        </p>
    </div>

    {{-- Invoice List --}}
    <div class="space-y-6">
        @forelse($invoices as $invoice)
            @php
                $invoiceTypeLabel = $invoiceTypeLabels[$invoice->type] ?? ucfirst(str_replace('_', ' ', $invoice->type));
                $invoiceStatus = $invoice->status ?? 'unpaid';

                $validPaidAmount = (float) $invoice->payments->where('status', 'valid')->sum('amount');
                $waitingPaidAmount = (float) $invoice->payments->where('status', 'waiting_verification')->sum('amount');
                $remainingAmount = max(0, (float) $invoice->total_amount - $validPaidAmount);

                $paymentHistories = $invoice->payments->sortByDesc('created_at')->values();
                $hasWaitingPayment = $waitingPaidAmount > 0;
            @endphp

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                {{-- Top Header --}}
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-black text-polmind-blue">
                                {{ $invoiceTypeLabel }}
                            </h2>

                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass($invoiceStatus) }}">
                                {{ $invoiceStatusLabels[$invoiceStatus] ?? str_replace('_', ' ', ucfirst($invoiceStatus)) }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-slate-600">
                            Invoice ini berisi rincian tagihan dan histori pembayaran.
                        </p>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                        <p class="mt-2 font-black text-polmind-blue break-all">
                            {{ $invoice->invoice_number }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                        <p class="mt-2 text-2xl font-black text-green-700">
                            Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">Sudah Dibayar</p>
                        <p class="mt-2 text-2xl font-black text-polmind-blue">
                            Rp{{ number_format($validPaidAmount, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">Menunggu Verifikasi</p>
                        <p class="mt-2 text-2xl font-black text-yellow-700">
                            Rp{{ number_format($waitingPaidAmount, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">Sisa Tagihan</p>
                        <p class="mt-2 text-2xl font-black text-red-600">
                            Rp{{ number_format($remainingAmount, 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <span class="font-bold">Jatuh Tempo:</span>
                    {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                </div>

                {{-- Rincian Tagihan --}}
                <div class="mt-6">
                    <h3 class="text-lg font-black text-slate-800">Rincian Tagihan</h3>

                    <div class="mt-4 space-y-3">
                        @forelse($invoice->items as $item)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-5 py-4">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $item->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Qty {{ $item->quantity }}
                                    </p>
                                </div>

                                <p class="font-black text-polmind-blue">
                                    Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">
                                Belum ada rincian item tagihan.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Status Info / Upload Form --}}
                <div class="mt-6">
                    @if($invoiceStatus === 'paid')
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-black text-green-700">
                                    ✓
                                </div>

                                <div>
                                    <p class="text-sm font-black text-slate-800">Tagihan Lunas</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Pembayaran sudah tervalidasi oleh admin PMB.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($invoiceStatus === 'cancelled')
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm font-bold text-red-700">
                            Tagihan ini dibatalkan.
                        </div>
                    @elseif($hasWaitingPayment)
                        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yellow-100 text-sm font-black text-yellow-700">
                                    !
                                </div>

                                <div>
                                    <p class="text-sm font-black text-slate-800">Menunggu Verifikasi</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">
                                        Masih ada pembayaran yang sedang diverifikasi admin. Silakan tunggu sebelum upload lagi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-lg font-black text-slate-800">Upload Bukti Pembayaran</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                Anda boleh membayar sebagian atau langsung melunasi sesuai sisa tagihan.
                            </p>

                            {{-- Sesuaikan route action ini kalau nama route di project kamu berbeda --}}
                            <form action="{{ route('camaba.payments.upload-proof', $invoice->id) }}" method="POST" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-2">
                                @csrf

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Nama Pengirim</label>
                                    <input type="text"
                                           name="sender_name"
                                           value="{{ old('sender_name') }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Bank Pengirim</label>
                                    <input type="text"
                                           name="sender_bank"
                                           value="{{ old('sender_bank') }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Tanggal Transfer</label>
                                    <input type="date"
                                           name="transfer_date"
                                           value="{{ old('transfer_date') }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Nominal Bayar</label>
                                    <input type="number"
                                           name="amount"
                                           min="1"
                                           max="{{ $remainingAmount }}"
                                           value="{{ old('amount', $remainingAmount) }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>

                                    <p class="mt-2 text-xs text-slate-500">
                                        Sisa tagihan saat ini Rp{{ number_format($remainingAmount, 0, ',', '.') }}.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-sm font-bold text-slate-700">Bukti Pembayaran</label>
                                    <input type="file"
                                           name="proof_file"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none file:mr-4 file:rounded-lg file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-sm file:font-bold file:text-polmind-blue"
                                           required>

                                    <p class="mt-2 text-xs text-slate-500">
                                        Format: JPG, JPEG, PNG, PDF.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <button type="submit"
                                            class="inline-flex rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                                        Upload Bukti Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- Riwayat Pembayaran --}}
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col justify-between gap-2 border-b border-slate-200 pb-4 md:flex-row md:items-center">
                        <div>
                            <p class="text-sm font-black text-slate-800">Riwayat Pembayaran</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Menampilkan seluruh pembayaran untuk invoice ini.
                            </p>
                        </div>

                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600">
                            {{ $paymentHistories->count() }} transaksi
                        </span>
                    </div>

                    @if($paymentHistories->count())
                        <div class="mt-4 space-y-3">
                            @foreach($paymentHistories as $payment)
                                @php
                                    $historyProofUrl = $payment->proof_file_path
                                        ? asset('storage/' . $payment->proof_file_path)
                                        : null;

                                    $historyStatus = $payment->status ?? 'waiting_verification';
                                    $historyStatusClass = match ($historyStatus) {
                                        'valid' => 'bg-green-100 text-green-700',
                                        'waiting_verification' => 'bg-yellow-100 text-yellow-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };

                                    $historyStatusLabel = $paymentStatusLabels[$historyStatus] ?? str_replace('_', ' ', ucfirst($historyStatus));
                                @endphp

                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                                        <div>
                                            <p class="text-sm font-black text-polmind-blue">
                                                {{ $payment->payment_number }}
                                            </p>

                                            <p class="mt-2 text-sm text-slate-600">
                                                {{ $payment->sender_name ?? '-' }} · {{ $payment->sender_bank ?? '-' }}
                                            </p>

                                            <p class="mt-1 text-sm text-slate-600">
                                                Tanggal Transfer:
                                                <span class="font-bold">
                                                    {{ $payment->transfer_date?->format('d M Y') ?? '-' }}
                                                </span>
                                            </p>

                                            @if($payment->proof_file_name)
                                                <p class="mt-1 text-xs text-slate-500">
                                                    File: {{ $payment->proof_file_name }}
                                                </p>
                                            @endif

                                            <p class="mt-3 text-xl font-black {{ $historyStatus === 'rejected' ? 'text-red-600' : 'text-polmind-blue' }}">
                                                Rp{{ number_format($payment->amount, 0, ',', '.') }}
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $historyStatusClass }}">
                                                {{ $historyStatusLabel }}
                                            </span>

                                            @if($historyProofUrl)
                                                <a href="{{ $historyProofUrl }}"
                                                   target="_blank"
                                                   class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                                                    Lihat Bukti
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    @if($payment->admin_note)
                                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700">
                                            <p class="font-black">Catatan Admin</p>
                                            <p class="mt-1">{{ $payment->admin_note }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-4 rounded-xl bg-white p-4 text-sm text-slate-500">
                            Belum ada pembayaran untuk invoice ini.
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm">
                Belum ada invoice pembayaran.
            </div>
        @endforelse
    </div>

    {{-- Bottom Action --}}
    <div class="flex justify-end">
        <a href="{{ route('camaba.dashboard') }}"
           class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
            Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection