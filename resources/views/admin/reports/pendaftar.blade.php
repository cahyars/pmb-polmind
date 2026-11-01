@extends('layouts.admin')

@section('title', 'Laporan Pendaftar')
@section('page_title', 'Laporan Pendaftar')
@section('page_subtitle', 'Laporan · ' . $sections[$section]['description'])

@section('content')
@php
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $funnel = $report['funnel']->keyBy('label');
    $total = $report['total'];
    $percent = fn ($value) => $total > 0 ? round($value / $total * 100) : 0;
    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
    $th = 'whitespace-nowrap px-4 py-3 font-medium';
    $td = 'px-4 py-2.5';

    $narrative = "Update PMB Politeknik Mitra Industri " . ($selectedPmbYear?->name ?? '') . ":\n"
        . $report['funnel']->map(fn ($item) => '- ' . $item['label'] . ': ' . $item['value'] . ' camaba')->implode("\n")
        . "\n\nPendaftar per prodi (pilihan 1):\n"
        . $report['programs']->map(fn ($program) => '- ' . $program['code'] . ': ' . $program['first'])->implode("\n");
@endphp

<div class="space-y-6">
    @include('admin.reports.partials.header')

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.reports.partials.stat', ['label' => 'Total Pendaftar', 'value' => $number($total), 'desc' => 'Akun yang terdaftar'])
        @include('admin.reports.partials.stat', ['label' => 'Formulir Lunas', 'value' => $number($funnel['Formulir Lunas']['value']), 'desc' => $percent($funnel['Formulir Lunas']['value']) . '% dari pendaftar'])
        @include('admin.reports.partials.stat', ['label' => 'Biodata Lengkap', 'value' => $number($funnel['Biodata Lengkap']['value']), 'desc' => $percent($funnel['Biodata Lengkap']['value']) . '% dari pendaftar'])
        @include('admin.reports.partials.stat', ['label' => 'Diterima', 'value' => $number($funnel['Diterima']['value']), 'desc' => $percent($funnel['Diterima']['value']) . '% dari pendaftar'])
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Funnel --}}
        <section class="{{ $card }} xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Tahapan Pendaftar</h2>
                    <p class="mt-1 text-sm text-slate-600">Jumlah camaba yang sudah mencapai setiap tahapan.</p>
                </div>
                <button type="button"
                        onclick="navigator.clipboard.writeText(@js($narrative)); Swal.fire({title: 'Ringkasan disalin', text: 'Teks ringkasan siap ditempel ke WhatsApp pimpinan.', icon: 'success', confirmButtonColor: '#003B82'})"
                        class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-polmind-blue hover:text-polmind-blue">
                    Salin Ringkasan
                </button>
            </div>
            @php
                // Funnel horizontal: tiap tahap trapesium dari tingginya sendiri ke tinggi tahap berikutnya.
                $stages = $report['funnel']->values();
                $stageCount = $stages->count();
                $funnelMax = max($stages->max('value'), 1);
                $funnelColors = ['#104281', '#1c5cab', '#2a78d6', '#5598e7', '#6da7ec', '#86b6ef'];
                [$svgWidth, $svgHeight, $gap] = [600, 220, 3];
                $columnWidth = $svgWidth / max($stageCount, 1);
                $stageHeight = fn ($value) => max($value / $funnelMax * $svgHeight, $svgHeight * 0.02);
            @endphp
            <div class="mt-5 overflow-x-auto">
                <div class="min-w-[560px]">
                    <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" preserveAspectRatio="none" class="block h-56 w-full" role="img" aria-label="Funnel tahapan pendaftar">
                        @foreach($stages as $i => $item)
                            @php
                                $left = $stageHeight($item['value']);
                                $right = $stageHeight($stages[$i + 1]['value'] ?? $item['value']);
                                $x0 = $i * $columnWidth + ($i > 0 ? $gap / 2 : 0);
                                $x1 = ($i + 1) * $columnWidth - ($i < $stageCount - 1 ? $gap / 2 : 0);
                                $mid = $svgHeight / 2;
                            @endphp
                            <polygon points="{{ $x0 }},{{ $mid - $left / 2 }} {{ $x1 }},{{ $mid - $right / 2 }} {{ $x1 }},{{ $mid + $right / 2 }} {{ $x0 }},{{ $mid + $left / 2 }}"
                                     fill="{{ $funnelColors[$i % count($funnelColors)] }}" class="transition-opacity hover:opacity-80">
                                <title>{{ $item['label'] }}: {{ $number($item['value']) }} camaba ({{ $item['percent'] }}%)</title>
                            </polygon>
                        @endforeach
                    </svg>
                    <ol class="mt-4 grid gap-3" style="grid-template-columns: repeat({{ max($stageCount, 1) }}, minmax(0, 1fr))">
                        @foreach($stages as $i => $item)
                            @php
                                $previous = $i > 0 ? $stages[$i - 1]['value'] : null;
                            @endphp
                            <li class="min-w-0">
                                <p class="flex min-h-[2rem] items-start gap-1.5 text-xs font-medium leading-tight text-slate-600">
                                    <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $funnelColors[$i % count($funnelColors)] }}"></span>{{ $item['label'] }}
                                </p>
                                <p class="mt-1.5 text-lg font-semibold text-slate-900">{{ $number($item['value']) }}</p>
                                <p class="text-xs text-slate-500">{{ $item['percent'] }}% dari pendaftar</p>
                                @if($previous !== null)
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $previous > 0 ? round($item['value'] / $previous * 100) : 0 }}% dari tahap sebelumnya</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        {{-- Gender --}}
        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Jenis Kelamin</h2>
            <p class="mt-1 text-sm text-slate-600">Dari biodata yang sudah diisi.</p>
            @php
                $genderColors = ['Laki-laki' => '#2a78d6', 'Perempuan' => '#eb6834', 'Belum diisi' => '#c3c2b7'];
            @endphp
            <div class="relative mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Komposisi jenis kelamin pendaftar',
                    'height' => 'h-56',
                    'config' => [
                        'type' => 'doughnut',
                        'labels' => array_keys($report['gender']),
                        'datasets' => [['label' => 'Pendaftar', 'data' => array_values($report['gender']), 'colors' => array_values($genderColors)]],
                        'unit' => 'pendaftar',
                    ],
                ])
                @if($total > 0)
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-semibold text-slate-900">{{ $number($total) }}</span>
                        <span class="text-xs text-slate-500">pendaftar</span>
                    </div>
                @endif
            </div>
            <dl class="mt-5 space-y-3">
                @foreach($report['gender'] as $label => $count)
                    <div class="flex items-center justify-between text-sm">
                        <dt class="flex items-center gap-2 text-slate-700"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $genderColors[$label] }}"></span>{{ $label }}</dt>
                        <dd class="text-slate-500"><span class="font-semibold text-slate-900">{{ $number($count) }}</span> · {{ $percent($count) }}%</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    {{-- Prodi pilihan 1 & 2 --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Pendaftar per Program Studi</h2>
        <p class="mt-1 text-sm text-slate-600">Jumlah pendaftar yang memilih prodi sebagai pilihan 1 dan pilihan 2.</p>

        <div class="mt-5 grid gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3">
                @include('admin.reports.partials.chart', [
                    'label' => 'Pendaftar per program studi, pilihan 1 dan pilihan 2',
                    'config' => [
                        'type' => 'bar',
                        'labels' => $report['programs']->pluck('code'),
                        'datasets' => [
                            ['label' => 'Pilihan 1', 'data' => $report['programs']->pluck('first')],
                            ['label' => 'Pilihan 2', 'data' => $report['programs']->pluck('second')],
                        ],
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
            <div class="overflow-x-auto lg:col-span-2">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-400">
                        <tr><th class="{{ $th }}">No</th><th class="{{ $th }}">Prodi</th><th class="{{ $th }} text-right">Pilihan 1</th><th class="{{ $th }} text-right">Pilihan 2</th></tr>
                    </thead>
                    <tbody class="table-zebra">
                        @foreach($report['programs'] as $program)
                            <tr>
                                <td class="{{ $td }} text-slate-500">{{ $loop->iteration }}</td>
                                <td class="{{ $td }}"><span class="font-semibold text-slate-900">{{ $program['code'] }}</span> <span class="text-xs text-slate-500">{{ $program['name'] }}</span></td>
                                <td class="{{ $td }} text-right font-semibold">{{ $number($program['first']) }}</td>
                                <td class="{{ $td }} text-right">{{ $number($program['second']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 font-semibold">
                        <tr>
                            <td class="{{ $td }}"></td>
                            <td class="{{ $td }}">Total</td>
                            <td class="{{ $td }} text-right">{{ $number($report['programs']->sum('first')) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($report['programs']->sum('second')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Per bulan --}}
        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Pendaftar per Bulan</h2>
            <p class="mt-1 text-sm text-slate-600">Berdasarkan tanggal pembuatan akun. Garis putus-putus menunjukkan tren linear.</p>
            <div class="mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Jumlah pendaftar per bulan',
                    'config' => [
                        'type' => 'bar',
                        'labels' => $report['monthly']->keys(),
                        'datasets' => [['label' => 'Pendaftar', 'data' => $report['monthly']->values()]],
                        'trend' => true,
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
        </section>

        {{-- Sumber informasi --}}
        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Sumber Informasi Pendaftaran</h2>
            <p class="mt-1 text-sm text-slate-600">Dari mana pendaftar mengetahui PMB Polmind.</p>
            <div class="mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Sumber informasi pendaftaran',
                    'config' => [
                        'type' => 'bar',
                        'horizontal' => true,
                        'labels' => $report['sources']->keys(),
                        'datasets' => [['label' => 'Pendaftar', 'data' => $report['sources']->values()]],
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
        </section>

        {{-- Tahun lulus --}}
        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Tahun Lulus Pendaftar</h2>
            <p class="mt-1 text-sm text-slate-600">Tahun lulus sekolah asal.</p>
            <div class="mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Tahun lulus pendaftar',
                    'config' => [
                        'type' => 'bar',
                        'labels' => $report['graduationYears']->keys(),
                        'datasets' => [['label' => 'Pendaftar', 'data' => $report['graduationYears']->values()]],
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
        </section>

        {{-- Jurusan --}}
        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900">Jurusan Pendidikan Asal</h2>
            <p class="mt-1 text-sm text-slate-600">Jurusan di sekolah asal, 11 terbanyak.</p>
            <div class="mt-5">
                @include('admin.reports.partials.chart', [
                    'label' => 'Jurusan pendidikan asal pendaftar',
                    'config' => [
                        'type' => 'bar',
                        'horizontal' => true,
                        'labels' => $report['majors']->keys(),
                        'datasets' => [['label' => 'Pendaftar', 'data' => $report['majors']->values()]],
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
        </section>
    </div>

    {{-- Jenis sekolah --}}
    <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-slate-900">Jenis Pendidikan Asal</h2>
        <p class="mt-1 text-sm text-slate-600">SMA, SMK, MA, dan lainnya, dipisah sekolah negeri dan swasta.</p>

        <div class="mt-5 grid gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3">
                @include('admin.reports.partials.chart', [
                    'label' => 'Jenis pendidikan asal, negeri dan swasta',
                    'config' => [
                        'type' => 'bar',
                        'stacked' => true,
                        'labels' => $report['schoolTypes']->pluck('type'),
                        'datasets' => [
                            ['label' => 'Negeri', 'data' => $report['schoolTypes']->pluck('negeri')],
                            ['label' => 'Swasta', 'data' => $report['schoolTypes']->pluck('swasta')],
                        ],
                        'unit' => 'pendaftar',
                    ],
                ])
            </div>
            <div class="overflow-x-auto lg:col-span-2">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-400">
                        <tr><th class="{{ $th }}">No</th><th class="{{ $th }}">Jenis</th><th class="{{ $th }} text-right">Negeri</th><th class="{{ $th }} text-right">Swasta</th><th class="{{ $th }} text-right">Total</th></tr>
                    </thead>
                    <tbody class="table-zebra">
                        @foreach($report['schoolTypes'] as $row)
                            <tr>
                                <td class="{{ $td }} text-slate-500">{{ $loop->iteration }}</td>
                                <td class="{{ $td }} font-semibold text-slate-900">{{ $row['type'] }}</td>
                                <td class="{{ $td }} text-right">{{ $number($row['negeri']) }}</td>
                                <td class="{{ $td }} text-right">{{ $number($row['swasta']) }}</td>
                                <td class="{{ $td }} text-right font-semibold">{{ $number($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Asal sekolah --}}
    <section x-data="{ search: '' }" class="{{ $card }}">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Pendaftar per Asal Sekolah</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $number($report['schools']->count()) }} sekolah, diurutkan dari pendaftar terbanyak.</p>
            </div>
            <input type="search" x-model="search" placeholder="Cari nama sekolah atau NPSN..."
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 md:w-72">
        </div>

        <div class="mt-5 max-h-[520px] overflow-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="sticky top-0 border-b border-slate-200 bg-white text-xs text-slate-400">
                    <tr>
                        <th class="{{ $th }}">No</th>
                        <th class="{{ $th }}">Sekolah</th>
                        <th class="{{ $th }}">Jenis</th>
                        <th class="{{ $th }} text-right">Pendaftar</th>
                        <th class="{{ $th }} text-right">Formulir Lunas</th>
                        <th class="{{ $th }} text-right">Diterima</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($report['schools'] as $school)
                        <tr x-show="! search || @js(mb_strtolower($school['name'] . ' ' . $school['npsn'])).includes(search.toLowerCase())">
                            <td class="{{ $td }} text-slate-500">{{ $loop->iteration }}</td>
                            <td class="{{ $td }}">
                                <p class="font-semibold text-slate-900">{{ $school['name'] }}</p>
                                @if($school['npsn'])
                                    <p class="text-xs text-slate-500">NPSN {{ $school['npsn'] }}</p>
                                @endif
                            </td>
                            <td class="{{ $td }} text-slate-600">{{ $school['type'] }}</td>
                            <td class="{{ $td }} text-right font-semibold text-slate-900">{{ $number($school['total']) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($school['paid']) }}</td>
                            <td class="{{ $td }} text-right">{{ $number($school['accepted']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada pendaftar.</td></tr>
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
