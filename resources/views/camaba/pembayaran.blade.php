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

    $badgeClass = fn ($status) => match ($status) {
        'valid', 'paid' => 'bg-green-100 text-green-700',
        'waiting_verification', 'unpaid' => 'bg-yellow-100 text-yellow-700',
        'partial' => 'bg-blue-100 text-polmind-blue',
        'rejected', 'cancelled' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };

    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');

    // Ringkasan per tagihan, dipakai untuk header halaman dan isi accordion.
    $cards = $invoices->map(function ($invoice) use ($invoiceTypeLabels) {
        $validPaid = (float) $invoice->payments->where('status', 'valid')->sum('amount');
        $waitingPaid = (float) $invoice->payments->where('status', 'waiting_verification')->sum('amount');
        $status = $invoice->status ?? 'unpaid';
        $isClosed = in_array($status, ['paid', 'cancelled'], true);
        $remaining = max(0, (float) $invoice->total_amount - $validPaid);
        $latestPayment = $invoice->payments->sortByDesc('created_at')->first();

        return [
            'invoice' => $invoice,
            'label' => $invoiceTypeLabels[$invoice->type] ?? ucfirst(str_replace('_', ' ', $invoice->type)),
            'status' => $status,
            'validPaid' => $validPaid,
            'waitingPaid' => $waitingPaid,
            'remaining' => $remaining,
            'isWaiting' => ! $isClosed && $waitingPaid > 0,
            'needsAction' => ! $isClosed && $waitingPaid <= 0 && $remaining > 0,
            // Tagihan aktif tanpa nominal (komponen biaya belum diatur admin).
            'noAmount' => ! $isClosed && $waitingPaid <= 0 && $remaining <= 0,
            'lastRejected' => $latestPayment?->status === 'rejected' ? $latestPayment : null,
            // Jadwal dari admin untuk camaba tertentu; selain itu daftar ulang dibayar sekaligus.
            'nextDue' => $invoice->type === 're_registration' && $invoice->hasInstallments() ? $invoice->nextInstallmentDue() : null,
        ];
    })->values();

    $totalRemaining = $cards->filter(fn ($card) => $card['status'] !== 'cancelled')->sum('remaining');
    $nextCard = $cards->firstWhere('needsAction', true);
    $waitingCard = $cards->firstWhere('isWaiting', true);
    $noAmountCard = $cards->firstWhere('noAmount', true);
@endphp

