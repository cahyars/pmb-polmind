@extends('layouts.print')

@section('title', 'Daftar Kelompok Peserta ' . $wave->name)
@section('back_url', route('admin.spmb.prints.index', ['gelombang' => $wave->id]))

@section('content')
@php
    $pmbYear = $wave->pmbYear;
    $academicYear = $pmbYear ? 'Tahun Ajaran ' . $pmbYear->year . '/' . ((int) $pmbYear->year + 1) : '';

    // Warna baris mengikuti Daftar_kelompok_peserta.pdf; kelompok mitra ungu muda, cadangan biru muda.
    // Lembar 1 berisi kelompok umum, lembar 2 kelompok mitra.
    $palette = ['#FCE4D6', '#C6EFCE', '#F2D0EE', '#FFF2CC', '#D9F2EC', '#FBE5E5'];
    $regularIndex = 0;
    $sheets = ['general' => [], 'partner' => []];
    $rowsOf = fn ($applicants) => $applicants->map(fn ($applicant) => [$applicant->selection_number, mb_strtoupper($applicant->full_name)])->all();

    foreach ($groups as $group) {
        $members = $participants->get($group->id, collect());
        if ($members->isEmpty()) {
            continue;
        }
        $sheets[$group->is_partner ? 'partner' : 'general'][] = [
            'label' => $group->name,
            'color' => $group->is_partner ? '#E4DFEC' : $palette[$regularIndex++ % count($palette)],
            'tests' => $group->components->pluck('id')->all(),
            'rows' => $rowsOf($members),
        ];
    }

    if ($participants->has('')) {
        $sheets['general'][] = [
            'label' => 'Belum berkelompok',
            'color' => '#F1F5F9',
            'tests' => $components->pluck('id')->all(),
            'rows' => $rowsOf($participants->get('')),
        ];
    }

    if ($blank) {
        $sheets['general'][] = [
            'label' => 'Cadangan',
            'color' => '#CDEBF7',
            'tests' => $components->pluck('id')->all(),
            'rows' => collect(range(0, $blank - 1))->map(fn ($index) => [\App\Services\SelectionNumber::format($wave, $nextSequence + $index), ''])->all(),
        ];
    }

    $sheets = array_filter($sheets);
@endphp

<style>
    .dk { font-family: Arial, 'Liberation Sans', Helvetica, sans-serif; color: #000; }
    .dk-head { display: grid; grid-template-columns: 34mm 1fr 34mm; align-items: center; margin-bottom: 4mm; }
    .dk-head img { max-height: 14mm; }
    .dk-title { text-align: center; font-size: 10pt; font-weight: 700; line-height: 1.35; }
    .dk-table { width: 100%; border-collapse: collapse; font-size: 7.6pt; }
    .dk-table th, .dk-table td { border: 0.25mm solid #000; padding: 0 1.5mm; height: 5.4mm; }
    .dk-table th { font-weight: 700; text-align: center; line-height: 1.15; padding: 1mm; }
    .dk-table td.dk-row { border-top-style: dotted; border-bottom-style: dotted; }
    .dk-table .dk-no { width: 14mm; text-align: center; }
    .dk-table .dk-test { width: 15mm; }
    .dk-table .dk-skip { background: repeating-linear-gradient(45deg, #D9D9D9 0 1mm, #EFEFEF 1mm 2mm); }
    .dk-table .dk-note { width: 20mm; text-align: center; vertical-align: middle; }
    .dk-table tr { break-inside: avoid; }
    /* Di layar tiap lembar tampil sebagai kertas A4 sendiri (dipisah jeda abu-abu selebar kertas);
       saat dicetak lembar berikutnya selalu mulai di halaman baru. */
    .dk-sheet { min-height: 273mm; }
    .dk-sheet + .dk-sheet { break-before: page; page-break-before: always; margin: 12mm -12mm 0; padding: 12mm 12mm 0; border-top: 8mm solid #e2e8f0; }
    .dk-sub { text-align: center; font-size: 8.5pt; font-weight: 700; margin-top: 1mm; }
    @media print {
        .dk-sheet { min-height: 0; }
        .dk-sheet + .dk-sheet { margin: 0; padding: 0; border-top: 0; }
    }
</style>

<div class="dk">
    @forelse($sheets as $sheet => $sections)
        <div class="dk-sheet">
            <div class="dk-head">
                <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri">
                <div>
                    <p class="dk-title">Pembagian Kelompok Peserta<br>Seleksi Penerimaan Mahasiswa Baru<br>{{ $academicYear }}</p>
                    @if($sheet === 'partner')
                        <p class="dk-sub">Peserta SMK Mitra Industri &amp; SMK Ananda Mitra Industri</p>
                    @endif
                </div>
            </div>

            <table class="dk-table">
                <thead>
                    <tr>
                        <th class="dk-no">No</th>
                        <th style="text-align: left">Nama Lengkap</th>
                        @foreach($components as $component)
                            <th class="dk-test">{{ $component->name }}</th>
                        @endforeach
                        <th class="dk-note">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sections as $section)
                        @foreach($section['rows'] as [$number, $name])
                            <tr>
                                <td class="dk-no dk-row" style="background: {{ $section['color'] }}">{{ $number }}</td>
                                <td class="dk-row" style="background: {{ $section['color'] }}">{{ $name }}</td>
                                @foreach($components as $component)
                                    <td @class(['dk-test', 'dk-skip' => ! in_array($component->id, $section['tests'])])></td>
                                @endforeach
                                @if($loop->first)
                                    <td class="dk-note" rowspan="{{ count($section['rows']) }}">{{ $section['label'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="mt-6 text-center text-sm text-slate-600 print:hidden">Belum ada peserta yang dikelompokkan.</p>
    @endforelse
</div>
@endsection
