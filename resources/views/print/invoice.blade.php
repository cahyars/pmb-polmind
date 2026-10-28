@extends('layouts.print')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('back_url', route('camaba.payments.index'))

@section('content')
@php
    $applicant = $invoice->applicant;
    $paidAmount = (float) $invoice->payments->where('status', 'valid')->sum('amount');
    $remainingAmount = $invoice->isWaived() ? 0 : max(0, (float) $invoice->total_amount - $paidAmount);
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');

    [$stampLabel, $stampClass] = match (true) {
        $invoice->isWaived() => ['GRATIS', 'border-green-600 text-green-700'],
        $invoice->status === 'paid' => ['LUNAS', 'border-green-600 text-green-700'],
        $invoice->status === 'cancelled' => ['DIBATALKAN', 'border-red-600 text-red-700'],
        $paidAmount > 0 => ['BELUM LUNAS', 'border-yellow-600 text-yellow-700'],
        default => ['BELUM DIBAYAR', 'border-red-600 text-red-700'],
    };
@endphp

@include('print.partials.letterhead', ['pmbYear' => $applicant->pmbYear])

<div class="mt-6 flex items-start justify-between gap-6">
    <div>
        <h1 class="text-2xl font-black uppercase tracking-wider text-slate-900">Invoice</h1>
        <p class="text-sm text-slate-600">{{ $invoice->type === 're_registration' ? 'Tagihan Daftar Ulang' : 'Tagihan Formulir Pendaftaran' }}</p>
    </div>
    <span class="-rotate-6 rounded border-4 px-4 py-1 text-xl font-black tracking-widest {{ $stampClass }}">{{ $stampLabel }}</span>
</div>

<section class="mt-6 grid grid-cols-2 gap-6 text-sm">
    <table>
        <tbody>
            <tr><td class="w-32 py-0.5 text-slate-600">Ditagihkan kepada</td><td class="py-0.5 font-semibold">{{ strtoupper($applicant->full_name) }}</td></tr>
            <tr><td class="py-0.5 text-slate-600">No. Pendaftaran</td><td class="py-0.5 font-semibold">{{ $applicant->registration_number }}</td></tr>
            <tr><td class="py-0.5 text-slate-600">Program Studi</td><td class="py-0.5">{{ $applicant->studyProgram ? $applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name : '-' }}</td></tr>
            <tr><td class="py-0.5 text-slate-600">Kelas</td><td class="py-0.5">{{ $applicant->classType?->name ?? '-' }}</td></tr>
        </tbody>
    </table>
    <table>
        <tbody>
            <tr><td class="w-32 py-0.5 text-slate-600">Nomor Invoice</td><td class="py-0.5 font-semibold">{{ $invoice->invoice_number }}</td></tr>
            <tr><td class="py-0.5 text-slate-600">Tanggal Terbit</td><td class="py-0.5">{{ $invoice->issue_date?->translatedFormat('j F Y') ?? '-' }}</td></tr>
            <tr><td class="py-0.5 text-slate-600">Jatuh Tempo</td><td class="py-0.5">{{ $invoice->due_date?->translatedFormat('j F Y') ?? '-' }}</td></tr>
        </tbody>
    </table>
</section>

<table class="mt-6 w-full border-collapse text-sm">
    <thead>
        <tr class="bg-slate-100 text-left">
            <th class="border border-slate-300 px-3 py-2 font-semibold">No</th>
            <th class="border border-slate-300 px-3 py-2 font-semibold">Uraian</th>
            <th class="border border-slate-300 px-3 py-2 text-right font-semibold">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td class="w-12 border border-slate-300 px-3 py-2">{{ $loop->iteration }}</td>
                <td class="border border-slate-300 px-3 py-2">{{ $item->name }}</td>
                <td class="border border-slate-300 px-3 py-2 text-right">{{ $rupiah($item->subtotal) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="border border-slate-300 px-3 py-2 text-right font-semibold">Total Tagihan</td>
            <td class="border border-slate-300 px-3 py-2 text-right font-black">{{ $rupiah($invoice->total_amount) }}</td>
        </tr>
        @if($invoice->isWaived())
            <tr>
                <td colspan="2" class="border border-slate-300 px-3 py-2 text-right font-semibold text-green-700">Dibebaskan</td>
                <td class="border border-slate-300 px-3 py-2 text-right font-black text-green-700">-{{ $rupiah($invoice->total_amount) }}</td>
            </tr>
        @else
            <tr>
                <td colspan="2" class="border border-slate-300 px-3 py-2 text-right font-semibold">Sudah Dibayar</td>
                <td class="border border-slate-300 px-3 py-2 text-right">{{ $rupiah($paidAmount) }}</td>
            </tr>
        @endif
        <tr class="bg-slate-50">
            <td colspan="2" class="border border-slate-300 px-3 py-2 text-right font-black">Sisa Tagihan</td>
            <td class="border border-slate-300 px-3 py-2 text-right font-black text-polmind-blue">{{ $rupiah($remainingAmount) }}</td>
        </tr>
    </tfoot>
</table>

@if($invoice->isWaived())
    <p class="mt-4 text-sm text-slate-700">Biaya formulir dibebaskan oleh Panitia PMB. Tidak perlu melakukan pembayaran.</p>
@elseif($remainingAmount > 0 && $paymentMethods->isNotEmpty())
    <section class="mt-6 text-sm">
        <h2 class="font-black uppercase tracking-wide text-polmind-blue">Tujuan Pembayaran</h2>
        <ul class="mt-2 grid grid-cols-2 gap-3">
            @foreach($paymentMethods as $method)
                <li class="border border-slate-300 p-3">
                    <p class="font-semibold">{{ $method->provider }}</p>
                    @if($method->account_number)
                        <p class="font-mono font-bold">{{ $method->account_number }}</p>
                    @endif
                    @if($method->account_name)
                        <p class="text-xs text-slate-600">a.n. {{ $method->account_name }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-xs text-slate-600">Setelah transfer, unggah bukti pembayaran melalui menu Pembayaran di dashboard camaba.</p>
    </section>
@endif

<p class="mt-10 text-xs text-slate-500">Dokumen ini dicetak dari sistem PMB Polmind pada {{ now()->translatedFormat('j F Y H:i') }} dan sah tanpa tanda tangan.</p>
@endsection
