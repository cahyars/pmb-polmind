@extends('layouts.admin')

@section('title', 'Laporan Sumber Informasi')
@section('page_title', 'Laporan Sumber Informasi')
@section('page_subtitle', 'Laporan · ' . $sections[$section]['description'])

@section('content')
@php
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $percent = fn ($value) => str_replace('.', ',', (string) $value) . '%';
    $total = $report['total'];
    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
    $th = 'whitespace-nowrap px-4 py-3 font-medium';
    $td = 'px-4 py-2.5';
    $chartSources = $report['sources']->where('total', '>', 0);

    // Angka di tabel membuka Data Camaba dengan filter yang sama (yang didukung halaman itu).
    $applicantsUrl = fn (string $source, ?string $summary = null) => route('admin.applicants.index', array_filter([
        'source' => $source,
        'summary' => $summary,
        'wave' => $filters['admission_wave'] ?? null,
        'study_program' => $filters['study_program'] ?? null,
        'registration_path' => $filters['registration_path'] ?? null,
    ])) . '#daftar-camaba';
    $link = 'font-semibold text-polmind-blue hover:underline';

    $narrative = "Sumber informasi pendaftar PMB Politeknik Mitra Industri " . ($selectedPmbYear?->name ?? '') . ":\n"
        . $chartSources->map(fn ($source) => '- ' . $source['label'] . ': ' . $source['total'] . ' pendaftar, ' . $source['paid'] . ' lunas formulir (' . $percent($source['paid_rate']) . ')')->implode("\n");
@endphp

