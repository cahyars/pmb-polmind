@extends('layouts.admin')

@section('title', 'Laporan PMB')
@section('page_title', 'Laporan PMB')
@section('page_subtitle', 'Rekapitulasi pendaftaran, seleksi, pembayaran, daftar ulang, dan integrasi SIAKAD.')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="rounded-xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold text-blue-100">Laporan PMB Politeknik Mitra Industri</p>
                <h1 class="mt-2 text-3xl font-bold">Monitoring {{ $selectedPmbYear?->name ?? 'PMB' }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-blue-100">
                    Total pendaftar sementara {{ $totalApplicants }} camaba dari target {{ $targetApplicants }}.
                    Progress target saat ini sekitar {{ $targetProgress }}%.
                </p>
            </div>

            <div class="rounded-lg bg-white/10 p-5 lg:w-72">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-blue-100">Progress Target</p>
                    <p class="text-xl font-bold text-polmind-yellow">{{ $targetProgress }}%</p>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ min($targetProgress, 100) }}%"></div>
                </div>

                <p class="mt-3 text-xs text-blue-100">
                    {{ $totalApplicants }} / {{ $targetApplicants }} pendaftar
                </p>
            </div>

            <div class="flex flex-col gap-3 lg:w-64">
                <a href="{{ route('admin.reports.export-applicants', request()->query()) }}"
                class="inline-flex justify-center rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Export Data Camaba
                </a>

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

            Total pembayaran valid: Rp{{ number_format($totalPaid, 0, ',', '.') }}.

            Demikian update sementara yang dapat kami sampaikan. Terima kasih.`); Swal.fire({title: 'Narasi disalin', text: 'Teks laporan PMB sudah disalin ke clipboard.', icon: 'success', confirmButtonColor: '#003B82'})"
                        class="inline-flex justify-center rounded-lg border border-white/30 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20">
                    Copy Narasi
                </button>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-base font-semibold text-slate-900">Filter Laporan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Gunakan filter untuk melihat laporan berdasarkan tanggal, prodi, kelas, jalur, seleksi, daftar ulang, dan status sinkron.
            </p>
        </div>

        <form action="{{ route('admin.reports.index') }}" method="GET" class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Tanggal Mulai</label>
                <input type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Tanggal Akhir</label>
                <input type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }} - {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Kelas</label>
                <select name="class_type"
                        class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Kelas</option>
                    @foreach($classTypes as $classType)
                        <option value="{{ $classType->id }}" @selected(request('class_type') == $classType->id)>
                            {{ $classType->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Jalur</label>
                <select name="registration_path"
                        class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    <option value="umum" @selected(request('registration_path') === 'umum')>Umum</option>
                    <option value="prestasi" @selected(request('registration_path') === 'prestasi')>Prestasi</option>
                    <option value="undangan" @selected(request('registration_path') === 'undangan')>Undangan</option>
                </select>
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Status Seleksi</label>
                <select name="selection_status"
                        class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Seleksi</option>
                    <option value="belum_diseleksi" @selected(request('selection_status') === 'belum_diseleksi')>Belum Diseleksi</option>
                    <option value="diterima" @selected(request('selection_status') === 'diterima')>Diterima</option>
                    <option value="cadangan" @selected(request('selection_status') === 'cadangan')>Cadangan</option>
                    <option value="ditolak" @selected(request('selection_status') === 'ditolak')>Ditolak</option>
                </select>
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Status DU</label>
                <select name="re_registration_status"
                        class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua DU</option>
                    <option value="belum_daftar_ulang" @selected(request('re_registration_status') === 'belum_daftar_ulang')>Belum Daftar Ulang</option>
                    <option value="menunggu_verifikasi" @selected(request('re_registration_status') === 'menunggu_verifikasi')>Menunggu Verifikasi</option>
                    <option value="daftar_ulang_valid" @selected(request('re_registration_status') === 'daftar_ulang_valid')>Daftar Ulang Valid</option>
                    <option value="ditolak" @selected(request('re_registration_status') === 'ditolak')>Ditolak</option>
                    <option value="lewat_batas" @selected(request('re_registration_status') === 'lewat_batas')>Lewat Batas</option>
                </select>
            </div>

            <div class="min-w-0">
                <label class="text-sm font-semibold text-slate-700">Sync SIAKAD</label>
                <select name="sync_status"
                        class="mt-2 w-full min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Sync</option>
                    <option value="belum_siap" @selected(request('sync_status') === 'belum_siap')>Belum Siap</option>
                    <option value="siap_sinkron" @selected(request('sync_status') === 'siap_sinkron')>Siap Sinkron</option>
                    <option value="proses_sinkron" @selected(request('sync_status') === 'proses_sinkron')>Proses Sinkron</option>
                    <option value="sudah_sinkron" @selected(request('sync_status') === 'sudah_sinkron')>Sudah Sinkron</option>
                    <option value="gagal_sinkron" @selected(request('sync_status') === 'gagal_sinkron')>Gagal Sinkron</option>
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-3 sm:col-span-2 xl:col-span-4">
                <button type="submit"
                        class="whitespace-nowrap rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan
                </button>

                <a href="{{ route('admin.reports.index') }}"
                class="whitespace-nowrap rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>

                <a href="{{ route('admin.reports.export-applicants', request()->query()) }}"
                class="whitespace-nowrap rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-green-900/20 transition hover:bg-green-700">
                    Export CSV
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
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Registration Path Reports --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Rekap Jalur Pendaftaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Distribusi camaba berdasarkan jalur umum, prestasi, dan undangan.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
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

                <div class="rounded-lg border p-5 {{ $pathClass }}">
                    <p class="text-sm font-semibold">Jalur {{ $path['label'] }}</p>
                    <p class="mt-3 text-[28px] font-bold leading-tight">{{ $path['registrants'] }}</p>
                    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-lg bg-white/70 px-1 py-2">
                            <p class="text-[11px] font-semibold">Diterima</p>
                            <p class="mt-1 text-xl font-bold">{{ $path['accepted'] }}</p>
                        </div>
                        <div class="rounded-lg bg-white/70 px-1 py-2">
                            <p class="text-[11px] font-semibold">DU Valid</p>
                            <p class="mt-1 text-xl font-bold">{{ $path['re_registered'] }}</p>
                        </div>
                        <div class="rounded-lg bg-white/70 px-1 py-2">
                            <p class="text-[11px] font-semibold">Sinkron</p>
                            <p class="mt-1 text-xl font-bold">{{ $path['synced'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Baris 1: Funnel | Ringkasan eksekutif & sumber informasi --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="min-w-0 xl:col-span-2">
            {{-- Funnel Chart --}}
            <div class="h-full min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Funnel PMB</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Alur progres camaba dari registrasi awal sampai sinkron SIAKAD.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                        Real Database
                    </span>
                </div>

                {{-- HP: bar horizontal --}}
                <div class="mt-6 space-y-4 rounded-lg bg-slate-50 p-4 md:hidden">
                    @foreach($funnel as $item)
                        <div>
                            <div class="flex items-baseline justify-between gap-3 text-xs">
                                <p class="font-semibold text-slate-800">{{ $item['label'] }}</p>
                                <p class="shrink-0 text-slate-500">
                                    {{ $item['value'] }} camaba · <span class="font-bold text-polmind-blue">{{ $item['percent'] }}%</span>
                                </p>
                            </div>
                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $item['percent'] > 0 ? max($item['percent'], 3) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Tablet ke atas: kolom vertikal mengikuti lebar kartu --}}
                <div class="mt-8 hidden items-end gap-2 rounded-lg bg-slate-50 p-4 md:flex lg:gap-3 xl:p-6">
                    @foreach($funnel as $item)
                        @php
                            $barHeight = $item['percent'] > 0 ? max($item['percent'], 8) : 0;
                        @endphp

                        <div class="flex min-w-0 flex-1 flex-col items-center justify-end">
                            <div class="mb-3 text-center">
                                <p class="text-sm font-bold text-polmind-blue">
                                    {{ $item['percent'] }}%
                                </p>
                                <p class="mt-1 text-[11px] font-semibold text-slate-500">
                                    {{ $item['value'] }} camaba
                                </p>
                            </div>

                            <div class="flex h-48 w-full items-end justify-center rounded-lg bg-white p-2 shadow-sm xl:h-56">
                                <div class="w-full max-w-[56px] rounded-t-lg bg-polmind-blue transition-all"
                                    style="height: {{ $barHeight }}%"></div>
                            </div>

                            <div class="mt-3 min-h-[40px] text-center">
                                <p class="text-[11px] font-bold leading-4 text-slate-800 [overflow-wrap:anywhere]">
                                    {{ $item['label'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid min-w-0 gap-6 md:grid-cols-2 xl:flex xl:flex-col">
                {{-- Executive Summary --}}
                <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Ringkasan Eksekutif</h2>

                    <div class="mt-5 space-y-4 text-sm leading-6 text-slate-700">
                        <p>
                            Total pendaftar saat ini
                            <span class="font-bold text-polmind-blue">{{ $totalApplicants }} camaba</span>
                            dari target {{ $targetApplicants }}.
                        </p>

                        <p>
                            Camaba yang sudah dinyatakan diterima:
                            <span class="font-bold text-purple-700">{{ $acceptedApplicants }}</span>.
                        </p>

                        <p>
                            Daftar ulang valid:
                            <span class="font-bold text-green-700">{{ $reRegistered }}</span>,
                            dan siap sinkron ke SIAKAD:
                            <span class="font-bold text-purple-700">{{ $readySync }}</span>.
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
                            class="mt-5 w-full rounded-lg border border-polmind-blue bg-white px-5 py-3 text-sm font-semibold text-polmind-blue transition hover:bg-blue-50">
                        Copy Narasi Laporan
                    </button>
                </div>

            {{-- Source Information --}}
            <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Sumber Informasi</h2>

                <div class="mt-5 space-y-4">
                    @forelse($sourceReports as $source)
                        @php
                            $percent = $totalApplicants > 0
                                ? round(($source->total / $totalApplicants) * 100)
                                : 0;
                        @endphp

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ $source->source_information }}
                                </p>
                                <p class="text-xs font-bold text-polmind-blue">
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
        </div>
    </div>

    {{-- Baris 2: Sebaran prodi | Rekap kelas --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="min-w-0 xl:col-span-2">
            {{-- Sebaran Prodi --}}
            <div class="h-full min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Sebaran Program Studi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Rekap pendaftar, diterima, daftar ulang valid, dan siap sinkron per prodi.
                </p>

                {{-- HP: kartu per prodi --}}
                <div class="mt-6 space-y-3 md:hidden">
                    @foreach($programReports as $program)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-polmind-blue">{{ $program['code'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $program['name'] }}</p>
                                </div>
                                <p class="shrink-0 text-xs text-slate-500">Kuota <span class="font-bold text-slate-800">{{ $program['quota'] }}</span></p>
                            </div>

                            <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                                @foreach([
                                    ['label' => 'Pendaftar', 'value' => $program['registrants'], 'class' => 'text-slate-800'],
                                    ['label' => 'Diterima', 'value' => $program['accepted'], 'class' => 'text-slate-800'],
                                    ['label' => 'DU Valid', 'value' => $program['re_registered'], 'class' => 'text-green-700'],
                                    ['label' => 'Siap Sync', 'value' => $program['ready_sync'], 'class' => 'text-purple-700'],
                                    ['label' => 'Sudah Sync', 'value' => $program['synced'], 'class' => 'text-green-700'],
                                ] as $stat)
                                    <div class="rounded-md bg-white p-2">
                                        <dt class="text-slate-500">{{ $stat['label'] }}</dt>
                                        <dd class="mt-0.5 font-bold {{ $stat['class'] }}">{{ $stat['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <div class="mt-3 flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($program['quota_percent'], 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-polmind-blue">{{ $program['quota_percent'] }}% kuota</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Tablet ke atas: tabel rapat yang pas selebar kartu --}}
                <div class="mt-6 hidden md:block">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                            <tr>
                                <th class="px-3 py-3">Prodi</th>
                                <th class="px-2 py-3 text-center">Kuota</th>
                                <th class="px-2 py-3 text-center">Pendaftar</th>
                                <th class="px-2 py-3 text-center">Diterima</th>
                                <th class="px-2 py-3 text-center">DU Valid</th>
                                <th class="px-2 py-3 text-center">Siap Sync</th>
                                <th class="px-2 py-3 text-center">Sudah Sync</th>
                                <th class="px-3 py-3">Kuota Terisi</th>
                            </tr>
                        </thead>

                        <tbody class="table-zebra">
                            @foreach($programReports as $program)
                                <tr>
                                    <td class="px-3 py-3">
                                        <p class="font-bold text-polmind-blue">{{ $program['code'] }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $program['name'] }}</p>
                                    </td>
                                    <td class="px-2 py-3 text-center font-medium text-slate-700">{{ $program['quota'] }}</td>
                                    <td class="px-2 py-3 text-center font-medium text-slate-700">{{ $program['registrants'] }}</td>
                                    <td class="px-2 py-3 text-center font-medium text-slate-700">{{ $program['accepted'] }}</td>
                                    <td class="px-2 py-3 text-center font-medium text-green-700">{{ $program['re_registered'] }}</td>
                                    <td class="px-2 py-3 text-center font-medium text-purple-700">{{ $program['ready_sync'] }}</td>
                                    <td class="px-2 py-3 text-center font-medium text-green-700">{{ $program['synced'] }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-full min-w-12 max-w-28 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($program['quota_percent'], 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-polmind-blue">
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
        </div>

        {{-- Rekap Kelas (pendaftar, diterima, DU valid, sinkron per kelas) --}}
        <div class="h-full min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Rekap Kelas</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Sebaran camaba per jenis kelas.</p>

            <div class="mt-5 space-y-4">
                @forelse($classReports as $class)
                    @php
                        $classShare = $totalApplicants > 0 ? round($class['registrants'] / $totalApplicants * 100) : 0;
                    @endphp
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="font-semibold text-slate-900">{{ $class['name'] }}</p>
                            <p class="text-xs text-slate-500"><span class="text-xl font-bold text-polmind-blue">{{ $class['registrants'] }}</span> pendaftar</p>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $classShare }}%"></div>
                        </div>
                        <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-md bg-white px-1 py-1.5">
                                <dt class="text-slate-500">Diterima</dt>
                                <dd class="mt-0.5 font-bold text-purple-700">{{ $class['accepted'] }}</dd>
                            </div>
                            <div class="rounded-md bg-white px-1 py-1.5">
                                <dt class="text-slate-500">DU Valid</dt>
                                <dd class="mt-0.5 font-bold text-green-700">{{ $class['re_registered'] }}</dd>
                            </div>
                            <div class="rounded-md bg-white px-1 py-1.5">
                                <dt class="text-slate-500">Sinkron</dt>
                                <dd class="mt-0.5 font-bold text-polmind-blue">{{ $class['synced'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @empty
                    <p class="text-sm font-semibold text-slate-500">Belum ada data kelas.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Baris 3: Ringkasan pembayaran | Pembayaran terbaru --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="min-w-0 xl:col-span-2">
            {{-- Pembayaran --}}
            <div class="h-full min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Ringkasan Pembayaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Rekap pembayaran valid, menunggu validasi, dan ditolak.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    <div class="rounded-lg bg-green-50 p-5">
                        <p class="text-sm font-semibold text-green-700">Total Pembayaran Valid</p>
                        <p class="mt-3 text-2xl font-bold text-green-700">
                            Rp{{ number_format($totalPaid, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs text-green-700">{{ $validPayments }} pembayaran valid</p>
                    </div>

                    <div class="rounded-lg bg-blue-50 p-5">
                        <p class="text-sm font-semibold text-polmind-blue">Pendaftaran</p>
                        <p class="mt-3 text-2xl font-bold text-polmind-blue">
                            Rp{{ number_format($registrationPaid, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Nominal dari invoice pendaftaran</p>
                    </div>

                    <div class="rounded-lg bg-purple-50 p-5">
                        <p class="text-sm font-semibold text-purple-700">Daftar Ulang</p>
                        <p class="mt-3 text-2xl font-bold text-purple-700">
                            Rp{{ number_format($reRegistrationPaid, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Nominal dari invoice daftar ulang</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs font-semibold text-slate-500">Menunggu Validasi</p>
                        <p class="mt-2 text-2xl font-bold text-yellow-700">{{ $waitingPayments }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs font-semibold text-slate-500">Valid</p>
                        <p class="mt-2 text-2xl font-bold text-green-700">{{ $validPayments }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs font-semibold text-slate-500">Ditolak</p>
                        <p class="mt-2 text-2xl font-bold text-red-700">{{ $rejectedPayments }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex h-full min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Pembayaran Terbaru</h2>

            <ul class="mt-4 divide-y divide-slate-100">
                @forelse($latestPayments as $payment)
                    <li class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $payment->applicant?->full_name ?? '-' }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $payment->payment_number }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold text-green-700">Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                    </li>
                @empty
                    <li class="text-sm font-semibold text-slate-500">Belum ada pembayaran.</li>
                @endforelse
            </ul>

            <a href="{{ route('admin.payments.index') }}" class="mt-auto pt-4 text-xs font-semibold text-polmind-blue hover:underline">Lihat semua pembayaran →</a>
        </div>
    </div>

    {{-- Baris 4: Status seleksi | Status sinkron | Pendaftar terbaru --}}
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach([
            ['title' => 'Status Seleksi', 'items' => $selectionReports, 'bar' => 'bg-purple-600'],
            ['title' => 'Status Sinkron SIAKAD', 'items' => $syncReports, 'bar' => 'bg-polmind-blue'],
        ] as $statusCard)
            <div class="h-full min-w-0 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">{{ $statusCard['title'] }}</h2>

                <ul class="mt-4 space-y-3.5">
                    @foreach($statusCard['items'] as $item)
                        @php
                            $share = $totalApplicants > 0 ? round($item['total'] / $totalApplicants * 100) : 0;
                        @endphp
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="shrink-0 text-xs text-slate-400"><span class="text-base font-bold text-slate-900">{{ $item['total'] }}</span> · {{ $share }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $statusCard['bar'] }}" style="width: {{ $share }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="flex h-full min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2 xl:col-span-1">
            <h2 class="text-base font-semibold text-slate-900">Pendaftar Terbaru</h2>

            <ul class="mt-4 divide-y divide-slate-100">
                @forelse($latestApplicants as $applicant)
                    <li class="py-2.5 first:pt-0 last:pb-0">
                        <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}" class="flex items-center justify-between gap-3 hover:text-polmind-blue">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-800">{{ $applicant->full_name }}</span>
                                <span class="block text-xs text-slate-500">{{ $applicant->registration_number }}</span>
                            </span>
                            <span class="shrink-0 rounded bg-blue-50 px-1.5 py-0.5 text-[11px] font-semibold text-polmind-blue">
                                {{ $applicant->studyProgram?->code ?? '-' }} · {{ $applicant->classType?->name ?? '-' }}
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="text-sm font-semibold text-slate-500">Belum ada pendaftar.</li>
                @endforelse
            </ul>

            <a href="{{ route('admin.applicants.index') }}" class="mt-auto pt-4 text-xs font-semibold text-polmind-blue hover:underline">Lihat semua camaba →</a>
        </div>
    </div>

</div>
@endsection