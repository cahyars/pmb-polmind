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

    $invoiceStatusClasses = [
        'unpaid' => 'bg-yellow-100 text-yellow-700',
        'waiting_verification' => 'bg-yellow-100 text-yellow-700',
        'partial' => 'bg-blue-100 text-polmind-blue',
        'paid' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
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
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Seleksi</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari dan filter camaba sebelum menentukan keputusan seleksi.
            </p>
        </div>

        <form action="{{ route('admin.selections.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, nomor pendaftaran, NIK, sekolah..."
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
                <label class="text-sm font-bold text-slate-700">Kesiapan</label>
                <select name="ready_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua</option>
                    <option value="ready" @selected(request('ready_status') === 'ready')>Siap Seleksi</option>
                    <option value="not_ready" @selected(request('ready_status') === 'not_ready')>Belum Siap</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status Seleksi</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
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
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.selections.index') }}"
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
                <h2 class="text-xl font-black text-polmind-blue">Daftar Seleksi Camaba</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Camaba yang diterima akan otomatis dibuatkan invoice daftar ulang.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $applicants->total() }} Data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1450px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Jalur</th>
                        <th class="px-6 py-4">Prodi</th>
                        <th class="px-6 py-4">Status Administrasi</th>
                        <th class="px-6 py-4">Nilai</th>
                        <th class="px-6 py-4">Status Seleksi</th>
                        <th class="px-6 py-4">Invoice DU</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($applicants as $applicant)
                        @php
                            $selection = $applicant->selection;
                            $selectionStatus = $applicant->selection_status ?? 'belum_diseleksi';
                            $registrationPath = $applicant->registration_path ?? 'umum';

                            $isReadyForSelection = $applicant->document_status === 'valid'
                                && $applicant->payment_status === 'valid';

                            $reRegistrationInvoice = $applicant->reRegistrationInvoice;

                            $hasReRegistrationPaymentActivity = $reRegistrationInvoice
                                && in_array($reRegistrationInvoice->status, ['waiting_verification', 'partial', 'paid']);
                        @endphp

                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $applicant->registration_number }}
                                </p>

                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $applicant->full_name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant->education?->school_name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant->phone ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $registrationPathClasses[$registrationPath] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $applicant->registration_path_label }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $applicant->studyProgram?->code ?? '-' }}
                                </span>

                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $applicant->classType?->name ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <div class="space-y-2">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $applicant->document_status === 'valid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        Berkas: {{ str_replace('_', ' ', $applicant->document_status) }}
                                    </span>

                                    <br>

                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $applicant->payment_status === 'valid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        Bayar: {{ str_replace('_', ' ', $applicant->payment_status) }}
                                    </span>

                                    @if(! $isReadyForSelection)
                                        <p class="mt-2 max-w-52 text-xs leading-5 text-red-600">
                                            Belum bisa diproses seleksi.
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-slate-500">Tes</p>
                                        <p class="mt-1 font-black text-slate-800">
                                            {{ $selection?->test_score ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-slate-500">Interview</p>
                                        <p class="mt-1 font-black text-slate-800">
                                            {{ $selection?->interview_score ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-slate-500">Final</p>
                                        <p class="mt-1 font-black text-polmind-blue">
                                            {{ $selection?->final_score ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $selectionClasses[$selectionStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $selectionLabels[$selectionStatus] ?? $selectionStatus }}
                                </span>

                                @if($selection?->decided_at)
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $selection->decided_at->format('d M Y H:i') }}
                                    </p>
                                @endif

                                @if($selection?->note)
                                    <p class="mt-2 max-w-[240px] text-xs leading-5 text-slate-500">
                                        {{ $selection->note }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if($reRegistrationInvoice)
                                    <p class="font-bold text-slate-800">
                                        {{ $reRegistrationInvoice->invoice_number }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Rp{{ number_format($reRegistrationInvoice->total_amount, 0, ',', '.') }}
                                    </p>

                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $invoiceStatusClasses[$reRegistrationInvoice->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $invoiceStatusLabels[$reRegistrationInvoice->status] ?? $reRegistrationInvoice->status }}
                                    </span>
                                @else
                                    <span class="text-xs font-bold text-slate-500">
                                        Belum dibuat
                                    </span>
                                @endif

                                @if($hasReRegistrationPaymentActivity)
                                    <p class="mt-2 max-w-52 text-xs leading-5 text-yellow-700">
                                        Sudah ada aktivitas pembayaran DU.
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $applicant->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if($isReadyForSelection)
                                        <button type="button"
                                                onclick="document.getElementById('selection-form-{{ $applicant->id }}').classList.toggle('hidden')"
                                                class="rounded-xl bg-polmind-blue px-3 py-2 text-xs font-bold text-white transition hover:bg-polmind-blue-dark">
                                            Proses
                                        </button>
                                    @else
                                        <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">
                                            Belum Siap
                                        </span>
                                    @endif
                                </div>

                                @if($isReadyForSelection)
                                    <form id="selection-form-{{ $applicant->id }}"
                                          action="{{ route('admin.selections.accept', $applicant) }}"
                                          method="POST"
                                          class="mt-3 hidden w-[320px] rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        @csrf
                                        @method('PATCH')

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Nilai Tes</label>
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       max="100"
                                                       name="test_score"
                                                       value="{{ old('test_score', $selection?->test_score) }}"
                                                       placeholder="0-100"
                                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-slate-600">Interview</label>
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       max="100"
                                                       name="interview_score"
                                                       value="{{ old('interview_score', $selection?->interview_score) }}"
                                                       placeholder="0-100"
                                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                            </div>
                                        </div>

                                        <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs leading-5 text-polmind-blue">
                                            Formula nilai akhir: 60% nilai tes + 40% interview.
                                        </div>

                                        <div class="mt-3">
                                            <label class="text-xs font-bold text-slate-600">Catatan Seleksi</label>
                                            <textarea name="note"
                                                      rows="3"
                                                      placeholder="Catatan seleksi..."
                                                      class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">{{ old('note', $selection?->note) }}</textarea>
                                        </div>

                                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                            <button type="submit"
                                                    formaction="{{ route('admin.selections.accept', $applicant) }}"
                                                    onclick="return confirm('Set camaba ini sebagai DITERIMA? Invoice daftar ulang akan dibuat otomatis.')"
                                                    class="w-full rounded-xl bg-green-600 px-3 py-2 text-xs font-black text-white hover:bg-green-700">
                                                Diterima
                                            </button>

                                            <button type="submit"
                                                    formaction="{{ route('admin.selections.reserve', $applicant) }}"
                                                    onclick="return confirm('Set camaba ini sebagai CADANGAN? Invoice daftar ulang akan dibatalkan jika belum ada pembayaran.')"
                                                    @disabled($hasReRegistrationPaymentActivity)
                                                    class="w-full rounded-xl bg-yellow-500 px-3 py-2 text-xs font-black text-polmind-blue-dark hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50">
                                                Cadangan
                                            </button>

                                            <button type="submit"
                                                    formaction="{{ route('admin.selections.reject', $applicant) }}"
                                                    onclick="return confirm('Tolak camaba ini? Pastikan catatan seleksi sudah diisi.')"
                                                    @disabled($hasReRegistrationPaymentActivity)
                                                    class="w-full rounded-xl bg-red-600 px-3 py-2 text-xs font-black text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                                                Ditolak
                                            </button>
                                        </div>

                                        @if($hasReRegistrationPaymentActivity)
                                            <p class="mt-3 text-xs leading-5 text-red-600">
                                                Status tidak bisa diubah ke cadangan/ditolak karena sudah ada aktivitas pembayaran daftar ulang.
                                            </p>
                                        @endif
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
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
        </div>

        <div class="border-t border-slate-200 p-6">
            {{ $applicants->links() }}
        </div>
    </div>

</div>
@endsection