<div class="space-y-6">
    @include('admin.reports.partials.header')

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.reports.partials.stat', ['label' => 'Total Pendaftar', 'value' => $number($total), 'desc' => 'Sesuai filter di atas'])
        @include('admin.reports.partials.stat', [
            'label' => 'Mengisi Sumber Informasi',
            'value' => $number($report['filled']),
            'desc' => ($total > 0 ? round($report['filled'] / $total * 100) : 0) . '% dari pendaftar',
        ])
        @include('admin.reports.partials.stat', [
            'label' => 'Sumber Terbanyak',
            'value' => $report['top']['label'] ?? '-',
            'desc' => $report['top'] ? $number($report['top']['total']) . ' pendaftar (' . $percent($report['top']['share']) . ')' : 'Belum ada data',
        ])
        @include('admin.reports.partials.stat', [
            'label' => 'Konversi Terbaik',
            'value' => $report['bestConversion']['label'] ?? '-',
            'desc' => $report['bestConversion']
                ? $percent($report['bestConversion']['paid_rate']) . ' lunas formulir'
                : 'Butuh minimal 5 pendaftar per sumber',
        ])
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="{{ $card }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Pendaftar per Sumber</h2>
                    <p class="mt-1 text-sm text-slate-600">Jumlah pendaftar dan yang sudah lunas formulir.</p>
                </div>
                <button type="button"
                        onclick="navigator.clipboard.writeText(@js($narrative)); Swal.fire({title: 'Ringkasan disalin', text: 'Teks ringkasan siap ditempel ke WhatsApp.', icon: 'success', confirmButtonColor: '#003B82'})"
                        class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-polmind-blue hover:text-polmind-blue">
                    Salin Ringkasan
                </button>
            </div>
            <div class="mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Pendaftar dan formulir lunas per sumber informasi',
                    'height' => $chartSources->count() > 8 ? 'h-96' : 'h-72',
                    'config' => [
                        'type' => 'bar',
                        'horizontal' => true,
                        'labels' => $chartSources->pluck('label')->values(),
                        'datasets' => [
                            ['label' => 'Pendaftar', 'data' => $chartSources->pluck('total')->values()],
                            ['label' => 'Formulir lunas', 'data' => $chartSources->pluck('paid')->values()],
                        ],
                        'unit' => 'camaba',
                    ],
                ])
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Tren Pendaftar per Bulan</h2>
            <p class="mt-1 text-sm text-slate-600">Tiga sumber teratas, sisanya digabung "Lainnya".</p>
            <div class="mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Tren pendaftar per bulan menurut sumber informasi',
                    'height' => $chartSources->count() > 8 ? 'h-96' : 'h-72',
                    'config' => [
                        'type' => 'line',
                        'labels' => $report['trend']['labels'],
                        'datasets' => $report['trend']['datasets'],
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
        </section>
    </div>

    {{-- Konversi per sumber --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Konversi per Sumber Informasi</h2>
                <p class="mt-1 text-sm text-slate-600">Persentase dihitung dari pendaftar sumber itu sendiri. Klik angka untuk membuka daftar camabanya.</p>
            </div>
            <a href="{{ route('admin.information-sources.index') }}" class="shrink-0 text-sm font-semibold text-polmind-blue hover:underline">Kelola pilihan sumber</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400">
                    <tr>
                        <th class="{{ $th }}">Sumber Informasi</th>
                        <th class="{{ $th }} text-right">Pendaftar</th>
                        <th class="{{ $th }} text-right">Porsi</th>
                        <th class="{{ $th }} text-right">Belum Bayar</th>
                        <th class="{{ $th }} text-right">Formulir Lunas</th>
                        <th class="{{ $th }} text-right">Biodata Lengkap</th>
                        <th class="{{ $th }} text-right">Diterima</th>
                        <th class="{{ $th }} text-right">Daftar Ulang</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($report['sources'] as $source)
                        <tr>
                            <td class="{{ $td }}">
                                <span class="font-semibold text-slate-900">{{ $source['label'] }}</span>
                                @unless($source['is_active'])
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">nonaktif</span>
                                @endunless
                            </td>
                            <td class="{{ $td }} text-right">
                                @if($source['total'] > 0)
                                    <a href="{{ $applicantsUrl($source['key']) }}" class="{{ $link }}">{{ $number($source['total']) }}</a>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="{{ $td }} text-right text-slate-600">{{ $percent($source['share']) }}</td>
                            <td class="{{ $td }} text-right">
                                @if($source['total'] - $source['paid'] > 0)
                                    <a href="{{ $applicantsUrl($source['key'], 'belum_bayar') }}" class="{{ $link }}">{{ $number($source['total'] - $source['paid']) }}</a>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="{{ $td }} text-right">
                                <span class="font-semibold text-slate-900">{{ $number($source['paid']) }}</span>
                                <span class="ml-1 text-xs text-slate-500">{{ $percent($source['paid_rate']) }}</span>
                            </td>
                            <td class="{{ $td }} text-right text-slate-700">{{ $number($source['biodata']) }}</td>
                            <td class="{{ $td }} text-right">
                                <span class="font-semibold text-slate-900">{{ $number($source['accepted']) }}</span>
                                <span class="ml-1 text-xs text-slate-500">{{ $percent($source['accepted_rate']) }}</span>
                            </td>
                            <td class="{{ $td }} text-right">
                                <span class="font-semibold text-slate-900">{{ $number($source['re_registered']) }}</span>
                                <span class="ml-1 text-xs text-slate-500">{{ $percent($source['re_registered_rate']) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm font-medium text-slate-500">Belum ada sumber informasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Sumber × prodi --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Sumber Informasi per Program Studi</h2>
            <p class="mt-1 text-sm text-slate-600">Pendaftar menurut prodi pilihan 1, untuk melihat kanal yang paling efektif per prodi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400">
                    <tr>
                        <th class="{{ $th }}">Sumber Informasi</th>
                        @foreach($report['programs']['columns'] as $program)
                            <th class="{{ $th }} text-right" title="{{ $program->degree }} {{ $program->name }}">{{ $program->code }}</th>
                        @endforeach
                        <th class="{{ $th }} text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($report['programs']['rows'] as $row)
                        <tr>
                            <td class="{{ $td }} whitespace-nowrap font-semibold text-slate-900">{{ $row['label'] }}</td>
                            @foreach($report['programs']['columns'] as $program)
                                <td class="{{ $td }} text-right {{ $row['counts'][$program->id] > 0 ? 'text-slate-700' : 'text-slate-300' }}">{{ $number($row['counts'][$program->id]) }}</td>
                            @endforeach
                            <td class="{{ $td }} text-right font-semibold text-slate-900">{{ $number($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $report['programs']['columns']->count() + 2 }}" class="px-6 py-10 text-center text-sm font-medium text-slate-500">Belum ada pendaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
