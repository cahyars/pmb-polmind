@extends('layouts.camaba')

@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')
@section('page_subtitle', 'Lihat tagihan dan upload bukti pembayaran Anda.')

@section('content')
@php
    $invoiceTypeLabels = [
        'registration' => 'Formulir Pendaftaran',
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

    @if(session('payment_submitted'))
        <div class="rounded-2xl border border-yellow-300 bg-yellow-50 px-5 py-4 text-sm leading-6 text-yellow-900" role="status">
            <p class="flex items-center gap-2 font-black"><x-icon name="clock" class="h-5 w-5 shrink-0" /> Bukti pembayaran terkirim dan sedang diverifikasi</p>
            <p>Admin PMB akan memeriksa pembayaran Anda maksimal 1x24 jam kerja. Status akan berubah menjadi <b>Lunas</b> setelah diverifikasi.</p>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => window.Swal.fire({
                icon: 'success',
                title: 'Bukti Pembayaran Terkirim',
                html: 'Pembayaran Anda <b>sedang diverifikasi</b> oleh admin PMB (maks. 1x24 jam kerja).<br>Kami akan membuka pengisian biodata dan berkas setelah pembayaran diverifikasi.',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#003B82',
            }));
        </script>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">
            <p class="font-bold">Bukti pembayaran belum terkirim:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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

    {{-- Tujuan Pembayaran --}}
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
        <div class="border-b border-blue-100 pb-4">
            <h2 class="text-xl font-black text-polmind-blue">Tujuan Pembayaran</h2>
            <p class="mt-1 text-sm text-slate-600">Transfer ke salah satu tujuan berikut, lalu unggah bukti pembayaran di bawah.</p>
        </div>

        @if($paymentMethods->isEmpty())
            <p class="mt-5 rounded-2xl bg-white p-5 text-sm font-semibold text-slate-600">
                Informasi rekening pembayaran belum tersedia. Silakan hubungi admin PMB melalui WhatsApp {{ \App\Services\AdminWhatsApp::display() }}.
            </p>
        @else
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($paymentMethods as $method)
                    <div class="rounded-2xl bg-white p-5 shadow-sm" x-data="{ copied: false }">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $method->type_label }}</p>
                        <p class="mt-2 text-lg font-black text-slate-900">{{ $method->provider }}</p>

                        @if($method->account_number)
                            <div class="mt-2 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                <span class="font-mono text-lg font-black tracking-wider text-polmind-blue">{{ $method->account_number }}</span>
                                <button type="button"
                                        @click="navigator.clipboard.writeText(@js($method->account_number)); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100"
                                        x-text="copied ? '✓ Tersalin' : 'Salin'">Salin</button>
                            </div>
                        @endif

                        @if($method->account_name)
                            <p class="mt-2 text-sm text-slate-600">a.n. <span class="font-bold">{{ $method->account_name }}</span></p>
                        @endif

                        @if($method->instructions)
                            <p class="mt-3 whitespace-pre-line text-xs leading-5 text-slate-500">{{ $method->instructions }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <p class="mt-4 text-sm leading-6 text-slate-600">
            Pastikan nominal transfer sesuai tagihan. Setelah bukti diunggah, admin PMB akan memverifikasi maksimal 1x24 jam kerja.
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
                                {{ $invoice->isWaived() ? 'Gratis' : ($invoiceStatusLabels[$invoiceStatus] ?? str_replace('_', ' ', ucfirst($invoiceStatus))) }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-slate-600">
                            Invoice ini berisi rincian tagihan dan histori pembayaran.
                        </p>
                    </div>

                    @if($invoiceStatus !== 'cancelled')
                        <a href="{{ route('camaba.print.invoice', $invoice) }}" target="_blank" rel="noopener"
                           class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                            <x-icon name="document-text" class="h-4 w-4" />
                            Cetak Invoice
                        </a>
                    @endif
                </div>

                {{-- Summary --}}
                @if($invoice->type === 'registration')
                    <div class="mt-6 grid gap-5 md:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                            <p class="mt-2 break-all font-black text-polmind-blue">{{ $invoice->invoice_number }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                            <p class="mt-2 text-2xl font-black text-slate-900">Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">
                            @if($invoice->isWaived())
                                <p class="text-xs font-bold text-slate-500">Status Biaya</p>
                                <p class="mt-2 text-2xl font-black text-green-700">Dibebaskan</p>
                            @else
                                <p class="text-xs font-bold text-slate-500">Sudah Dibayar</p>
                                <p class="mt-2 text-2xl font-black {{ $validPaidAmount > 0 ? 'text-green-700' : 'text-slate-400' }}">Rp{{ number_format($validPaidAmount, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                @else
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

                @endif

                {{-- Rincian Tagihan --}}
                <div class="mt-6 {{ $invoice->type === 'registration' ? 'hidden' : '' }}">
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
                                    @if($invoice->isWaived())
                                        <p class="text-sm font-black text-slate-800">Formulir Gratis</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Biaya formulir Anda dibebaskan oleh PMB Polmind. Tidak perlu melakukan pembayaran.
                                        </p>
                                    @else
                                        <p class="text-sm font-black text-slate-800">Tagihan Lunas</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Pembayaran sudah tervalidasi oleh admin PMB.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif($invoiceStatus === 'cancelled')
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm font-bold text-red-700">
                            Tagihan ini dibatalkan.
                        </div>
                    @elseif($hasWaitingPayment && $invoice->type === 'registration')
                        {{-- Status menunggu verifikasi cukup ditampilkan sebagai badge di atas. --}}
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

                                @if($paymentMethods->isNotEmpty())
                                    <div class="md:col-span-2">
                                        <p class="text-sm font-bold text-slate-700">Ditransfer ke <span class="text-red-500">*</span></p>
                                        <div class="mt-2 grid gap-3 md:grid-cols-2">
                                            @foreach($paymentMethods as $method)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 bg-white p-3 text-sm transition has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" required class="mt-1"
                                                           @checked((string) old('payment_method_id', $paymentMethods->count() === 1 ? $method->id : '') === (string) $method->id)>
                                                    <span>
                                                        <span class="block font-bold text-slate-800">{{ $method->provider }}</span>
                                                        <span class="block font-mono text-xs text-slate-500">{{ $method->account_number ?? $method->type_label }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Nama Pengirim</label>
                                    <input type="text"
                                           name="sender_name"
                                           value="{{ old('sender_name') }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Bank / E-Wallet Pengirim</label>
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
                                           max="{{ now()->format('Y-m-d') }}"
                                           value="{{ old('transfer_date', now()->format('Y-m-d')) }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700">Nominal Bayar</label>
                                    <input type="number"
                                           name="amount"
                                           min="1"
                                           max="{{ $remainingAmount }}"
                                           value="{{ $invoice->type === 'registration' ? $remainingAmount : old('amount', $remainingAmount) }}"
                                           @readonly($invoice->type === 'registration')
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"
                                           required>

                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $invoice->type === 'registration' ? 'Nominal sesuai biaya formulir' : 'Sisa tagihan saat ini' }}
                                        Rp{{ number_format($remainingAmount, 0, ',', '.') }}.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-sm font-bold text-slate-700">Bukti Pembayaran</label>
                                    <input type="file"
                                           name="proof_file"
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none file:mr-4 file:rounded-lg file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-sm file:font-bold file:text-polmind-blue"
                                           required>

                                    <p class="mt-2 text-xs text-slate-500">
                                        Format: JPG, JPEG, PNG, PDF. Maksimal 4 MB.
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
                                        ? route('camaba.payments.proof', $payment)
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

                                            @if($payment->paymentMethod)
                                                <p class="mt-1 text-sm text-slate-600">
                                                    Ke: <span class="font-bold">{{ $payment->paymentMethod->label }}</span>
                                                </p>
                                            @endif

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

                                            @if($historyStatus === 'valid')
                                                <a href="{{ route('camaba.print.receipt', $payment) }}" target="_blank" rel="noopener"
                                                   class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                                    Cetak Kwitansi
                                                </a>
                                            @endif

                                            @if($historyProofUrl)
                                                <a href="{{ $historyProofUrl }}"
                                                   @click.prevent="$store.preview.show($el.href, @js(basename($payment->proof_file_path)))"
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