@extends('layouts.admin')

@section('title', 'Data Camaba')
@section('page_title', 'Data Camaba')
@section('page_subtitle', 'Kelola data calon mahasiswa baru Politeknik Mitra Industri.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Camaba', 'value' => $totalApplicants, 'desc' => 'Semua pendaftar'],
            ['label' => 'Biodata Lengkap', 'value' => $biodataComplete, 'desc' => 'Siap proses lanjutan'],
            ['label' => 'Belum Lengkap', 'value' => $incompleteApplicants, 'desc' => 'Perlu follow up'],
            ['label' => 'Daftar Ulang Valid', 'value' => $validReRegistrations, 'desc' => 'Siap sinkron akademik'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
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
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter Section --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
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
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Export Excel
            </button>
        </div>

        <form action="{{ url('/admin/applicants') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, NIK, NISN, No. Pendaftaran, atau Sekolah"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Gelombang</label>
                <select name="wave"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Gelombang</option>
                    @foreach($waves as $wave)
                        <option value="{{ $wave->id }}" @selected(request('wave') == $wave->id)>
                            {{ $wave->name }}
                        </option>
                    @endforeach
                </select>
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
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
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
                <label class="text-sm font-bold text-slate-700">Jalur Pendaftaran</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
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
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/applicants') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Daftar Calon Mahasiswa
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data sudah diambil dari database PMB.
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs font-bold">
                <span class="rounded-full bg-blue-100 px-3 py-1 text-polmind-blue">
                    {{ $applicants->total() }} Data
                </span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">
                    Real Database
                </span>
            </div>
        </div>

        @php
            $statusLabels = [
                'registrasi_awal' => 'Registrasi Awal',
                'biodata_lengkap' => 'Biodata Lengkap',
                'berkas_menunggu_verifikasi' => 'Berkas Menunggu',
                'pembayaran_pendaftaran_valid' => 'Pembayaran Valid',
                'diterima' => 'Diterima',
                'daftar_ulang_valid' => 'Daftar Ulang Valid',
            ];

            $statusClasses = [
                'registrasi_awal' => 'bg-slate-100 text-slate-600',
                'biodata_lengkap' => 'bg-blue-100 text-polmind-blue',
                'berkas_menunggu_verifikasi' => 'bg-yellow-100 text-yellow-700',
                'pembayaran_pendaftaran_valid' => 'bg-green-100 text-green-700',
                'diterima' => 'bg-purple-100 text-purple-700',
                'daftar_ulang_valid' => 'bg-emerald-100 text-emerald-700',
            ];

            $registrationPathClasses = [
                'umum' => 'bg-blue-50 text-polmind-blue',
                'prestasi' => 'bg-green-100 text-green-700',
                'undangan' => 'bg-purple-100 text-purple-700',
            ];
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">No. Pendaftaran</th>
                        <th class="px-6 py-4">Nama Camaba</th>
                        <th class="px-6 py-4">NIK / NISN</th>
                        <th class="px-6 py-4">Asal Sekolah</th>
                        <th class="px-6 py-4">Prodi</th>
                        <th class="px-6 py-4">Gelombang</th>
                        <th class="px-6 py-4">Jalur</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($applicants as $applicant)
                        @php
                            $initials = collect(explode(' ', $applicant->full_name))
                                ->map(fn($name) => mb_substr($name, 0, 1))
                                ->take(2)
                                ->implode('');

                            $status = $applicant->registration_status;
                        @endphp

                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $applicant->registration_number }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">
                                            {{ $applicant->full_name }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $applicant->email }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-700">
                                    {{ $applicant->nik ?? '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    NISN: {{ $applicant->nisn ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-700">
                                    {{ $applicant->education?->school_name ?? '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant->education?->major ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $applicant->studyProgram?->code ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <span class="font-semibold text-slate-700">
                                    {{ $applicant->admissionWave?->name ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                @php
                                    $registrationPath = $applicant->registration_path ?? 'umum';
                                @endphp

                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $registrationPathClasses[$registrationPath] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $applicant->registration_path_label }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$status] ?? str_replace('_', ' ', ucfirst($status)) }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-slate-600">
                                {{ $applicant->created_at?->format('d M Y') }}
                            </td>

                            <td class="px-6 py-5 text-right">
                                <a href="{{ url('/admin/applicants/' . $applicant->registration_number) }}"
                                   class="rounded-xl bg-polmind-blue px-4 py-2 text-xs font-bold text-white transition hover:bg-polmind-blue-dark">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
                                    Data camaba tidak ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba reset filter atau jalankan seeder data dummy.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-slate-200 p-6">
            {{ $applicants->links() }}
        </div>
    </div>

</div>
@endsection