@extends('layouts.admin')

@section('title', 'Laporan PMB')
@section('page_title', 'Laporan PMB')
@section('page_subtitle', 'Rekapitulasi pendaftaran, seleksi, pembayaran, daftar ulang, dan integrasi SIAKAD.')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Laporan PMB Politeknik Mitra Industri</p>
                <h1 class="mt-2 text-3xl font-black">Monitoring PMB 2026</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-blue-100">
                    Total pendaftar sementara {{ $totalApplicants }} camaba dari target {{ $targetApplicants }}.
                    Progress target saat ini sekitar {{ $targetProgress }}%.
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-72">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-blue-100">Progress Target</p>
                    <p class="text-xl font-black text-polmind-yellow">{{ $targetProgress }}%</p>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ min($targetProgress, 100) }}%"></div>
                </div>

                <p class="mt-3 text-xs text-blue-100">
                    {{ $totalApplicants }} / {{ $targetApplicants }} pendaftar
                </p>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Laporan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Gunakan filter untuk melihat laporan berdasarkan rentang tanggal pendaftaran dan program studi.
            </p>
        </div>

        <form action="{{ route('admin.reports.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Mulai</label>
                <input type="date"
                       name="start_date"
                       value="{{ request('start_date') }}"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Tanggal Akhir</label>
                <input type="date"
                       name="end_date"
                       value="{{ request('end_date') }}"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }} - {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan
                </button>

                <a href="{{ route('admin.reports.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Pendaftar', 'value' => $totalApplicants, 'desc' => 'Semua camaba', 'class' => 'text-polmind-blue'],
            ['label' => 'Biodata Lengkap', 'value' => $biodataComplete, 'desc' => 'Siap proses administrasi', 'class' => 'text-blue-700'],
            ['label' => 'Berkas Valid', 'value' => $documentValid, 'desc' => 'Dokumen diterima', 'class' => 'text-green-700'],
            ['label' => 'Pembayaran Valid', 'value' => $paymentValid, 'desc' => 'Pembayaran pendaftaran valid', 'class' => 'text-green-700'],
            ['label' => 'Diterima', 'value' => $acceptedApplicants, 'desc' => 'Lolos seleksi', 'class' => 'text-purple-700'],
            ['label' => 'Daftar Ulang Valid', 'value' => $reRegistered, 'desc' => 'Sudah validasi DU', 'class' => 'text-green-700'],
            ['label' => 'Siap Sinkron', 'value' => $readySync, 'desc' => 'Siap ke SIAKAD', 'class' => 'text-purple-700'],
            ['label' => 'Sudah Sinkron', 'value' => $synced, 'desc' => 'NIM sudah diterima', 'class' => 'text-polmind-blue'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

        {{-- Main --}}
        <div class="space-y-8">

            {{-- Funnel --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">Funnel PMB</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Alur progres camaba dari registrasi awal sampai siap sinkron.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                        Real Database
                    </span>
                </div>

                <div class="mt-6 space-y-5">
                    @foreach($funnel as $item)
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item['value'] }} camaba</p>
                                </div>

                                <p class="text-sm font-black text-polmind-blue">{{ $item['percent'] }}%</p>
                            </div>

                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($item['percent'], 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Sebaran Prodi --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">Sebaran Program Studi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Rekap pendaftar, diterima, daftar ulang valid, dan siap sinkron per prodi.
                </p>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Prodi</th>
                                <th class="px-5 py-4">Kuota</th>
                                <th class="px-5 py-4">Pendaftar</th>
                                <th class="px-5 py-4">Diterima</th>
                                <th class="px-5 py-4">DU Valid</th>
                                <th class="px-5 py-4">Siap Sync</th>
                                <th class="px-5 py-4">Progress Kuota</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200">
                            @foreach($programReports as $program)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-black text-polmind-blue">{{ $program['code'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $program['name'] }}</p>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-slate-700">{{ $program['quota'] }}</td>
                                    <td class="px-5 py-4 font-bold text-slate-700">{{ $program['registrants'] }}</td>
                                    <td class="px-5 py-4 font-bold text-slate-700">{{ $program['accepted'] }}</td>
                                    <td class="px-5 py-4 font-bold text-green-700">{{ $program['re_registered'] }}</td>
                                    <td class="px-5 py-4 font-bold text-purple-700">{{ $program['ready_sync'] }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-3 w-28 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($program['quota_percent'], 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-black text-polmind-blue">
                                                {{ $program['quota_percent'] }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pembayaran --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">Ringkasan Pembayaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Rekap pembayaran valid, menunggu validasi, dan ditolak.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    <div class="rounded-2xl bg-green-50 p-5">
                        <p class="text-sm font-bold text-green-700">Total Pembayaran Valid</p>
                        <p class="mt-3 text-2xl font-black text-green-700">
                            Rp{{ number_format($totalPaid, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs text-green-700">{{ $validPayments }} pembayaran valid</p>
                    </div>

                    <div class="rounded-2xl bg-blue-50 p-5">
                        <p class="text-sm font-bold text-polmind-blue">Pendaftaran</p>
                        <p class="mt-3 text-2xl font-black text-polmind-blue">
                            Rp{{ number_format($registrationPaid, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Nominal dari invoice pendaftaran</p>
                    </div>

                    <div class="rounded-2xl bg-purple-50 p-5">
                        <p class="text-sm font-bold text-purple-700">Daftar Ulang</p>
                        <p class="mt-3 text-2xl font-black text-purple-700">
                            Rp{{ number_format($reRegistrationPaid, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Nominal dari invoice daftar ulang</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-500">Menunggu Validasi</p>
                        <p class="mt-2 text-2xl font-black text-yellow-700">{{ $waitingPayments }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-500">Valid</p>
                        <p class="mt-2 text-2xl font-black text-green-700">{{ $validPayments }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-500">Ditolak</p>
                        <p class="mt-2 text-2xl font-black text-red-700">{{ $rejectedPayments }}</p>
                    </div>
                </div>
            </div>

            {{-- Sebaran Kelas --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">Sebaran Kelas</h2>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach($classReports as $class)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-lg font-black text-polmind-blue">{{ $class['name'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $class['code'] }}</p>

                            <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-xl bg-white p-3">
                                    <p class="text-xs text-slate-500">Pendaftar</p>
                                    <p class="mt-1 text-xl font-black text-slate-900">{{ $class['registrants'] }}</p>
                                </div>
                                <div class="rounded-xl bg-white p-3">
                                    <p class="text-xs text-slate-500">Diterima</p>
                                    <p class="mt-1 text-xl font-black text-purple-700">{{ $class['accepted'] }}</p>
                                </div>
                                <div class="rounded-xl bg-white p-3">
                                    <p class="text-xs text-slate-500">DU Valid</p>
                                    <p class="mt-1 text-xl font-black text-green-700">{{ $class['re_registered'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            {{-- Executive Summary --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Ringkasan Eksekutif</h2>

                <div class="mt-5 space-y-4 text-sm leading-6 text-slate-700">
                    <p>
                        Total pendaftar saat ini
                        <span class="font-black text-polmind-blue">{{ $totalApplicants }} camaba</span>
                        dari target {{ $targetApplicants }}.
                    </p>

                    <p>
                        Camaba yang sudah dinyatakan diterima:
                        <span class="font-black text-purple-700">{{ $acceptedApplicants }}</span>.
                    </p>

                    <p>
                        Daftar ulang valid:
                        <span class="font-black text-green-700">{{ $reRegistered }}</span>,
                        dan siap sinkron ke SIAKAD:
                        <span class="font-black text-purple-700">{{ $readySync }}</span>.
                    </p>
                </div>
            </div>

            {{-- Source Information --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Sumber Informasi</h2>

                <div class="mt-5 space-y-4">
                    @forelse($sourceReports as $source)
                        @php
                            $percent = $totalApplicants > 0
                                ? round(($source->total / $totalApplicants) * 100)
                                : 0;
                        @endphp

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-slate-700">
                                    {{ $source->source_information }}
                                </p>
                                <p class="text-xs font-black text-polmind-blue">
                                    {{ $source->total }}
                                </p>
                            </div>

                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($percent, 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">
                            Belum ada data sumber informasi.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Latest Applicants --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Pendaftar Terbaru</h2>

                <div class="mt-5 space-y-3">
                    @forelse($latestApplicants as $applicant)
                        <a href="{{ url('/admin/applicants/' . $applicant->registration_number) }}"
                           class="block rounded-2xl border border-slate-200 p-4 transition hover:bg-slate-50">
                            <p class="text-sm font-black text-polmind-blue">{{ $applicant->registration_number }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $applicant->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $applicant->studyProgram?->code ?? '-' }} · {{ $applicant->classType?->name ?? '-' }}
                            </p>
                        </a>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">Belum ada pendaftar.</p>
                    @endforelse
                </div>
            </div>

            {{-- Latest Payments --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-polmind-blue">Pembayaran Terbaru</h2>

                <div class="mt-5 space-y-3">
                    @forelse($latestPayments as $payment)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-sm font-black text-polmind-blue">{{ $payment->payment_number }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">
                                {{ $payment->applicant?->full_name ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $payment->invoice?->invoice_number ?? '-' }}
                            </p>
                            <p class="mt-2 font-black text-green-700">
                                Rp{{ number_format($payment->amount, 0, ',', '.') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">Belum ada pembayaran.</p>
                    @endforelse
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection