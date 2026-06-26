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
                Gunakan filter untuk melihat laporan berdasarkan tanggal, prodi, kelas, jalur, seleksi, daftar ulang, dan status sinkron.
            </p>
        </div>

        <form action="{{ route('admin.reports.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
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

            <div>
                <label class="text-sm font-bold text-slate-700">Kelas</label>
                <select name="class_type"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Kelas</option>
                    @foreach($classTypes as $classType)
                        <option value="{{ $classType->id }}" @selected(request('class_type') == $classType->id)>
                            {{ $classType->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jalur</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    <option value="umum" @selected(request('registration_path') === 'umum')>Umum</option>
                    <option value="prestasi" @selected(request('registration_path') === 'prestasi')>Prestasi</option>
                    <option value="undangan" @selected(request('registration_path') === 'undangan')>Undangan</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status Seleksi</label>
                <select name="selection_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Seleksi</option>
                    <option value="belum_diseleksi" @selected(request('selection_status') === 'belum_diseleksi')>Belum Diseleksi</option>
                    <option value="diterima" @selected(request('selection_status') === 'diterima')>Diterima</option>
                    <option value="cadangan" @selected(request('selection_status') === 'cadangan')>Cadangan</option>
                    <option value="ditolak" @selected(request('selection_status') === 'ditolak')>Ditolak</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status DU</label>
                <select name="re_registration_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua DU</option>
                    <option value="belum_daftar_ulang" @selected(request('re_registration_status') === 'belum_daftar_ulang')>Belum Daftar Ulang</option>
                    <option value="menunggu_verifikasi" @selected(request('re_registration_status') === 'menunggu_verifikasi')>Menunggu Verifikasi</option>
                    <option value="daftar_ulang_valid" @selected(request('re_registration_status') === 'daftar_ulang_valid')>Daftar Ulang Valid</option>
                    <option value="ditolak" @selected(request('re_registration_status') === 'ditolak')>Ditolak</option>
                    <option value="lewat_batas" @selected(request('re_registration_status') === 'lewat_batas')>Lewat Batas</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Sync SIAKAD</label>
                <select name="sync_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Sync</option>
                    <option value="belum_siap" @selected(request('sync_status') === 'belum_siap')>Belum Siap</option>
                    <option value="siap_sinkron" @selected(request('sync_status') === 'siap_sinkron')>Siap Sinkron</option>
                    <option value="proses_sinkron" @selected(request('sync_status') === 'proses_sinkron')>Proses Sinkron</option>
                    <option value="sudah_sinkron" @selected(request('sync_status') === 'sudah_sinkron')>Sudah Sinkron</option>
                    <option value="gagal_sinkron" @selected(request('sync_status') === 'gagal_sinkron')>Gagal Sinkron</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-6">
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

    {{-- Registration Path Reports --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">Rekap Jalur Pendaftaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Distribusi camaba berdasarkan jalur umum, prestasi, dan undangan.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                Real Database
            </span>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-3">
            @foreach($registrationPathReports as $path)
                @php
                    $pathClass = match ($path['key']) {
                        'prestasi' => 'text-green-700 bg-green-50 border-green-100',
                        'undangan' => 'text-purple-700 bg-purple-50 border-purple-100',
                        default => 'text-polmind-blue bg-blue-50 border-blue-100',
                    };
                @endphp

                <div class="rounded-2xl border p-5 {{ $pathClass }}">
                    <p class="text-sm font-bold">Jalur {{ $path['label'] }}</p>
                    <p class="mt-3 text-4xl font-black">{{ $path['registrants'] }}</p>
                    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-white/70 p-3">
                            <p class="text-xs font-bold">Diterima</p>
                            <p class="mt-1 text-xl font-black">{{ $path['accepted'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white/70 p-3">
                            <p class="text-xs font-bold">DU Valid</p>
                            <p class="mt-1 text-xl font-black">{{ $path['re_registered'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white/70 p-3">
                            <p class="text-xs font-bold">Sinkron</p>
                            <p class="mt-1 text-xl font-black">{{ $path['synced'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
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
                                <th class="px-5 py-4">Sudah Sync</th>
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
                                    <td class="px-5 py-4 font-bold text-green-700">{{ $program['synced'] }}</td>
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

            {{-- Rekap Kelas, Seleksi, Sync --}}
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-polmind-blue">Rekap Kelas</h2>
                    <div class="mt-5 space-y-3">
                        @foreach($classReports as $class)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-800">{{ $class['name'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $class['code'] }}</p>
                                    </div>
                                    <p class="text-2xl font-black text-polmind-blue">{{ $class['registrants'] }}</p>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                                    <div class="rounded-xl bg-white p-2">
                                        <p class="font-bold text-slate-500">Diterima</p>
                                        <p class="mt-1 font-black text-green-700">{{ $class['accepted'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white p-2">
                                        <p class="font-bold text-slate-500">DU</p>
                                        <p class="mt-1 font-black text-polmind-blue">{{ $class['re_registered'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white p-2">
                                        <p class="font-bold text-slate-500">Sync</p>
                                        <p class="mt-1 font-black text-purple-700">{{ $class['synced'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-polmind-blue">Status Seleksi</h2>
                    <div class="mt-5 space-y-3">
                        @foreach($selectionReports as $selection)
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-bold text-slate-700">{{ $selection['label'] }}</p>
                                <p class="text-2xl font-black text-polmind-blue">{{ $selection['total'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-polmind-blue">Status Sinkron</h2>
                    <div class="mt-5 space-y-3">
                        @foreach($syncReports as $sync)
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-bold text-slate-700">{{ $sync['label'] }}</p>
                                <p class="text-2xl font-black text-polmind-blue">{{ $sync['total'] }}</p>
                            </div>
                        @endforeach
                    </div>
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
                <button type="button"
        onclick="navigator.clipboard.writeText(`Selamat pagi Bapak/Ibu, izin menyampaikan update sementara PMB Politeknik Mitra Industri.

                Total pendaftar: {{ $totalApplicants }} camaba.
                Biodata lengkap: {{ $biodataComplete }} camaba.
                Berkas valid: {{ $documentValid }} camaba.
                Pembayaran pendaftaran valid: {{ $paymentValid }} camaba.
                Diterima: {{ $acceptedApplicants }} camaba.
                Cadangan: {{ $reserveApplicants }} camaba.
                Ditolak: {{ $rejectedApplicants }} camaba.
                Daftar ulang valid: {{ $reRegistered }} camaba.
                Siap sinkron SIAKAD: {{ $readySync }} camaba.
                Sudah sinkron SIAKAD: {{ $synced }} camaba.

                Rekap jalur pendaftaran:
                - Umum: {{ $registrationPathReports->firstWhere('key', 'umum')['registrants'] ?? 0 }} camaba.
                - Prestasi: {{ $registrationPathReports->firstWhere('key', 'prestasi')['registrants'] ?? 0 }} camaba.
                - Undangan: {{ $registrationPathReports->firstWhere('key', 'undangan')['registrants'] ?? 0 }} camaba.

                Total pembayaran valid: Rp{{ number_format($totalPaid, 0, ',', '.') }}.
                - Pendaftaran: Rp{{ number_format($registrationPaid, 0, ',', '.') }}.
                - Daftar ulang: Rp{{ number_format($reRegistrationPaid, 0, ',', '.') }}.

                Demikian update sementara yang dapat kami sampaikan. Terima kasih.`); Swal.fire({title: 'Narasi disalin', text: 'Teks laporan PMB sudah disalin ke clipboard.', icon: 'success', confirmButtonColor: '#003B82'})"
                        class="mt-5 w-full rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                    Copy Narasi Laporan
                </button>
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