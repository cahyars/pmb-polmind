@extends('layouts.admin')

@section('title', 'Data Camaba')
@section('page_title', 'Data Camaba')
@section('page_subtitle', 'Kelola data calon mahasiswa baru Politeknik Mitra Industri.')

@section('content')
<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Summary Cards: angka utama + rincian dua sisi --}}
    @php
        $number = fn ($value) => number_format((int) $value, 0, ',', '.');
        $accepted = (int) ($selectionStats['diterima'] ?? 0);
        $reserve = (int) ($selectionStats['cadangan'] ?? 0);
    @endphp
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Total Camaba', 'value' => $totalApplicants, 'icon' => 'users', 'filter' => null,
                'class' => 'bg-polmind-blue text-white', 'panel' => 'bg-white/10 divide-white/20', 'hover' => 'hover:bg-white/15', 'active' => 'bg-white/20 ring-2 ring-white/70',
                'parts' => [['Belum Bayar', $totalApplicants - $paidApplicants, 'belum_bayar'], ['Sudah Bayar', $paidApplicants, 'sudah_bayar']],
            ],
            [
                'label' => 'Isi Biodata', 'value' => $biodataComplete, 'icon' => 'pencil-square', 'filter' => 'lengkap',
                'class' => 'bg-sky-700 text-white', 'panel' => 'bg-white/10 divide-white/20', 'hover' => 'hover:bg-white/15', 'active' => 'bg-white/20 ring-2 ring-white/70',
                'parts' => [['Belum Lengkap', $incompleteApplicants, 'belum_lengkap'], ['Lengkap', $biodataComplete, 'lengkap']],
            ],
            [
                'label' => 'Lolos Seleksi', 'value' => $accepted + $reserve, 'icon' => 'badge-check', 'filter' => 'lolos',
                'class' => 'bg-polmind-yellow text-polmind-blue-dark', 'panel' => 'bg-polmind-blue-dark/10 divide-polmind-blue-dark/15', 'hover' => 'hover:bg-polmind-blue-dark/10', 'active' => 'bg-polmind-blue-dark/15 ring-2 ring-polmind-blue-dark/50',
                'parts' => [['Diterima', $accepted, 'diterima'], ['Cadangan', $reserve, 'cadangan']],
            ],
            [
                'label' => 'Daftar Ulang', 'value' => $validReRegistrations, 'icon' => 'cap', 'filter' => 'daftar_ulang',
                'class' => 'bg-teal-700 text-white', 'panel' => 'bg-white/10 divide-white/20', 'hover' => 'hover:bg-white/15', 'active' => 'bg-white/20 ring-2 ring-white/70',
                'parts' => [['Sudah', $validReRegistrations, 'daftar_ulang'], ['Belum', $pendingReRegistrations, 'belum_daftar_ulang']],
            ],
        ] as $card)
            @php
                // Angka yang diklik membuka daftar camaba tersaring, langsung ke tabel.
                $filterUrl = fn (?string $filter) => route('admin.applicants.index', array_filter(['summary' => $filter])) . '#daftar-camaba';
                $bigActive = $card['filter'] ? $summary === $card['filter'] : false;
            @endphp
            <div class="relative overflow-hidden rounded-xl p-5 shadow-sm {{ $card['class'] }}">
                <x-icon :name="$card['icon']" class="pointer-events-none absolute -bottom-5 -right-4 h-28 w-28 opacity-10" stroke-width="1.2" />

                <p class="relative text-sm font-semibold opacity-90">{{ $card['label'] }}</p>
                <a href="{{ $filterUrl($card['filter']) }}"
                   title="{{ $card['filter'] ? 'Tampilkan camaba ' . mb_strtolower(\App\Http\Controllers\Admin\ApplicantController::SUMMARY_FILTERS[$card['filter']]) : 'Tampilkan semua camaba' }}"
                   class="relative -mx-1 mt-1 inline-block rounded-md px-1 text-4xl font-bold leading-tight tabular-nums underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-current">{{ $number($card['value']) }}</a>

                <div class="relative mt-4 grid grid-cols-2 gap-1 rounded-lg p-1 {{ $card['panel'] }}">
                    @foreach($card['parts'] as [$partLabel, $partValue, $partFilter])
                        <a href="{{ $filterUrl($partFilter) }}"
                           @if($summary === $partFilter) aria-current="true" @endif
                           title="Tampilkan camaba {{ mb_strtolower(\App\Http\Controllers\Admin\ApplicantController::SUMMARY_FILTERS[$partFilter]) }}"
                           class="block rounded-md px-3 py-1.5 transition focus-visible:outline-2 focus-visible:outline-current {{ $summary === $partFilter ? $card['active'] : $card['hover'] }}">
                            <span class="block text-lg font-bold leading-tight tabular-nums">{{ $number($partValue) }}</span>
                            <span class="block text-xs font-medium opacity-80">{{ $partLabel }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Registration Path Stats --}}
    <div class="grid gap-5 md:grid-cols-3">
        @foreach(\App\Models\RegistrationPath::ordered()
            ->filter(fn ($path) => $path->is_active || ($registrationPathStats[$path->code] ?? 0) > 0)
            ->map(fn ($path) => [
                'label' => 'Jalur ' . $path->name,
                'value' => $registrationPathStats[$path->code] ?? 0,
                'class' => \App\Models\RegistrationPath::textClass($path->code),
                'badge' => \App\Models\RegistrationPath::badgeClass($path->code),
            ]) as $card)
            @php
                $share = $totalApplicants > 0 ? round($card['value'] / $totalApplicants * 100) : 0;
            @endphp
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $card['badge'] }}">
                    <x-icon name="bookmark" class="h-6 w-6" stroke-width="1.8" />
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="truncate text-sm font-semibold text-slate-600">{{ $card['label'] }}</p>
                        <p class="text-2xl font-bold tabular-nums {{ $card['class'] }}">{{ $number($card['value']) }}</p>
                    </div>

                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 {{ $card['class'] }}" aria-hidden="true">
                        <div class="h-full rounded-full bg-current" style="width: {{ $share }}%"></div>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">{{ $share }}% dari total camaba</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filter Section --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    Filter Data Camaba
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cari data pendaftar berdasarkan nama, NIK, NISN, nomor pendaftaran, atau sekolah asal.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.applicants.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-polmind-blue px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:brightness-110">
                    <x-icon name="user-plus" class="h-5 w-5" stroke-width="2" />
                    Tambah Pendaftar
                </a>

                <a href="{{ route('admin.reports.export-applicants', array_filter([
                        'admission_wave' => request('wave'),
                        'study_program' => request('study_program'),
                        'registration_path' => request('registration_path'),
                        'source' => request('source'),
                    ])) }}"
                   class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Export Excel
                </a>
            </div>
        </div>

        <form action="{{ url('/admin/applicants') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-7">
            @if($summary)
                <input type="hidden" name="summary" value="{{ $summary }}">
            @endif
            <div class="xl:col-span-2">
                <label class="text-sm font-semibold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, NIK, NISN, No. Pendaftaran, atau Sekolah"
                       class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Gelombang</label>
                <select name="wave"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Gelombang</option>
                    @foreach($waves as $wave)
                        <option value="{{ $wave->id }}" @selected(request('wave') == $wave->id)>
                            {{ $wave->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }} - {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach([
                        'registrasi_awal' => 'Registrasi Awal',
                        'biodata_lengkap' => 'Biodata Lengkap',
                        'berkas_menunggu_verifikasi' => 'Berkas Menunggu',
                        'pembayaran_pendaftaran_valid' => 'Pembayaran Valid',
                        'diterima' => 'Diterima',
                        'daftar_ulang_valid' => 'Daftar Ulang Valid',
                    ] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Jalur Pendaftaran</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    @foreach(\App\Models\RegistrationPath::ordered() as $pathOption)
                        <option value="{{ $pathOption->code }}" @selected(request('registration_path') === $pathOption->code)>{{ $pathOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Sumber Informasi</label>
                <select name="source"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Sumber</option>
                    @foreach($informationSources as $sourceName)
                        <option value="{{ $sourceName }}" @selected(request('source') === $sourceName)>{{ $sourceName }}</option>
                    @endforeach
                    <option value="{{ \App\Models\InformationSource::NOT_FILLED_KEY }}" @selected(request('source') === \App\Models\InformationSource::NOT_FILLED_KEY)>{{ \App\Models\InformationSource::NOT_FILLED_LABEL }}</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-7">
                <button type="submit"
                        class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/applicants') }}"
                   class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div id="daftar-camaba" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white shadow-sm">
        @if($summary)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-t-xl border-b border-blue-100 bg-blue-50 px-6 py-3 text-sm">
                <p class="text-slate-700">
                    Menampilkan camaba: <span class="font-bold text-polmind-blue">{{ \App\Http\Controllers\Admin\ApplicantController::SUMMARY_FILTERS[$summary] }}</span>
                </p>
                <a href="{{ route('admin.applicants.index', request()->except(['summary', 'page'])) }}#daftar-camaba"
                   class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-polmind-blue hover:bg-blue-100">
                    <x-icon name="close" class="h-3.5 w-3.5" stroke-width="2" />
                    Hapus filter ini
                </a>
            </div>
        @endif
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    Daftar Calon Mahasiswa
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data sudah diambil dari database PMB.
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="rounded-full bg-blue-100 px-3 py-1 text-polmind-blue">
                    {{ $applicants->total() }} Data
                </span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">
                    Real Database
                </span>
            </div>
        </div>

        @php
            // Ringkasan tampilan per camaba, dipakai tabel (desktop) dan kartu (HP/tablet).
            $avatarColors = ['bg-polmind-blue', 'bg-red-800', 'bg-emerald-800', 'bg-indigo-800', 'bg-amber-700', 'bg-slate-700', 'bg-purple-800', 'bg-teal-800'];

            $present = function ($applicant) use ($avatarColors) {
                $accepted = $applicant->selection_status === 'diterima';
                $decided = in_array($applicant->selection_status, ['diterima', 'cadangan', 'ditolak'], true);

                $badges = [];

                if (! $decided) {
                    $badges[] = match ($applicant->payment_status) {
                        'valid' => $applicant->registrationInvoice?->isWaived()
                            ? ['Formulir Gratis', 'bg-green-100 text-green-700']
                            : ['Formulir Lunas', 'bg-green-100 text-green-700'],
                        'menunggu_verifikasi' => ['Bayar Diverifikasi', 'bg-yellow-100 text-yellow-800'],
                        'ditolak' => ['Bukti Bayar Ditolak', 'bg-red-100 text-red-700'],
                        default => ['Belum Bayar (Peminat)', 'bg-red-100 text-red-700'],
                    };
                    $badges[] = $applicant->registration_status === 'biodata_lengkap'
                        ? ['Biodata Lengkap', 'bg-green-100 text-green-700']
                        : ['Biodata Belum Lengkap', 'bg-red-100 text-red-700'];
                }

                $badges[] = match ($applicant->selection_status) {
                    'diterima' => ['Lulus', 'bg-green-100 text-green-700'],
                    'cadangan' => ['Cadangan', 'bg-yellow-100 text-yellow-800'],
                    'ditolak' => ['Tidak Lulus', 'bg-red-100 text-red-700'],
                    default => ['Belum Diseleksi', 'bg-amber-100 text-amber-800'],
                };

                if ($accepted) {
                    $badges[] = match ($applicant->re_registration_status) {
                        'daftar_ulang_valid' => ['Sudah Daftar Ulang', 'bg-green-100 text-green-700'],
                        'menunggu_verifikasi' => ['Daftar Ulang Diverifikasi', 'bg-yellow-100 text-yellow-800'],
                        default => ['Belum Daftar Ulang', 'bg-red-100 text-red-700'],
                    };
                }

                if ($applicant->nim) {
                    $badges[] = ['Sudah Memiliki NIM', 'bg-green-100 text-green-700'];
                }

                $phone = preg_replace('/\D/', '', (string) $applicant->phone);

                return [
                    'url' => route('admin.applicants.show', $applicant->registration_number),
                    'photo' => $applicant->photoDocument ? route('admin.documents.file', $applicant->photoDocument) : null,
                    'initials' => collect(preg_split('/\s+/', trim($applicant->full_name)))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''),
                    'color' => $avatarColors[crc32($applicant->registration_number) % count($avatarColors)],
                    'wave' => trim(($applicant->pmbYear?->name ?? '') . ' - ' . ($applicant->admissionWave?->name ?? '-'), ' -'),
                    'pathClass' => $applicant->registration_path_label . ($applicant->classType ? ' - ' . $applicant->classType->name : ''),
                    'programs' => array_values(array_filter([
                        $applicant->studyProgram ? ['label' => $applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name, 'accepted' => $accepted] : null,
                        $applicant->secondStudyProgram ? ['label' => $applicant->secondStudyProgram->degree . ' - ' . $applicant->secondStudyProgram->name, 'accepted' => false] : null,
                    ])),
                    'badges' => $badges,
                    'whatsapp' => $phone ? 'https://wa.me/' . (str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone) : null,
                    'deletable' => $applicant->isDeletable(),
                ];
            };
        @endphp

        @if($applicants->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-semibold text-slate-600">Data camaba tidak ditemukan.</p>
                <p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci atau reset filter.</p>
            </div>
        @else
            {{-- Desktop (>= 1280px): tabel dengan lebar kolom tetap, tanpa scroll horizontal --}}
            <table class="hidden w-full table-fixed text-left text-sm xl:table">
                <colgroup>
                    <col class="w-[52px]">
                    <col class="w-[84px]">
                    <col class="w-[16%]">
                    <col class="w-[14%]">
                    <col class="w-[19%]">
                    <col class="w-[16%]">
                    <col>
                    <col class="w-[88px]">
                </colgroup>
                <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                    <tr>
                        <th class="px-4 py-3.5">No</th>
                        <th class="px-4 py-3.5">Foto</th>
                        <th class="px-3 py-3.5">Nama &amp; Kode Pendaftar</th>
                        <th class="px-3 py-3.5">Jalur Pendaftaran</th>
                        <th class="px-3 py-3.5">Program Studi</th>
                        <th class="px-3 py-3.5">Status Pendaftar</th>
                        <th class="px-3 py-3.5">Kontak</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-zebra bg-white align-top">
                    @foreach($applicants as $applicant)
                        @php $row = $present($applicant); @endphp
                        <tr>
                            <td class="px-4 py-3.5 text-slate-500">{{ $applicants->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3.5">
                                @include('admin.applicants.partials.avatar', ['row' => $row, 'size' => 'h-16 w-14 text-lg'])
                            </td>
                            <td class="px-3 py-3.5">
                                <a href="{{ $row['url'] }}" class="font-semibold uppercase leading-5 text-polmind-blue hover:underline">{{ $applicant->full_name }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ $applicant->registration_number }}</p>
                            </td>
                            <td class="px-3 py-3.5">
                                <p class="font-semibold leading-5 text-slate-800">{{ $row['wave'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $row['pathClass'] }}</p>
                            </td>
                            <td class="px-3 py-3.5">
                                @include('admin.applicants.partials.programs', ['row' => $row])
                            </td>
                            <td class="px-3 py-3.5">
                                @include('admin.applicants.partials.badges', ['row' => $row])
                            </td>
                            <td class="px-3 py-3.5">
                                @include('admin.applicants.partials.contacts', ['applicant' => $applicant, 'row' => $row])
                            </td>
                            <td class="px-4 py-3.5">
                                @include('admin.applicants.partials.actions', ['applicant' => $applicant, 'row' => $row])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- HP & tablet: kartu per camaba --}}
            <ul class="divide-y divide-slate-100 xl:hidden">
                @foreach($applicants as $applicant)
                    @php $row = $present($applicant); @endphp
                    <li class="p-4 sm:p-5">
                        <div class="flex gap-4">
                            @include('admin.applicants.partials.avatar', ['row' => $row, 'size' => 'h-16 w-14 text-lg'])

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ $row['url'] }}" class="font-semibold uppercase leading-5 text-polmind-blue hover:underline">{{ $applicant->full_name }}</a>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $applicant->registration_number }}</p>
                                    </div>
                                    @include('admin.applicants.partials.actions', ['applicant' => $applicant, 'row' => $row])
                                </div>

                                <p class="mt-2 text-xs text-slate-600">
                                    <span class="font-semibold text-slate-800">{{ $row['wave'] }}</span> · {{ $row['pathClass'] }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div class="min-w-0">
                                <p class="mb-1.5 text-[11px] font-medium text-slate-400">Program Studi</p>
                                @include('admin.applicants.partials.programs', ['row' => $row])
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1.5 text-[11px] font-medium text-slate-400">Status</p>
                                @include('admin.applicants.partials.badges', ['row' => $row])
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1.5 text-[11px] font-medium text-slate-400">Kontak</p>
                                @include('admin.applicants.partials.contacts', ['applicant' => $applicant, 'row' => $row])
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Pagination --}}
        <div class="border-t border-slate-200 p-6">
            <x-pagination :paginator="$applicants" />
        </div>
    </div>

</div>
@endsection