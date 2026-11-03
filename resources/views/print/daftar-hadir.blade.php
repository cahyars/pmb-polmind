@extends('layouts.print')

@section('title', 'Daftar Hadir Peserta ' . $wave->name)
@section('back_url', route('admin.spmb.prints.index', ['gelombang' => $wave->id]))

@section('content')
@php
    $pmbYear = $wave->pmbYear;
    $academicYear = $pmbYear ? 'TAHUN AKADEMIK ' . $pmbYear->year . '/' . ((int) $pmbYear->year + 1) : '';
    $subtitle = $wave->name . ' · ' . ($group?->name ?? ($partner ? 'Pendaftar SMK Mitra Industri' : 'Pendaftar Umum'));
@endphp

<style>
    /* Mengikuti Daftar_hadir_peserta.pdf: logo kiri, judul tengah, tabel No/Nama/Asal Sekolah/TTD. */
    .dh { font-family: Arial, 'Liberation Sans', Helvetica, sans-serif; color: #000; }
    .dh-head { display: grid; grid-template-columns: 34mm 1fr 34mm; align-items: center; margin-bottom: 4mm; }
    .dh-head img { max-height: 15mm; }
    .dh-title { text-align: center; font-size: 10pt; font-weight: 700; line-height: 1.35; }
    .dh-sub { text-align: center; font-size: 8pt; margin-top: 1mm; }
    .dh-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    .dh-table th, .dh-table td { border: 0.3mm solid #000; padding: 0 2mm; height: 7.6mm; }
    .dh-table th { font-weight: 700; text-align: left; }
    .dh-table .dh-no { width: 15mm; text-align: center; }
    .dh-table .dh-sign { width: 26mm; }
    .dh-table tr { break-inside: avoid; }
</style>

<div class="dh">
    <div class="dh-head">
        <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri">
        <div>
            <p class="dh-title">DAFTAR HADIR PESERTA<br>SELEKSI PENERIMAAN MAHASISWA BARU<br>{{ $academicYear }}</p>
            <p class="dh-sub">{{ $subtitle }}</p>
        </div>
    </div>

    <table class="dh-table">
        <thead>
            <tr>
                <th class="dh-no">No</th>
                <th>Nama Lengkap</th>
                <th style="text-align: center">Asal Sekolah</th>
                <th class="dh-sign" style="text-align: center">TTD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($applicants as $applicant)
                <tr>
                    <td class="dh-no">{{ $applicant->selection_number }}</td>
                    <td>{{ $partner ? mb_strtoupper($applicant->full_name) : $applicant->full_name }}</td>
                    <td>{{ $applicant->education?->school_name ?? '-' }}</td>
                    <td></td>
                </tr>
            @endforeach
            @for($index = 0; $index < $blank; $index++)
                <tr>
                    <td class="dh-no">{{ \App\Services\SelectionNumber::format($wave, $nextSequence + $index) }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    @if($applicants->isEmpty() && ! $blank)
        <p class="mt-6 text-center text-sm text-slate-600 print:hidden">Belum ada peserta bernomor pada daftar ini.</p>
    @endif
</div>
@endsection
