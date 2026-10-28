@extends('layouts.admin')

@section('title', 'Laporan Daftar Ulang')
@section('page_title', 'Laporan Daftar Ulang')
@section('page_subtitle', 'Laporan · ' . $sections[$section]['description'])

@section('content')
@php
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $rupiah = fn ($value) => 'Rp' . number_format($value, 0, ',', '.');
    $summary = $report['summary'];
    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
    $th = 'whitespace-nowrap px-4 py-3 font-medium';
    $td = 'px-4 py-2.5';

    $statusBadge = [
        'lunas' => 'bg-green-100 text-green-700',
        'cicilan' => 'bg-yellow-100 text-yellow-800',
        'belum_bayar' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="space-y-6">
    @include('admin.reports.partials.header')

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.reports.partials.stat', ['label' => 'Camaba Diterima', 'value' => $number($summary['accepted']), 'desc' => 'Lolos seleksi'])
        @include('admin.reports.partials.stat', [
            'label' => 'Sudah Daftar Ulang',
            'value' => $number($summary['re_registered']),
            'desc' => $number($summary['paid_off']) . ' lunas · ' . $number($summary['installment']) . ' cicilan · ' . $number($summary['unpaid']) . ' belum bayar',
        ])
        @include('admin.reports.partials.stat', ['label' => 'NIM Terbit', 'value' => $number($summary['nim']), 'desc' => $number($summary['synced']) . ' sudah sinkron SIAKAD'])
        @include('admin.reports.partials.stat', ['label' => 'Dana Daftar Ulang Masuk', 'value' => $rupiah($summary['total_paid']), 'desc' => 'Sisa tagihan ' . $rupiah($summary['total_remaining'])])
    </div>

    {{-- Per prodi --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Daftar Ulang per Program Studi</h2>
        <p class="mt-1 text-sm text-slate-600">Status pembayaran daftar ulang camaba diterima. Keterisian kuota dihitung dari yang sudah daftar ulang.</p>

        <div class="mt-5">
            @include('admin.reports.partials.chart', [
                'label' => 'Status daftar ulang per program studi',
                'config' => [
                    'type' => 'bar',
                    'stacked' => true,
                    'labels' => $report['programs']->pluck('code'),
                    'datasets' => [
                        ['label' => 'Lunas', 'data' => $report['programs']->pluck('paid_off'), 'status' => 'good'],
                        ['label' => 'Cicilan', 'data' => $report['programs']->pluck('installment'), 'status' => 'warning'],
                        ['label' => 'Belum bayar', 'data' => $report['programs']->pluck('unpaid'), 'status' => 'neutral'],
                    ],
                    'unit' => 'camaba',
                ],
            ])
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400">
                    <tr>
                        <th class="{{ $th }}">Prodi</th>
                        <th class="{{ $th }} text-right">Diterima</th>
                        <th class="{{ $th }} text-right">Lunas</th>
                        <th class="{{ $th }} text-right">Cicilan</th>
                        <th class="{{ $th }} text-right">Belum Bayar</th>
                        <th class="{{ $th }} text-right">NIM</th>
                        <th class="{{ $th }} text-right">Dana Masuk</th>
                        <th class="{{ $th }}">Kuota Terisi</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @foreach($report['programs'] as $program)
                        <tr>
                            <td class="{{ $td }}"><span class="font-semibold text-slate-900">{{ $program['code'] }}</span> <span class="text-xs text-slate-500">{{ $program['name'] }}</span></td>
                            <td class="{{ $td }} text-right">{{ $number($program['accepted']) }}</td>
                            <td class="{{ $td }} text-right font-semibold text-slate-900">{{ $number($program['paid_off']) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($program['installment']) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($program['unpaid']) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($program['nim']) }}</td>
                            <td class="{{ $td }} text-right">{{ $rupiah($program['total_paid']) }}</td>
                            <td class="{{ $td }} w-56">
                                @if($program['quota_percent'] !== null)
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-[#2a78d6]" style="width: {{ min($program['quota_percent'], 100) }}%"></div>
                                        </div>
                                        <span class="w-24 shrink-0 text-right text-xs text-slate-600">{{ $program['re_registered'] }}/{{ $program['quota'] }} · {{ $program['quota_percent'] }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">Kuota belum diatur</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Tren --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Daftar Ulang per Bulan</h2>
        <p class="mt-1 text-sm text-slate-600">Berdasarkan tanggal pembayaran daftar ulang pertama setiap camaba.</p>
        <div class="mt-5">
            @include('admin.reports.partials.chart', [
                'label' => 'Jumlah camaba daftar ulang per bulan',
                'height' => 'h-60',
                'config' => [
                    'type' => 'bar',
                    'labels' => $report['monthly']->keys(),
                    'datasets' => [['label' => 'Camaba daftar ulang', 'data' => $report['monthly']->values()]],
                    'unit' => 'camaba',
                ],
            ])
        </div>
    </section>

    {{-- Daftar --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Daftar Camaba Diterima</h2>
        <p class="mt-1 text-sm text-slate-600">Diurutkan per prodi dan tanggal daftar ulang, sama dengan urutan pembubuhan NIM.</p>

        <div class="mt-5 max-h-[560px] overflow-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="sticky top-0 border-b border-slate-200 bg-white text-xs text-slate-400">
                    <tr>
                        <th class="{{ $th }}">No</th>
                        <th class="{{ $th }}">Camaba</th>
                        <th class="{{ $th }}">Prodi</th>
                        <th class="{{ $th }}">NIM</th>
                        <th class="{{ $th }}">Tanggal DU</th>
                        <th class="{{ $th }} text-right">Tagihan</th>
                        <th class="{{ $th }} text-right">Dibayar</th>
                        <th class="{{ $th }} text-right">Sisa</th>
                        <th class="{{ $th }}">Status</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($report['rows'] as $row)
                        <tr>
                            <td class="{{ $td }} text-slate-500">{{ $loop->iteration }}</td>
                            <td class="{{ $td }}">
                                <a href="{{ route('admin.applicants.show', $row['applicant']->registration_number) }}" class="font-semibold text-slate-900 hover:text-polmind-blue">{{ $row['applicant']->full_name }}</a>
                                <p class="text-xs text-slate-500">{{ $row['applicant']->registration_number }}</p>
                            </td>
                            <td class="{{ $td }}">{{ $row['applicant']->studyProgram?->code ?? '-' }}</td>
                            <td class="{{ $td }} font-mono font-semibold text-slate-900">{{ $row['applicant']->nim ?? '-' }}</td>
                            <td class="{{ $td }} text-slate-600">{{ $row['re_registered_at']?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="{{ $td }} text-right">{{ $rupiah($row['total']) }}</td>
                            <td class="{{ $td }} text-right font-semibold text-slate-900">{{ $rupiah($row['paid']) }}</td>
                            <td class="{{ $td }} text-right">{{ $rupiah($row['remaining']) }}</td>
                            <td class="{{ $td }}">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusBadge[$row['status']] }}">
                                    {{ \App\Services\Reports\ReRegistrationReport::STATUSES[$row['status']] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada camaba diterima.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/report-charts.js')
@endpush
