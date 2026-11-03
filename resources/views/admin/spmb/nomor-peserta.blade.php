@extends('layouts.admin')

@section('title', 'Nomor Peserta Seleksi')
@section('page_title', 'Nomor Peserta Seleksi')
@section('page_subtitle', 'Seleksi · Nomor urut peserta per gelombang untuk kartu peserta dan daftar hadir.')

{{-- Nomor urut peserta seleksi per gelombang, format {gelombang}.{urutan}; pendaftar mitra punya range sendiri. --}}
@section('content')
<div class="space-y-6">
@include('admin.spmb.partials.header')

@if($selectedWave)
    @php
        $waveDigit = \App\Services\RegistrationNumber::waveDigit($selectedWave);
        $canOpenApplicant = ! auth('web')->user()?->isSelectionChair();
        $sections = [
            'general' => [
                'title' => 'Pendaftar Umum',
                'jenis' => 'umum',
                'description' => 'Nomor ' . \App\Services\SelectionNumber::format($selectedWave, 1) . ' dan seterusnya, urut dari pendaftar paling awal. Beri Nomor menambahkan nomor lanjutan untuk yang baru bayar; Susun Ulang mengurutkan ulang semua nomor umum, lakukan sebelum kartu dicetak.',
                'empty' => 'Belum ada pendaftar umum ' . $selectedWave->name . ' yang sudah bayar formulir.',
            ],
            'partner' => [
                'title' => 'Pendaftar SMK Mitra Industri',
                'jenis' => 'mitra',
                'description' => 'Range terpisah mulai ' . \App\Services\SelectionNumber::format($selectedWave, $partnerStart) . '. Biasanya diberi nomor paling akhir, setelah nomor pendaftar umum selesai.',
                'empty' => 'Belum ada pendaftar SMK Mitra Industri / Ananda Mitra Industri ' . $selectedWave->name . ' yang sudah bayar formulir.',
            ],
        ];
    @endphp

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Format nomor</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-polmind-blue">
                {{ \App\Services\SelectionNumber::format($selectedWave, 1) }}
                <span class="text-base font-semibold text-purple-700">· {{ \App\Services\SelectionNumber::format($selectedWave, $partnerStart) }}</span>
            </p>
            <p class="mt-1 text-xs leading-5 text-slate-500">
                {{ $waveDigit }} = kode {{ $selectedWave->name }}. Umum mulai 001, mitra mulai {{ str_pad((string) $partnerStart, 3, '0', STR_PAD_LEFT) }}.
            </p>
        </div>
        @foreach($sections as $key => $section)
            @php
                $unnumberedCount = $lists[$key]['unnumbered']->count();
            @endphp
            <div class="rounded-xl border p-5 shadow-sm {{ $unnumberedCount ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }}">
                <p class="text-sm font-semibold text-slate-500">{{ $section['title'] }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">{{ $lists[$key]['numbered']->count() }} <span class="text-sm font-semibold text-slate-500">bernomor</span></p>
                <p class="mt-1 text-xs {{ $unnumberedCount ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ $unnumberedCount }} sudah bayar, belum bernomor</p>
            </div>
        @endforeach
    </div>

    @foreach($sections as $key => $section)
        @php
            $numbered = $lists[$key]['numbered'];
            $unnumbered = $lists[$key]['unnumbered'];
            $unpaidNumbered = $numbered->where('payment_status', '!=', 'valid');
            $isPartner = $key === 'partner';
        @endphp

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">
                        {{ $section['title'] }}
                        @if($isPartner)
                            <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-bold text-purple-700">Mitra</span>
                        @endif
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">{{ $section['description'] }}</p>
                </div>

                <div class="flex shrink-0 flex-wrap items-end gap-2">
                    <form action="{{ route('admin.spmb.numbers.assign', $selectedWave) }}" method="POST">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $section['jenis'] }}">
                        <button type="submit" @disabled($unnumbered->isEmpty())
                                class="rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">
                            Beri Nomor ({{ $unnumbered->count() }})
                        </button>
                    </form>

                    <form action="{{ route('admin.spmb.numbers.renumber', $selectedWave) }}" method="POST" class="flex items-end gap-2"
                          x-data
                          @submit.prevent="window.Swal.fire({
                              icon: 'warning',
                              title: @js('Susun ulang nomor ' . strtolower($section['title']) . '?'),
                              text: @js('Nomor diurutkan ulang sesuai urutan mendaftar. Kartu peserta yang sudah dicetak bisa tidak sesuai lagi.'),
                              showCancelButton: true,
                              confirmButtonText: 'Ya, susun ulang',
                              cancelButtonText: 'Batal',
                              confirmButtonColor: '#003B82',
                          }).then((result) => result.isConfirmed && $el.submit())">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $section['jenis'] }}">
                        @if($isPartner)
                            <label class="text-xs font-semibold text-slate-600">
                                Mulai dari
                                <span class="mt-1 flex items-center rounded-lg border border-slate-300 bg-white text-sm">
                                    <span class="pl-3 text-slate-500">{{ $waveDigit }}.</span>
                                    <input type="number" name="mulai" min="1" max="999" required value="{{ old('mulai', $partnerStart) }}"
                                           class="w-20 rounded-lg border-0 px-1 py-2 text-sm font-semibold outline-none">
                                </span>
                            </label>
                        @endif
                        <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Susun Ulang
                        </button>
                    </form>
                </div>
            </div>

            @if($unpaidNumbered->isNotEmpty())
                <p class="border-b border-red-100 bg-red-50 px-6 py-3 text-sm text-red-700">
                    {{ $unpaidNumbered->count() }} camaba bernomor tetapi pembayarannya tidak lagi valid. Susun Ulang untuk melepas nomornya.
                </p>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3 [&_th]:font-medium">
                        <tr>
                            <th class="w-28">No. Peserta</th>
                            <th>Camaba</th>
                            <th>Asal Sekolah</th>
                            <th>Program Studi</th>
                            <th>Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody class="table-zebra">
                        @forelse($numbered->concat($unnumbered) as $applicant)
                            <tr>
                                <td class="px-5 py-3">
                                    @if($applicant->selection_sequence)
                                        <span class="text-base font-bold tabular-nums {{ $isPartner ? 'text-purple-700' : 'text-polmind-blue' }}">{{ $applicant->selection_number }}</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Belum</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if($canOpenApplicant)
                                        <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}" class="font-semibold text-slate-800 hover:text-polmind-blue">{{ $applicant->full_name }}</a>
                                    @else
                                        <span class="font-semibold text-slate-800">{{ $applicant->full_name }}</span>
                                    @endif
                                    <span class="block text-xs text-slate-500">{{ $applicant->registration_number }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $applicant->education?->school_name ?? '-' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $applicant->studyProgram ? $applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name : '-' }}</td>
                                <td class="px-5 py-3 tabular-nums text-slate-600">{{ $applicant->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">{{ $section['empty'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    <div class="flex justify-end">
        <a href="{{ route('admin.spmb.prints.index', ['gelombang' => $selectedWave->id]) }}"
           class="inline-flex items-center gap-2 rounded-lg bg-polmind-yellow px-4 py-2.5 text-sm font-bold text-polmind-blue-dark hover:brightness-95">
            <x-icon name="printer" class="h-4 w-4" stroke-width="2" />
            Cetak Kartu &amp; Daftar Hadir
        </a>
    </div>
@endif
</div>
@endsection
