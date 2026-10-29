@extends('layouts.admin')

@section('title', 'Rekap Pembayaran')
@section('page_title', 'Rekap Pembayaran')
@section('page_subtitle', 'Laporan · ' . $sections[$section]['description'])

@section('content')
@php
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $rupiah = fn ($value) => 'Rp' . number_format($value, 0, ',', '.');
    $money = fn ($value) => $value > 0 ? number_format($value, 0, ',', '.') : '';
    $totals = $report['totals'];
    $th = 'whitespace-nowrap border border-slate-200 px-3 py-2 font-semibold';
    $td = 'whitespace-nowrap border border-slate-200 px-3 py-2';
@endphp

<div class="space-y-6">
    @include('admin.reports.partials.header')

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.reports.partials.stat', ['label' => 'Total Pemasukan', 'value' => $rupiah($totals['paid']), 'desc' => $number($totals['applicants']) . ' camaba sudah membayar'])
        @foreach(collect($totals['components'])->take(2) as $name => $amount)
            @include('admin.reports.partials.stat', ['label' => $name, 'value' => $rupiah($amount), 'desc' => 'Pemasukan ' . mb_strtolower($name)])
        @endforeach
        @include('admin.reports.partials.stat', ['label' => 'Outstanding', 'value' => $rupiah($totals['outstanding']), 'desc' => 'Sisa tagihan yang sudah terbit'])
    </div>

    @if(count($totals['components']) > 2)
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach(collect($totals['components'])->skip(2) as $name => $amount)
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600">
                    {{ $name }}: <span class="font-semibold text-slate-900">{{ $rupiah($amount) }}</span>
                </span>
            @endforeach
        </div>
    @endif

    <section class="min-w-0 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Pembayaran per Camaba</h2>
            <p class="mt-1 text-sm text-slate-600">
                Pembayaran valid per minggu (minggu dimulai Senin) berdasarkan tanggal bayar. Pembayaran daftar ulang dibagi ke komponen
                mengikuti jadwal cicilan atau urutan komponen di invoice. Outstanding = tagihan yang sudah terbit dikurangi semua pembayaran valid.
            </p>
        </div>

        @if($report['rows']->isEmpty())
            <p class="p-10 text-center text-sm text-slate-500">Belum ada pembayaran valid untuk filter ini.</p>
        @else
            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full border-collapse text-left text-xs">
                    <thead class="sticky top-0 z-10 text-slate-700">
                        <tr class="bg-amber-50">
                            <th colspan="5" class="{{ $th }} bg-slate-50"></th>
                            @foreach($report['months'] as $month)
                                <th colspan="{{ $month['span'] }}" class="{{ $th }} text-center">{{ $month['label'] }}</th>
                            @endforeach
                            <th colspan="{{ $report['components']->count() }}" class="{{ $th }} text-center">Per Komponen</th>
                            <th colspan="2" class="{{ $th }} text-center">Total</th>
                            <th class="{{ $th }} bg-slate-50"></th>
                        </tr>
                        <tr class="bg-slate-100">
                            @foreach(['No', 'Gel.', 'Nama Lengkap', 'Prodi', 'Kelas'] as $header)
                                <th class="{{ $th }}">{{ $header }}</th>
                            @endforeach
                            @foreach($report['weeks'] as $week)
                                <th class="{{ $th }} text-center" title="Minggu ke-{{ $week['week'] }} {{ $week['month_label'] }}">{{ $week['week'] }}</th>
                            @endforeach
                            @foreach($report['components'] as $name)
                                <th class="{{ $th }} text-right">{{ $name }}</th>
                            @endforeach
                            <th class="{{ $th }} text-right">Pembayaran</th>
                            <th class="{{ $th }} text-right">Outstanding</th>
                            <th class="{{ $th }}">Jalur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            @php
                                $applicant = $row['applicant'];
                                $program = $applicant->enrolled_study_program;
                            @endphp
                            <tr class="hover:bg-blue-50/50">
                                <td class="{{ $td }} text-slate-500">{{ $loop->iteration }}</td>
                                <td class="{{ $td }} text-center">{{ preg_replace('/\D/', '', (string) $applicant->admissionWave?->code) ?: '-' }}</td>
                                <td class="{{ $td }}">
                                    <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}" class="font-semibold text-polmind-blue hover:underline">{{ $applicant->full_name }}</a>
                                </td>
                                <td class="{{ $td }}">{{ $program ? $program->degree . ' - ' . $program->name : '-' }}</td>
                                <td class="{{ $td }}">{{ $applicant->classType?->name ?? '-' }}</td>
                                @foreach($report['weeks'] as $week)
                                    <td class="{{ $td }} text-right">{{ $money($row['weekly'][$week['key']]) }}</td>
                                @endforeach
                                @foreach($report['components'] as $name)
                                    <td class="{{ $td }} text-right text-slate-600">{{ $money($row['components'][$name] ?? 0) }}</td>
                                @endforeach
                                <td class="{{ $td }} text-right font-semibold text-slate-900">{{ $number($row['paid']) }}</td>
                                <td class="{{ $td }} text-right {{ $row['outstanding'] > 0 ? 'font-semibold text-red-600' : 'text-slate-400' }}">{{ $number($row['outstanding']) }}</td>
                                <td class="{{ $td }}">{{ $applicant->registration_path_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="sticky bottom-0 bg-amber-50 font-bold text-slate-900">
                        <tr>
                            <td colspan="5" class="{{ $td }}">TOTAL</td>
                            @foreach($report['weeks'] as $week)
                                <td class="{{ $td }} text-right">{{ $money($totals['weekly'][$week['key']]) }}</td>
                            @endforeach
                            @foreach($report['components'] as $name)
                                <td class="{{ $td }} text-right">{{ $number($totals['components'][$name] ?? 0) }}</td>
                            @endforeach
                            <td class="{{ $td }} text-right">{{ $number($totals['paid']) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($totals['outstanding']) }}</td>
                            <td class="{{ $td }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
