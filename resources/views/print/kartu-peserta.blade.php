@extends('layouts.print')

@section('title', 'Kartu Peserta Seleksi' . ($wave ? ' ' . $wave->name : ''))
@section('back_url', route('admin.selections.index'))

@section('content')
@php
    $academicYear = $pmbYear ? 'TAHUN AKADEMIK ' . $pmbYear->year . '/' . ((int) $pmbYear->year + 1) : '';
    $input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
@endphp

<style>
    /* Kartu mengikuti Kartu Peserta.pdf: 2 kolom x 5 baris per A4. Lebar isi A4 = 210 - 2 x 12 mm. */
    .kp-page { display: grid; grid-template-columns: repeat(2, 89mm); column-gap: 8mm; row-gap: 5mm; align-content: start; }
    .kp-page + .kp-page { break-before: page; margin-top: 12mm; }
    .kp-card { width: 89mm; border-collapse: collapse; table-layout: fixed; font-family: Arial, 'Liberation Sans', Helvetica, sans-serif; color: #000; break-inside: avoid; }
    .kp-card td { border: 0.3mm solid #000; text-align: center; vertical-align: middle; padding: 0 1.2mm; }
    .kp-card .kp-logo { height: 15mm; padding: 1mm; }
    .kp-card .kp-logo img { max-height: 11.5mm; max-width: 100%; margin: 0 auto; }
    .kp-card .kp-title { font-size: 6.6pt; font-weight: 700; line-height: 1.3; }
    .kp-card .kp-label { background: #DDEBF7; font-size: 6.8pt; font-weight: 700; text-align: left; }
    .kp-card .kp-name { height: 6mm; font-size: 9.5pt; font-weight: 700; line-height: 1.1; }
    /* Nama panjang dikecilkan agar tetap satu baris dan tinggi kartu sebaris sama. */
    .kp-card .kp-name.kp-long { font-size: 7.6pt; }
    .kp-card .kp-name.kp-longer { font-size: 6.6pt; }
    .kp-card .kp-number { height: 9mm; font-size: 17pt; font-weight: 700; letter-spacing: 0.3mm; }
    .kp-card .kp-program { height: 6mm; font-size: 7.6pt; font-weight: 700; line-height: 1.1; }
    .kp-card .kp-test td { height: 4.2mm; background: #92D050; font-size: 6.6pt; font-weight: 700; color: #002060; }
    .kp-card .kp-stamp td { height: 9.5mm; }
    @media print { .kp-page + .kp-page { margin-top: 0; } }
</style>

{{-- Filter (tidak ikut tercetak) --}}
<form method="GET" action="{{ route('admin.selections.cards') }}" class="mb-6 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-5 print:hidden">
    <div>
        <label for="wave" class="text-xs font-semibold text-slate-600">Gelombang</label>
        <select id="wave" name="wave" class="{{ $input }}">
            @foreach($waves as $option)
                <option value="{{ $option->id }}" @selected($wave?->is($option))>{{ $option->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="study_program" class="text-xs font-semibold text-slate-600">Program studi</label>
        <select id="study_program" name="study_program" class="{{ $input }}">
            <option value="">Semua prodi</option>
            @foreach($studyPrograms as $program)
                <option value="{{ $program->id }}" @selected((int) ($filters['study_program'] ?? 0) === $program->id)>{{ $program->code }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="from" class="text-xs font-semibold text-slate-600">Dari urutan</label>
        <input id="from" type="number" min="1" name="from" value="{{ $filters['from'] ?? '' }}" placeholder="1" class="{{ $input }}">
    </div>
    <div>
        <label for="to" class="text-xs font-semibold text-slate-600">Sampai urutan</label>
        <input id="to" type="number" min="1" name="to" value="{{ $filters['to'] ?? '' }}" placeholder="terakhir" class="{{ $input }}">
    </div>
    <div class="flex items-end">
        <button type="submit" class="w-full rounded-lg bg-polmind-blue px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
    </div>
    <p class="text-xs text-slate-600 sm:col-span-5">
        {{ $applicants->count() }} kartu · {{ (int) ceil($applicants->count() / 10) }} lembar A4.
        @if($unnumbered)
            <span class="font-semibold text-amber-700">{{ $unnumbered }} camaba sudah bayar tetapi belum bernomor, sehingga belum ada kartunya.</span>
            @if(auth('web')->user()?->isAdmin())
                <a href="{{ route('admin.master-data.show', ['nomor-peserta', 'gelombang' => $wave?->id]) }}" class="font-semibold text-polmind-blue underline">Beri nomor di Master Data</a>
            @endif
        @endif
    </p>
</form>

@forelse($applicants->chunk(10) as $page)
    <div class="kp-page">
        @foreach($page as $applicant)
            <table class="kp-card">
                <colgroup><col style="width: 25%"><col style="width: 25%"><col style="width: 25%"><col style="width: 25%"></colgroup>
                <tr>
                    <td class="kp-logo"><img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri"></td>
                    <td colspan="3" class="kp-title">
                        KARTU PESERTA SELEKSI<br>
                        PENERIMAAN MAHASISWA BARU<br>
                        POLITEKNIK MITRA INDUSTRI<br>
                        {{ $academicYear }}
                    </td>
                </tr>
                <tr>
                    <td class="kp-label">Nama Peserta</td>
                    @php($nameLength = mb_strlen($applicant->full_name))
                    <td colspan="3" class="kp-name {{ $nameLength > 36 ? 'kp-longer' : ($nameLength > 28 ? 'kp-long' : '') }}">{{ mb_strtoupper($applicant->full_name) }}</td>
                </tr>
                <tr>
                    <td class="kp-label">Nomor Urut</td>
                    <td colspan="3" class="kp-number">{{ $applicant->selection_number }}</td>
                </tr>
                <tr>
                    <td class="kp-label">Program Studi</td>
                    <td colspan="3" class="kp-program">{{ $applicant->studyProgram ? $applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name : '-' }}</td>
                </tr>
                <tr class="kp-test">
                    <td>TPA dan TMB</td><td>Tes Fisik</td><td>Interview</td><td>MCU</td>
                </tr>
                <tr class="kp-stamp">
                    <td></td><td></td><td></td><td></td>
                </tr>
            </table>
        @endforeach
    </div>
@empty
    <div class="rounded-lg border-2 border-dashed border-slate-300 p-10 text-center text-sm text-slate-600 print:hidden">
        Belum ada peserta bernomor{{ $wave ? ' pada ' . $wave->name : '' }}.
        Nomor peserta diberikan di Master Data &gt; Nomor Peserta Seleksi.
    </div>
@endforelse
@endsection
