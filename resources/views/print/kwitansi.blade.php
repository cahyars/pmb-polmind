@extends('layouts.print')

@section('title', 'Kwitansi ' . $payment->receipt_number)
@section('back_url', route('camaba.payments.index'))

@section('content')
@php
    $applicant = $payment->applicant;
    $invoice = $payment->invoice;
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');

    $paidAmount = (float) $invoice->payments->where('status', 'valid')->sum('amount');
    $remainingAmount = max(0, (float) $invoice->total_amount - $paidAmount);

    $purpose = ($invoice->type === 're_registration' ? 'Pembayaran daftar ulang' : 'Pembayaran formulir pendaftaran')
        . ' (' . $invoice->items->pluck('name')->implode(', ') . ')'
        . ' · Invoice ' . $invoice->invoice_number;

    $rows = [
        'Telah terima dari' => strtoupper($applicant->full_name) . ' (' . $applicant->registration_number . ')',
        'Uang sejumlah' => \App\Services\Terbilang::rupiah($payment->amount),
        'Untuk pembayaran' => $purpose,
        'Tanggal bayar' => $payment->transfer_date?->translatedFormat('j F Y') ?? '-',
        'Metode / tujuan' => $payment->paymentMethod?->label ?? ($payment->sender_bank ?? '-'),
        'Keterangan' => $remainingAmount > 0
            ? 'Cicilan. Sisa tagihan ' . $rupiah($remainingAmount)
            : 'Lunas',
    ];
@endphp

@include('print.partials.letterhead', ['pmbYear' => $applicant->pmbYear])

<div class="mt-6 flex items-start justify-between gap-6">
    <div>
        <h1 class="text-2xl font-black uppercase tracking-wider text-slate-900">Kwitansi</h1>
        <p class="text-sm text-slate-600">No. <span class="font-mono font-bold text-slate-900">{{ $payment->receipt_number }}</span></p>
    </div>
    <span class="-rotate-6 rounded border-4 border-green-600 px-4 py-1 text-xl font-black tracking-widest text-green-700">
        {{ $remainingAmount > 0 ? 'DITERIMA' : 'LUNAS' }}
    </span>
</div>

<table class="mt-6 w-full text-sm">
    <tbody>
        @foreach($rows as $label => $value)
            <tr class="align-top">
                <td class="w-40 border-b border-dotted border-slate-300 py-2.5 pr-2 text-slate-600">{{ $label }}</td>
                <td class="w-3 border-b border-dotted border-slate-300 py-2.5 text-slate-600">:</td>
                <td class="border-b border-dotted border-slate-300 py-2.5 font-semibold {{ $label === 'Uang sejumlah' ? 'italic' : '' }}">{{ $value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-8 flex items-end justify-between gap-8">
    <div class="rounded border-2 border-polmind-blue px-6 py-3">
        <p class="text-xs font-semibold uppercase text-slate-500">Jumlah</p>
        <p class="text-2xl font-black text-polmind-blue">{{ $rupiah($payment->amount) }}</p>
    </div>

    <div class="w-56 text-center text-sm">
        <p class="text-slate-700">Divalidasi {{ ($payment->verified_at ?? now())->translatedFormat('j F Y') }}</p>
        <p class="mt-1 text-slate-700">{{ $signer['title'] }}</p>
        <div class="h-20"></div>
        <p class="min-h-7 border-t border-slate-400 pt-1 font-semibold">{{ $signer['name'] }}</p>
    </div>
</div>

<p class="mt-10 text-xs text-slate-500">
    Kwitansi ini diterbitkan otomatis oleh sistem PMB Polmind untuk pembayaran {{ $payment->payment_number }}
    yang telah divalidasi. Dicetak {{ now()->translatedFormat('j F Y H:i') }}.
</p>
@endsection
