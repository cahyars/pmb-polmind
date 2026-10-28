@extends('layouts.print')

@section('title', 'Kartu Ujian ' . $applicant->registration_number)
@section('back_url', route('camaba.status-seleksi'))

@section('content')
@php
    $wave = $applicant->admissionWave;
    $schedules = $wave?->selectionSchedules ?? collect();
    $program = fn ($studyProgram) => $studyProgram ? $studyProgram->degree . ' - ' . $studyProgram->name : '-';

    $rows = [
        'Nomor Pendaftaran' => $applicant->registration_number,
        'Nama Lengkap' => strtoupper($applicant->full_name),
        'NIK' => $applicant->nik ?? '-',
        'Tempat, Tanggal Lahir' => trim(($applicant->birth_place ?? '-') . ', ' . ($applicant->birth_date?->translatedFormat('j F Y') ?? '-')),
        'Jenis Kelamin' => $applicant->gender_label,
        'Asal Sekolah' => $applicant->education?->school_name ?? '-',
        'Jalur / Gelombang' => $applicant->registration_path_label . ' / ' . ($wave?->name ?? '-'),
        'Pilihan Prodi 1' => $program($applicant->studyProgram),
        'Pilihan Prodi 2' => $program($applicant->secondStudyProgram),
        'Kelas' => $applicant->classType?->name ?? '-',
    ];
@endphp

@include('print.partials.letterhead', ['pmbYear' => $applicant->pmbYear])

<h1 class="mt-6 text-center text-lg font-black uppercase tracking-wider text-slate-900">Kartu Peserta Ujian Seleksi</h1>
<p class="text-center text-sm text-slate-600">{{ $applicant->pmbYear?->name }} · {{ $wave?->name }}</p>

<section class="mt-6 flex gap-6">
    <div class="flex h-[40mm] w-[30mm] shrink-0 items-center justify-center overflow-hidden border border-slate-400 bg-slate-50">
        @if($applicant->photoDocument)
            <img src="{{ route('camaba.documents.file', $applicant->photoDocument) }}" alt="Pas foto {{ $applicant->full_name }}" class="h-full w-full object-cover">
        @else
            <span class="px-2 text-center text-xs text-slate-500">Tempel pas foto 3x4</span>
        @endif
    </div>

    <table class="w-full text-sm">
        <tbody>
            @foreach($rows as $label => $value)
                <tr class="align-top">
                    <td class="w-44 py-1 pr-2 text-slate-600">{{ $label }}</td>
                    <td class="w-3 py-1 text-slate-600">:</td>
                    <td class="py-1 font-semibold text-slate-900">{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>

<section class="mt-8">
    <h2 class="text-sm font-black uppercase tracking-wide text-polmind-blue">Jadwal Ujian Seleksi</h2>

    @if($schedules->isEmpty())
        <p class="mt-3 border border-slate-300 p-4 text-sm text-slate-600">Jadwal tes belum diumumkan. Pantau menu Status Seleksi di dashboard camaba.</p>
    @else
        <table class="mt-3 w-full border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100 text-left">
                    <th class="border border-slate-300 px-3 py-2 font-semibold">No</th>
                    <th class="border border-slate-300 px-3 py-2 font-semibold">Tes</th>
                    <th class="border border-slate-300 px-3 py-2 font-semibold">Hari, Tanggal</th>
                    <th class="border border-slate-300 px-3 py-2 font-semibold">Waktu</th>
                    <th class="border border-slate-300 px-3 py-2 font-semibold">Lokasi</th>
                    <th class="border border-slate-300 px-3 py-2 font-semibold">Paraf Pengawas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schedules as $schedule)
                    <tr class="align-top">
                        <td class="border border-slate-300 px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="border border-slate-300 px-3 py-2 font-semibold">{{ $schedule->component?->name ?? 'Tes Seleksi' }}</td>
                        <td class="border border-slate-300 px-3 py-2">{{ $schedule->test_date?->translatedFormat('l, j F Y') ?? '-' }}</td>
                        <td class="border border-slate-300 px-3 py-2">{{ $schedule->time_range ?? 'Menyusul' }}</td>
                        <td class="border border-slate-300 px-3 py-2">{{ $schedule->location ?? 'Menyusul' }}</td>
                        <td class="w-28 border border-slate-300 px-3 py-2"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section class="mt-8 grid grid-cols-[1fr_auto] gap-8 text-sm">
    <div>
        <h2 class="text-sm font-black uppercase tracking-wide text-polmind-blue">Ketentuan Peserta</h2>
        <ol class="mt-2 list-decimal space-y-1 pl-5 text-slate-700">
            <li>Kartu ini wajib dibawa dan ditunjukkan kepada pengawas pada setiap tes.</li>
            <li>Bawa KTP/kartu pelajar asli sebagai bukti identitas.</li>
            <li>Hadir paling lambat 30 menit sebelum tes dimulai dan berpakaian rapi.</li>
            <li>Perubahan jadwal diinformasikan melalui dashboard camaba dan WhatsApp panitia.</li>
        </ol>
    </div>

    <div class="w-56 text-center">
        <p class="text-slate-700">Dicetak {{ now()->translatedFormat('j F Y') }}</p>
        <p class="mt-1 text-slate-700">Panitia PMB Polmind</p>
        <div class="h-20"></div>
        <p class="border-t border-slate-400 pt-1 text-xs text-slate-500">Tanda tangan &amp; cap panitia</p>
    </div>
</section>
@endsection
