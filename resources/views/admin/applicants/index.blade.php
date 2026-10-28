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

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Camaba', 'value' => $totalApplicants, 'desc' => 'Semua pendaftar'],
            ['label' => 'Biodata Lengkap', 'value' => $biodataComplete, 'desc' => 'Siap proses lanjutan'],
            ['label' => 'Belum Lengkap', 'value' => $incompleteApplicants, 'desc' => 'Perlu follow up'],
            ['label' => 'Daftar Ulang Valid', 'value' => $validReRegistrations, 'desc' => 'Siap sinkron akademik'],
        ] as $card)
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight text-polmind-blue">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Registration Path Stats --}}
    <div class="grid gap-5 md:grid-cols-3">
        @foreach([
            [
                'label' => 'Jalur Umum',
                'value' => $registrationPathStats['umum'] ?? 0,
                'desc' => 'Pendaftar jalur umum',
                'class' => 'text-polmind-blue',
            ],
            [
                'label' => 'Jalur Prestasi',
                'value' => $registrationPathStats['prestasi'] ?? 0,
                'desc' => 'Pendaftar jalur prestasi',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Jalur Undangan',
                'value' => $registrationPathStats['undangan'] ?? 0,
                'desc' => 'Pendaftar jalur undangan',
                'class' => 'text-purple-700',
            ],
        ] as $card)
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
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

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Export Excel',
                        text: 'Fitur export akan kita sambungkan setelah modul laporan aktif.',
                        icon: 'info',
                        confirmButtonColor: '#003B82'
                    })"
                    class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Export Excel
            </button>
        </div>

        <form action="{{ url('/admin/applicants') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
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
                    <option value="umum" @selected(request('registration_path') === 'umum')>
                        Umum
                    </option>
                    <option value="prestasi" @selected(request('registration_path') === 'prestasi')>
                        Prestasi
                    </option>
                    <option value="undangan" @selected(request('registration_path') === 'undangan')>
                        Undangan
                    </option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-6">
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
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
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
            {{ $applicants->links() }}
        </div>
    </div>

</div>
@endsection