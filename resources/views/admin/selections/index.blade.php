@extends('layouts.admin')

@section('title', 'Seleksi Camaba')
@section('page_title', 'Seleksi Camaba')
@section('page_subtitle', 'Kelola keputusan seleksi calon mahasiswa baru.')

@section('content')
@php
    $selectionLabels = [
        'belum_diseleksi' => 'Belum Diseleksi',
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'ditolak' => 'Ditolak',
    ];

    $selectionClasses = [
        'belum_diseleksi' => 'bg-slate-100 text-slate-600',
        'diterima' => 'bg-green-100 text-green-700',
        'cadangan' => 'bg-yellow-100 text-yellow-700',
        'ditolak' => 'bg-red-100 text-red-700',
    ];

    $registrationPathClasses = [
        'umum' => 'bg-blue-50 text-polmind-blue',
        'prestasi' => 'bg-green-100 text-green-700',
        'undangan' => 'bg-purple-100 text-purple-700',
    ];

    $invoiceStatusLabels = [
        'unpaid' => 'Belum Bayar',
        'waiting_verification' => 'Menunggu Verifikasi',
        'partial' => 'Belum Lunas',
        'paid' => 'Lunas',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

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

    {{-- Summary --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Siap Seleksi', 'value' => $readySelection, 'desc' => 'Berkas dan pembayaran valid', 'class' => 'text-polmind-blue'],
            ['label' => 'Diterima', 'value' => $acceptedApplicants, 'desc' => 'Lolos seleksi', 'class' => 'text-green-700'],
            ['label' => 'Cadangan', 'value' => $reserveApplicants, 'desc' => 'Menunggu kuota', 'class' => 'text-yellow-700'],
            ['label' => 'Ditolak', 'value' => $rejectedApplicants, 'desc' => 'Tidak lolos', 'class' => 'text-red-700'],
        ] as $card)
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-base font-semibold text-slate-900">Filter Seleksi</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari dan filter camaba sebelum menentukan keputusan seleksi.
            </p>
        </div>

        <form action="{{ route('admin.selections.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-semibold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, nomor pendaftaran, NIK, sekolah..."
                       class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
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
                <label class="text-sm font-semibold text-slate-700">Jalur</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    <option value="umum" @selected(request('registration_path') === 'umum')>Umum</option>
                    <option value="prestasi" @selected(request('registration_path') === 'prestasi')>Prestasi</option>
                    <option value="undangan" @selected(request('registration_path') === 'undangan')>Undangan</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Kesiapan</label>
                <select name="ready_status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua</option>
                    <option value="ready" @selected(request('ready_status') === 'ready')>Siap Seleksi</option>
                    <option value="not_ready" @selected(request('ready_status') === 'not_ready')>Belum Siap</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Status Seleksi</label>
                <select name="status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach($selectionLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-6">
                <button type="submit"
                        class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.selections.index') }}"
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
                <h2 class="text-base font-semibold text-slate-900">Daftar Seleksi Camaba</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Klik Proses untuk input nilai per komponen tes dan menentukan keputusan. Nilai hanya untuk internal kampus.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                {{ $applicants->total() }} Data
            </span>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">Camaba</th>
                    <th class="hidden px-5 py-3.5 lg:table-cell">Pilihan Prodi</th>
                    <th class="hidden px-5 py-3.5 md:table-cell">Administrasi</th>
                    <th class="hidden px-5 py-3.5 text-right xl:table-cell">Nilai Akhir</th>
                    <th class="px-5 py-3.5">Status Seleksi</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($applicants as $applicant)
                    @php
                        $selection = $applicant->selection;
                        $selectionStatus = $applicant->selection_status ?? 'belum_diseleksi';
                        $registrationPath = $applicant->registration_path ?? 'umum';
                        $isReadyForSelection = $applicant->document_status === 'valid'
                            && $applicant->payment_status === 'valid';
                        $acceptedChoice = $applicant->acceptedChoice();
                        $reRegistrationInvoice = $applicant->reRegistrationInvoice;
                    @endphp

                    <tr class="align-top transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <p class="font-bold text-polmind-blue">{{ $applicant->registration_number }}</p>
                            <p class="mt-1 font-semibold text-slate-800">{{ $applicant->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $applicant->education?->school_name ?? '-' }}</p>
                            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $registrationPathClasses[$registrationPath] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $applicant->registration_path_label }}
                            </span>
                        </td>

                        <td class="hidden px-5 py-4 lg:table-cell">
                            <p class="text-xs text-slate-500">1. <span class="font-semibold text-slate-800">{{ $applicant->studyProgram?->code ?? '-' }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">2. <span class="font-semibold text-slate-800">{{ $applicant->secondStudyProgram?->code ?? '-' }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">{{ $applicant->classType?->name ?? '-' }}</p>
                        </td>

                        <td class="hidden px-5 py-4 md:table-cell">
                            <p class="text-xs font-semibold {{ $applicant->document_status === 'valid' ? 'text-green-700' : 'text-yellow-700' }}">
                                {{ $applicant->document_status === 'valid' ? '✓' : '•' }} Berkas {{ str_replace('_', ' ', $applicant->document_status) }}
                            </p>
                            <p class="mt-1 text-xs font-semibold {{ $applicant->payment_status === 'valid' ? 'text-green-700' : 'text-yellow-700' }}">
                                {{ $applicant->payment_status === 'valid' ? '✓' : '•' }} Bayar {{ str_replace('_', ' ', $applicant->payment_status) }}
                            </p>
                        </td>

                        <td class="hidden px-5 py-4 text-right font-bold text-polmind-blue xl:table-cell">
                            {{ $selection?->final_score ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $selectionClasses[$selectionStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $selectionLabels[$selectionStatus] ?? $selectionStatus }}
                            </span>

                            @if($acceptedChoice)
                                <p class="mt-2 text-xs font-semibold text-green-700">
                                    Pilihan {{ $acceptedChoice }} · {{ $applicant->enrolled_study_program?->code }}
                                </p>
                            @endif

                            @if($reRegistrationInvoice && $selectionStatus === 'diterima')
                                <p class="mt-1 text-xs text-slate-500">
                                    Invoice DU: {{ $invoiceStatusLabels[$reRegistrationInvoice->status] ?? $reRegistrationInvoice->status }}
                                </p>
                            @endif

                            @if($selection?->decided_at)
                                <p class="mt-1 text-xs text-slate-400">{{ $selection->decided_at->format('d M Y H:i') }}</p>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right">
                            @if($isReadyForSelection)
                                <a href="{{ route('admin.selections.show', $applicant->registration_number) }}"
                                   class="inline-flex rounded-lg px-3 py-2 text-xs font-semibold transition {{ $selectionStatus === 'belum_diseleksi' ? 'bg-polmind-blue text-white hover:bg-polmind-blue-dark' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                                    {{ $selectionStatus === 'belum_diseleksi' ? 'Proses' : 'Ubah' }}
                                </a>
                            @else
                                <span class="inline-flex rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500" title="Berkas dan pembayaran pendaftaran harus valid">
                                    Belum Siap
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-slate-600">
                                Data seleksi tidak ditemukan.
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Coba reset filter atau tunggu camaba menyelesaikan administrasi.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-200 p-6">
            {{ $applicants->links() }}
        </div>
    </div>

</div>
@endsection