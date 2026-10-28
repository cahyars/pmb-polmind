@extends('layouts.admin')

@section('title', 'Laporan Seleksi')
@section('page_title', 'Laporan Seleksi')
@section('page_subtitle', 'Laporan · ' . $sections[$section]['description'])

@section('content')
@php
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $summary = $report['summary'];
    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
    $th = 'whitespace-nowrap px-4 py-3 font-medium';
    $td = 'px-4 py-2.5';

    $statusBadge = [
        'diterima' => 'bg-green-100 text-green-700',
        'cadangan' => 'bg-yellow-100 text-yellow-800',
        'ditolak' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="space-y-6">
    @include('admin.reports.partials.header')

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @include('admin.reports.partials.stat', ['label' => 'Siap Seleksi', 'value' => $number($summary['ready']), 'desc' => 'Berkas & formulir valid'])
        @include('admin.reports.partials.stat', ['label' => 'Menunggu Keputusan', 'value' => $number($summary['waiting']), 'desc' => 'Siap tetapi belum diputuskan'])
        @include('admin.reports.partials.stat', ['label' => 'Diterima', 'value' => $number($summary['accepted']), 'desc' => $summary['ready'] > 0 ? round($summary['accepted'] / $summary['ready'] * 100) . '% dari yang siap seleksi' : '-'])
        @include('admin.reports.partials.stat', ['label' => 'Cadangan', 'value' => $number($summary['reserve'])])
        @include('admin.reports.partials.stat', ['label' => 'Ditolak', 'value' => $number($summary['rejected'])])
    </div>

    {{-- Per prodi --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Hasil Seleksi per Program Studi</h2>
        <p class="mt-1 text-sm text-slate-600">Berdasarkan prodi pilihan 1. Rasio diterima dihitung dari camaba yang siap seleksi.</p>

        <div class="mt-5">
            @include('admin.reports.partials.chart', [
                'label' => 'Hasil seleksi per program studi',
                'config' => [
                    'type' => 'bar',
                    'stacked' => true,
                    'labels' => $report['programs']->pluck('code'),
                    'datasets' => [
                        ['label' => 'Diterima', 'data' => $report['programs']->pluck('accepted'), 'status' => 'good'],
                        ['label' => 'Cadangan', 'data' => $report['programs']->pluck('reserve'), 'status' => 'warning'],
                        ['label' => 'Ditolak', 'data' => $report['programs']->pluck('rejected'), 'status' => 'critical'],
                        ['label' => 'Menunggu keputusan', 'data' => $report['programs']->pluck('waiting'), 'status' => 'neutral'],
                    ],
                    'unit' => 'camaba',
                ],
            ])
        </div>

        @include('admin.reports.partials.selection-table', ['title' => 'Program Studi', 'rows' => $report['programs'], 'name' => fn ($row) => $row['code'] . ' · ' . $row['name']])
    </section>

    <div class="grid gap-6 2xl:grid-cols-2">
        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Per Jalur Pendaftaran</h2>
            @include('admin.reports.partials.selection-table', ['title' => 'Jalur', 'rows' => $report['paths'], 'name' => fn ($row) => $row['label'], 'compact' => true])
        </section>

        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Per Gelombang</h2>
            @include('admin.reports.partials.selection-table', ['title' => 'Gelombang', 'rows' => $report['waves'], 'name' => fn ($row) => $row['label'], 'compact' => true])
        </section>
    </div>

    {{-- Sebaran nilai --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Sebaran Nilai Akhir</h2>
        <p class="mt-1 text-sm text-slate-600">Nilai akhir = 60% nilai tes + 40% nilai wawancara, dari camaba yang sudah dinilai.</p>
        <div class="mt-5">
            @include('admin.reports.partials.chart', [
                'label' => 'Sebaran nilai akhir seleksi',
                'height' => 'h-60',
                'config' => [
                    'type' => 'bar',
                    'labels' => $report['scores']->keys(),
                    'datasets' => [['label' => 'Camaba', 'data' => $report['scores']->values()]],
                    'unit' => 'camaba',
                ],
            ])
        </div>
    </section>

    {{-- Daftar hasil --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Daftar Hasil Seleksi</h2>
        <p class="mt-1 text-sm text-slate-600">{{ $number($report['results']->count()) }} camaba sudah diputuskan, diurutkan per prodi dan nilai akhir tertinggi.</p>

        <div class="mt-5 max-h-[560px] overflow-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="sticky top-0 border-b border-slate-200 bg-white text-xs text-slate-400">
                    <tr>
                        <th class="{{ $th }}">No</th>
                        <th class="{{ $th }}">Camaba</th>
                        <th class="{{ $th }}">Prodi</th>
                        <th class="{{ $th }}">Jalur</th>
                        <th class="{{ $th }} text-right">Tes</th>
                        <th class="{{ $th }} text-right">Wawancara</th>
                        <th class="{{ $th }} text-right">Nilai Akhir</th>
                        <th class="{{ $th }}">Status</th>
                        <th class="{{ $th }}">Diputuskan</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($report['results'] as $applicant)
                        <tr>
                            <td class="{{ $td }} text-slate-500">{{ $loop->iteration }}</td>
                            <td class="{{ $td }}">
                                <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}" class="font-semibold text-slate-900 hover:text-polmind-blue">{{ $applicant->full_name }}</a>
                                <p class="text-xs text-slate-500">{{ $applicant->registration_number }}</p>
                            </td>
                            <td class="{{ $td }}">{{ $applicant->studyProgram?->code ?? '-' }}</td>
                            <td class="{{ $td }}">{{ $applicant->registration_path_label }}</td>
                            <td class="{{ $td }} text-right">{{ $applicant->selection?->test_score ?? '-' }}</td>
                            <td class="{{ $td }} text-right">{{ $applicant->selection?->interview_score ?? '-' }}</td>
                            <td class="{{ $td }} text-right font-semibold text-slate-900">{{ $applicant->selection?->final_score ?? '-' }}</td>
                            <td class="{{ $td }}">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusBadge[$applicant->selection_status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ \App\Services\Reports\SelectionReport::STATUSES[$applicant->selection_status] ?? $applicant->selection_status }}
                                </span>
                            </td>
                            <td class="{{ $td }} text-slate-600">{{ $applicant->selection?->decided_at?->translatedFormat('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada keputusan seleksi.</td></tr>
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