<div class="space-y-6">

    @if(session('payment_submitted'))
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

    {{-- Ringkasan: satu angka dan langkah berikutnya --}}
    <div class="rounded-3xl bg-polmind-blue p-5 text-white shadow-lg shadow-blue-900/20 sm:p-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Sisa yang perlu dibayar</p>
                <p class="mt-1 text-3xl font-black text-polmind-yellow">{{ $rupiah($totalRemaining) }}</p>
            </div>

            <p class="flex items-start gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm leading-6 text-blue-50 sm:max-w-sm">
                @if($nextCard)
                    <x-icon name="arrow-up-right" class="mt-1 h-4 w-4 shrink-0" />
                    <span>Langkah berikutnya: bayar <b>{{ $nextCard['label'] }}</b>, lalu unggah bukti transfer di bawah.</span>
                @elseif($waitingCard)
                    <x-icon name="clock" class="mt-1 h-4 w-4 shrink-0" />
                    <span>Bukti pembayaran <b>{{ $waitingCard['label'] }}</b> sedang diverifikasi admin (maks. 1x24 jam kerja).</span>
                @elseif($noAmountCard)
                    <x-icon name="warning" class="mt-1 h-4 w-4 shrink-0" />
                    <span>Nominal tagihan <b>{{ $noAmountCard['label'] }}</b> belum ditetapkan. Silakan hubungi admin PMB.</span>
                @elseif($cards->isNotEmpty())
                    <x-icon name="check" class="mt-1 h-4 w-4 shrink-0" />
                    <span>Semua tagihan sudah selesai. Tidak ada pembayaran yang perlu dilakukan.</span>
                @else
                    <span>Belum ada tagihan untuk Anda.</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Tagihan (accordion) --}}
    @forelse($cards as $card)
        @php
            $invoice = $card['invoice'];
            $status = $card['status'];
            $isRegistration = $invoice->type === 'registration';
            $hasFailedUpload = (string) old('invoice_id') === (string) $invoice->id;
            $startOpen = $card['needsAction'] || $card['noAmount'] || $hasFailedUpload || $cards->count() === 1;
            $statusLabel = $invoice->isWaived() ? 'Gratis' : ($invoiceStatusLabels[$status] ?? str_replace('_', ' ', ucfirst($status)));
            $paymentHistories = $invoice->payments->sortByDesc('created_at')->values();
            $progress = (float) $invoice->total_amount > 0 ? min(100, round($card['validPaid'] / (float) $invoice->total_amount * 100)) : 0;
        @endphp

        <details class="group overflow-hidden rounded-3xl border bg-white shadow-sm {{ $card['needsAction'] ? 'border-polmind-blue/40 ring-1 ring-blue-100' : 'border-slate-200' }}"
                 @if($startOpen) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-4 px-5 py-4 transition hover:bg-slate-50 sm:px-6 [&::-webkit-details-marker]:hidden">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ match (true) {
                    $status === 'paid' => 'bg-green-100 text-green-700',
                    $status === 'cancelled' => 'bg-red-100 text-red-600',
                    $card['isWaiting'] => 'bg-yellow-100 text-yellow-700',
                    default => 'bg-blue-50 text-polmind-blue',
                } }}">
                    <x-icon :name="match (true) { $status === 'paid' => 'check', $status === 'cancelled' => 'close', $card['isWaiting'] => 'clock', default => 'card' }" class="h-5 w-5" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="text-lg font-black text-polmind-blue">{{ $card['label'] }}</span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-black {{ $badgeClass($card['isWaiting'] ? 'waiting_verification' : $status) }}">
                            {{ $card['isWaiting'] ? 'Menunggu Verifikasi' : $statusLabel }}
                        </span>
                    </span>
                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $invoice->invoice_number }}</span>
                </span>

                <span class="hidden text-right sm:block">
                    <span class="block text-xs text-slate-500">{{ $card['needsAction'] ? ($card['nextDue'] ? 'Tagihan berikutnya' : 'Perlu dibayar') : 'Total' }}</span>
                    <span class="block text-lg font-black text-slate-900">{{ $rupiah($card['needsAction'] ? ($card['nextDue']['amount'] ?? $card['remaining']) : $invoice->total_amount) }}</span>
                </span>

                <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                </svg>
            </summary>

            <div class="space-y-5 border-t border-slate-100 px-5 pb-6 pt-5 sm:px-6">

                {{-- Nominal & progres --}}
                @if($invoice->isWaived())
                    <div class="flex items-start gap-3 rounded-2xl bg-green-50 p-4">
                        <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0 text-green-700" />
                        <div>
                            <p class="text-sm font-black text-slate-800">Formulir Gratis</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">
                                Biaya formulir {{ $rupiah($invoice->total_amount) }} <b>Dibebaskan</b> oleh PMB Polmind. Tidak perlu melakukan pembayaran.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-slate-500">{{ $isRegistration ? 'Biaya formulir' : 'Total tagihan' }}</p>
                                <p class="mt-1 text-2xl font-black text-slate-900">{{ $rupiah($invoice->total_amount) }}</p>
                            </div>

                            @unless($isRegistration)
                                <dl class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                    <div><dt class="inline text-slate-500">Dibayar</dt> <dd class="inline font-bold text-green-700">{{ $rupiah($card['validPaid']) }}</dd></div>
                                    <div><dt class="inline text-slate-500">Sisa</dt> <dd class="inline font-bold text-red-600">{{ $rupiah($card['remaining']) }}</dd></div>
                                    @if($invoice->due_date)
                                        <div><dt class="inline text-slate-500">Jatuh tempo</dt> <dd class="inline font-bold text-slate-800">{{ $invoice->due_date->translatedFormat('d M Y') }}</dd></div>
                                    @endif
                                </dl>
                            @endunless
                        </div>

                        @unless($isRegistration)
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres pembayaran">
                                <div class="h-full rounded-full bg-green-600" style="width: {{ $progress }}%"></div>
                            </div>

                            @if($invoice->items->isNotEmpty())
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-bold text-polmind-blue hover:underline">
                                        Lihat rincian tagihan ({{ $invoice->items->count() }} item)
                                    </summary>
                                    <ul class="mt-2 divide-y divide-slate-200 text-sm">
                                        @foreach($invoice->items as $item)
                                            <li class="flex justify-between gap-3 py-2">
                                                <span class="text-slate-700">{{ $item->name }}@if($item->quantity > 1) <span class="text-slate-400">× {{ $item->quantity }}</span>@endif</span>
                                                <span class="font-bold text-slate-900">{{ $rupiah($item->subtotal) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif

                            @if($invoice->hasInstallments())
                                <div class="mt-4">
                                    <p class="mb-2 text-xs font-bold text-slate-500">Jadwal pembayaran</p>
                                    @include('camaba.partials.installment-schedule', ['invoice' => $invoice])
                                </div>
                            @endif
                        @endunless
                    </div>
                @endif

                {{-- Status / form pembayaran --}}
                @if($status === 'paid' && ! $invoice->isWaived())
                    <p class="flex items-center gap-2 text-sm font-bold text-green-700">
                        <x-icon name="check" class="h-5 w-5" /> Tagihan Lunas · pembayaran sudah tervalidasi admin PMB.
                    </p>
                @elseif($status === 'cancelled')
                    <p class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">Tagihan ini dibatalkan.</p>
                @elseif($card['isWaiting'])
                    <div class="flex items-start gap-3 rounded-2xl border border-yellow-200 bg-yellow-50 p-4">
                        <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0 text-yellow-700" />
                        <div>
                            <p class="text-sm font-black text-slate-800">Bukti pembayaran {{ $rupiah($card['waitingPaid']) }} sedang diverifikasi</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">Admin PMB memeriksa maksimal 1x24 jam kerja. Anda tidak perlu mengunggah ulang.</p>
                        </div>
                    </div>
                @elseif($card['noAmount'])
                    <p class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                        Nominal tagihan ini belum ditetapkan. Silakan hubungi admin PMB melalui WhatsApp {{ \App\Services\AdminWhatsApp::display() }} sebelum melakukan pembayaran.
                    </p>
                @elseif($card['needsAction'])
                    @if($card['lastRejected'])
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700">
                            <p class="font-black">Pembayaran sebelumnya ditolak</p>
                            <p class="mt-1">{{ $card['lastRejected']->admin_note ?: 'Silakan periksa kembali bukti transfer Anda dan unggah ulang.' }}</p>
                        </div>
                    @endif

                    <form action="{{ route('camaba.payments.upload-proof', $invoice->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                        {{-- Langkah 1 --}}
                        <fieldset>
                            <legend class="flex items-center gap-2 text-base font-black text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">1</span>
                                Transfer {{ $rupiah($card['remaining']) }} ke salah satu rekening
                            </legend>

                            @if($paymentMethods->isEmpty())
                                <p class="mt-3 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">
                                    Informasi rekening pembayaran belum tersedia. Silakan hubungi admin PMB melalui WhatsApp {{ \App\Services\AdminWhatsApp::display() }}.
                                </p>
                            @else
                                <p class="mt-1 pl-9 text-xs text-slate-500">Pilih rekening yang Anda gunakan untuk transfer.</p>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    @foreach($paymentMethods as $method)
                                        <label x-data="{ copied: false }"
                                               class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-300 bg-white p-4 transition has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                                            <input type="radio" name="payment_method_id" value="{{ $method->id }}" required class="mt-1 accent-polmind-blue"
                                                   @checked((string) old('payment_method_id', $paymentMethods->count() === 1 ? $method->id : '') === (string) $method->id)>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $method->type_label }}</span>
                                                <span class="block font-black text-slate-900">{{ $method->provider }}</span>
                                                @if($method->account_number)
                                                    <span class="mt-1 flex items-center justify-between gap-2">
                                                        <span class="font-mono text-base font-black tracking-wider text-polmind-blue">{{ $method->account_number }}</span>
                                                        <button type="button"
                                                                @click.prevent="navigator.clipboard.writeText(@js($method->account_number)); copied = true; setTimeout(() => copied = false, 2000)"
                                                                class="shrink-0 rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100"
                                                                x-text="copied ? '✓ Tersalin' : 'Salin'">Salin</button>
                                                    </span>
                                                @endif
                                                @if($method->account_name)
                                                    <span class="mt-1 block text-xs text-slate-600">a.n. <b>{{ $method->account_name }}</b></span>
                                                @endif
                                                @if($method->instructions)
                                                    <span class="mt-2 block whitespace-pre-line text-xs leading-5 text-slate-500">{{ $method->instructions }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </fieldset>

                        {{-- Langkah 2 --}}
                        <fieldset>
                            <legend class="flex items-center gap-2 text-base font-black text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">2</span>
                                Isi data transfer & unggah bukti
                            </legend>

                            <div class="mt-3 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="sender_name_{{ $invoice->id }}" class="text-sm font-bold text-slate-700">Nama Pengirim</label>
                                    <input id="sender_name_{{ $invoice->id }}" type="text" name="sender_name" required
                                           value="{{ $hasFailedUpload ? old('sender_name') : '' }}"
                                           placeholder="Sesuai nama di rekening/e-wallet"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div>
                                    <label for="sender_bank_{{ $invoice->id }}" class="text-sm font-bold text-slate-700">Bank / E-Wallet Pengirim</label>
                                    <input id="sender_bank_{{ $invoice->id }}" type="text" name="sender_bank" required
                                           value="{{ $hasFailedUpload ? old('sender_bank') : '' }}"
                                           placeholder="Contoh: BRI, DANA"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div>
                                    <label for="transfer_date_{{ $invoice->id }}" class="text-sm font-bold text-slate-700">Tanggal Transfer</label>
                                    <input id="transfer_date_{{ $invoice->id }}" type="date" name="transfer_date" required
                                           max="{{ now()->format('Y-m-d') }}"
                                           value="{{ $hasFailedUpload ? old('transfer_date', now()->format('Y-m-d')) : now()->format('Y-m-d') }}"
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div>
                                    <label for="amount_{{ $invoice->id }}" class="text-sm font-bold text-slate-700">Nominal Bayar</label>
                                    @php
                                        $nextDue = $card['nextDue'];
                                        $suggestedAmount = $nextDue ? min($nextDue['amount'], $card['remaining']) : $card['remaining'];
                                    @endphp
                                    <input id="amount_{{ $invoice->id }}" type="number" name="amount" required
                                           min="1"
                                           max="{{ $card['remaining'] }}"
                                           value="{{ ! $nextDue || ! $hasFailedUpload ? $suggestedAmount : old('amount', $suggestedAmount) }}"
                                           @readonly(! $nextDue)
                                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none read-only:bg-slate-50 focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                    <p class="mt-1.5 text-xs text-slate-500">
                                        @if($isRegistration)
                                            Sesuai biaya formulir.
                                        @elseif($nextDue)
                                            Tagihan berikutnya {{ $rupiah($nextDue['amount']) }}, jatuh tempo {{ $nextDue['due_date']->translatedFormat('d M Y') }}.
                                        @else
                                            Sesuai sisa tagihan daftar ulang.
                                        @endif
                                    </p>
                                </div>

                                <div class="md:col-span-2" x-data="{ fileName: '' }">
                                    <span class="text-sm font-bold text-slate-700">Bukti Pembayaran</span>
                                    <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-4 transition hover:border-polmind-blue hover:bg-blue-50 has-focus-visible:border-polmind-blue">
                                        <x-icon name="upload" class="h-6 w-6 shrink-0 text-polmind-blue" />
                                        <span class="min-w-0 text-sm">
                                            <span class="block truncate font-bold text-slate-800" x-text="fileName || 'Pilih foto atau PDF bukti transfer'">Pilih foto atau PDF bukti transfer</span>
                                            <span class="block text-xs text-slate-500">JPG, PNG, atau PDF · maks. 4 MB</span>
                                        </span>
                                        <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="sr-only"
                                               @change="fileName = $event.target.files[0]?.name ?? ''">
                                    </label>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs leading-5 text-slate-500">Setelah dikirim, admin PMB memverifikasi maksimal 1x24 jam kerja.</p>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                                Kirim Bukti Pembayaran
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Aksi sekunder: cetak invoice & riwayat --}}
                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4">
                    @if($status !== 'cancelled')
                        <a href="{{ route('camaba.print.invoice', $invoice) }}" target="_blank" rel="noopener"
                           class="inline-flex w-fit items-center gap-2 text-sm font-bold text-polmind-blue hover:underline">
                            <x-icon name="document-text" class="h-4 w-4" />
                            Cetak Invoice
                        </a>
                    @endif

                    <details>
                        <summary class="cursor-pointer text-sm font-bold text-slate-700 hover:text-polmind-blue">
                            Riwayat pembayaran ({{ $paymentHistories->count() }} transaksi)
                        </summary>

                        @if($paymentHistories->isEmpty())
                            <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada pembayaran untuk invoice ini.</p>
                        @else
                            <ul class="mt-3 space-y-2">
                                @foreach($paymentHistories as $payment)
                                    @php
                                        $historyStatus = $payment->status ?? 'waiting_verification';
                                    @endphp
                                    <li class="rounded-2xl border border-slate-200 p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-black {{ $historyStatus === 'rejected' ? 'text-red-600' : 'text-slate-900' }}">{{ $rupiah($payment->amount) }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    {{ $payment->transfer_date?->translatedFormat('d M Y') ?? '-' }}
                                                    · {{ $payment->sender_name ?? '-' }} ({{ $payment->sender_bank ?? '-' }})
                                                    @if($payment->paymentMethod) · ke {{ $payment->paymentMethod->label }} @endif
                                                </p>
                                                <p class="mt-0.5 text-xs text-slate-400">{{ $payment->payment_number }}</p>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-black {{ $badgeClass($historyStatus) }}">
                                                    {{ $paymentStatusLabels[$historyStatus] ?? str_replace('_', ' ', ucfirst($historyStatus)) }}
                                                </span>

                                                @if($historyStatus === 'valid')
                                                    <a href="{{ route('camaba.print.receipt', $payment) }}" target="_blank" rel="noopener"
                                                       class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-black text-green-700 hover:bg-green-200">
                                                        Cetak Kwitansi
                                                    </a>
                                                @endif

                                                @if($payment->proof_file_path)
                                                    <a href="{{ route('camaba.payments.proof', $payment) }}"
                                                       @click.prevent="$store.preview.show($el.href, @js(basename($payment->proof_file_path)))"
                                                       class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-black text-polmind-blue hover:bg-blue-200">
                                                        Lihat Bukti
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        @if($payment->admin_note)
                                            <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-xs leading-5 text-red-700">
                                                <b>Catatan admin:</b> {{ $payment->admin_note }}
                                            </p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </details>
                </div>
            </div>
        </details>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm">
            Belum ada invoice pembayaran.
        </div>
    @endforelse
</div>
@endsection
