@extends('layouts.admin')

@section('title', 'Report Format Mitra')
@section('page_title', 'Report Format Mitra')
@section('page_subtitle', 'Laporan · ' . $sections[$section]['description'])

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
@endphp

<div class="space-y-6">
    @include('admin.reports.partials.nav')

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.reports.show', $section) }}" method="GET" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="wave" class="text-xs font-semibold text-slate-600">Gelombang</label>
                <select id="wave" name="wave" class="{{ $input }}">
                    @foreach($waves as $wave)
                        <option value="{{ $wave->id }}" @selected($report && $report['wave']->id === $wave->id)>{{ $wave->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date" class="text-xs font-semibold text-slate-600">Sampai tanggal (hari H)</label>
                <input type="date" id="date" name="date" max="{{ today()->toDateString() }}"
                       value="{{ $report ? $report['date']->toDateString() : today()->toDateString() }}" class="{{ $input }}">
            </div>
            <div>
                <label for="range" class="text-xs font-semibold text-slate-600">Baris tanggal</label>
                <select id="range" name="range" class="{{ $input }}">
                    <option value="week" @selected(! ($report['wholeWave'] ?? false))>Minggu berjalan (Minggu - Sabtu)</option>
                    <option value="all" @selected($report['wholeWave'] ?? false)>Seluruh gelombang</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white">Terapkan</button>
                @if($report)
                    <button type="button" data-download-report="mitra-report"
                            data-filename="report-format-mitra-{{ \Illuminate\Support\Str::slug($report['wave']->name) }}-{{ $report['date']->format('Y-m-d') }}.png"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-polmind-yellow px-4 py-2.5 text-sm font-bold text-polmind-blue-dark transition hover:brightness-95 disabled:cursor-wait disabled:opacity-70">
                        <x-icon name="download" class="h-4 w-4" stroke-width="2" />
                        <span data-label>Download PNG</span>
                    </button>
                @endif
            </div>
        </div>
        <p class="mt-3 text-xs leading-5 text-slate-500">
            Satu baris = satu hari rekap yang ditutup pukul 18.00 WIB, sama dengan Rekap Harian WhatsApp. Baris Jumlah dan Total tetap akumulasi sejak awal gelombang.
            Mitra Industri / Afiliasi = pendaftar lulusan SMK Mitra Industri.
        </p>
    </form>

    @if(! $report)
        <div class="rounded-xl border-2 border-dashed border-slate-200 px-6 py-12 text-center text-sm font-semibold text-slate-500">
            Belum ada gelombang pada Tahun Akademik ini.
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            @include('admin.reports.partials.mitra-sheets', ['report' => $report])
        </div>
    @endif
</div>

@vite('resources/js/report-image.js')
@endsection
