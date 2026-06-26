@extends('layouts.admin')

@section('title', 'Daftar Ulang')
@section('page_title', 'Daftar Ulang')
@section('page_subtitle', 'Monitoring dan validasi daftar ulang calon mahasiswa yang diterima.')

@section('content')
@php
    $statusLabels = [
        'belum_daftar_ulang' => 'Belum Daftar Ulang',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
        'lewat_batas' => 'Lewat Batas',
    ];

    $statusClasses = [
        'belum_daftar_ulang' => 'bg-slate-100 text-slate-600',
        'menunggu_verifikasi' => 'bg-yellow-100 text-yellow-700',
        'valid' => 'bg-green-100 text-green-700',
        'ditolak' => 'bg-red-100 text-red-700',
        'lewat_batas' => 'bg-red-100 text-red-700',
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
        'unpaid' => 'bg-slate-100 text-slate-600',
        'waiting_verification' => 'bg-yellow-100 text-yellow-700',
        'partial' => 'bg-blue-100 text-polmind-blue',
        'paid' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    $registrationPathClasses = [
        'umum' => 'bg-blue-50 text-polmind-blue',
        'prestasi' => 'bg-green-100 text-green-700',
        'undangan' => 'bg-purple-100 text-purple-700',
    ];

    $syncStatusClasses = [
        'belum_siap' => 'bg-slate-100 text-slate-600',
        'siap_sinkron' => 'bg-purple-100 text-purple-700',
        'proses_sinkron' => 'bg-yellow-100 text-yellow-700',
        'sudah_sinkron' => 'bg-green-100 text-green-700',
        'gagal_sinkron' => 'bg-red-100 text-red-700',
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

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            [
                'label' => 'Camaba Diterima',
                'value' => $acceptedApplicants,
                'desc' => 'Lolos seleksi',
                'class' => 'text-polmind-blue',
            ],
            [
                'label' => 'Menunggu DU',
                'value' => $waitingReRegistrations,
                'desc' => 'Belum valid',
                'class' => 'text-yellow-700',
            ],
            [
                'label' => 'Cicilan DU',
                'value' => $partialReRegistrations,
                'desc' => 'Belum lunas',
                'class' => 'text-polmind-blue',
            ],
            [
                'label' => 'DU Valid',
                'value' => $validReRegistrations,
                'desc' => 'Sudah divalidasi',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Siap Sinkron',
                'value' => $readySyncApplicants,
                'desc' => 'Siap ke SIAKAD',
                'class' => 'text-purple-700',
            ],
            [
                'label' => 'Total DU Masuk',
                'value' => 'Rp' . number_format($totalPaidReRegistration, 0, ',', '.'),
                'desc' => 'Pembayaran valid',
                'class' => 'text-polmind-blue',
            ],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-black {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Daftar Ulang</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama, nomor pendaftaran, invoice, NIK, atau asal sekolah.
            </p>
        </div>

        <form action="{{ route('admin.re-registrations.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, no pendaftaran, invoice, sekolah..."
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
                <label class="text-sm font-bold text-slate-700">Status DU</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status Invoice</label>
                <select name="invoice_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Invoice</option>
                    @foreach($invoiceStatusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('invoice_status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
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
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.re-registrations.index') }}"
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
                <h2 class="text-xl font-black text-polmind-blue">Data Daftar Ulang</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Halaman ini untuk monitoring final daftar ulang. Validasi hanya bisa dilakukan jika pembayaran valid sudah lunas.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $reRegistrations->total() }} Data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1550px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Jalur</th>
                        <th class="px-6 py-4">Program</th>
                        <th class="px-6 py-4">Invoice DU</th>
                        <th class="px-6 py-4">Progress Pembayaran</th>
                        <th class="px-6 py-4">Riwayat Pembayaran</th>
                        <th class="px-6 py-4">Status DU</th>
                        <th class="px-6 py-4">Sync SIAKAD</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($reRegistrations as $reRegistration)
                        @php
                            $applicant = $reRegistration->applicant;
                            $invoice = $reRegistration->invoice;

                            $payments = $invoice?->payments?->sortByDesc('created_at')->values() ?? collect();

                            $totalAmount = (float) ($invoice?->total_amount ?? 0);

                            $validPaidAmount = (float) $payments
                                ->where('status', 'valid')
                                ->sum('amount');

                            $waitingPaidAmount = (float) $payments
                                ->where('status', 'waiting_verification')
                                ->sum('amount');

                            $remainingAmount = max(0, $totalAmount - $validPaidAmount);

                            $progressPercentage = $totalAmount > 0
                                ? min(100, round(($validPaidAmount / $totalAmount) * 100))
                                : 0;

                            $isFullyPaid = $totalAmount <= 0 || $validPaidAmount >= $totalAmount;

                            $registrationPath = $applicant?->registration_path ?? 'umum';

                            $syncStatus = $applicant?->sync_status ?? 'belum_siap';

                            $canValidate = $invoice
                                && $isFullyPaid
                                && $reRegistration->status !== 'valid';

                            $canReadySync = $reRegistration->status === 'valid'
                                && $syncStatus !== 'siap_sinkron'
                                && $syncStatus !== 'sudah_sinkron';

                            $canReject = $reRegistration->status !== 'valid'
                                && $validPaidAmount <= 0
                                && $waitingPaidAmount <= 0
                                && ! in_array($syncStatus, ['siap_sinkron', 'proses_sinkron', 'sudah_sinkron']);
                        @endphp

                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $applicant?->registration_number ?? '-' }}
                                </p>

                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $applicant?->full_name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant?->education?->school_name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant?->phone ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $registrationPathClasses[$registrationPath] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $applicant?->registration_path_label ?? 'Umum' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $applicant?->studyProgram?->code ?? '-' }}
                                </span>

                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $applicant?->classType?->name ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                @if($invoice)
                                    <p class="font-bold text-slate-800">
                                        {{ $invoice->invoice_number }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Total:
                                        <span class="font-black text-slate-700">
                                            Rp{{ number_format($totalAmount, 0, ',', '.') }}
                                        </span>
                                    </p>

                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $invoiceStatusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $invoiceStatusLabels[$invoice->status] ?? str_replace('_', ' ', ucfirst($invoice->status)) }}
                                    </span>
                                @else
                                    <span class="text-xs font-bold text-slate-500">
                                        Belum ada invoice
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="w-72 rounded-2xl bg-slate-50 p-4">
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <p class="font-bold text-slate-500">Valid</p>
                                            <p class="mt-1 font-black text-polmind-blue">
                                                Rp{{ number_format($validPaidAmount, 0, ',', '.') }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="font-bold text-slate-500">Menunggu</p>
                                            <p class="mt-1 font-black text-yellow-700">
                                                Rp{{ number_format($waitingPaidAmount, 0, ',', '.') }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="font-bold text-slate-500">Sisa</p>
                                            <p class="mt-1 font-black text-red-600">
                                                Rp{{ number_format($remainingAmount, 0, ',', '.') }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="font-bold text-slate-500">Progress</p>
                                            <p class="mt-1 font-black text-slate-700">
                                                {{ $progressPercentage }}%
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full {{ $isFullyPaid ? 'bg-green-600' : 'bg-polmind-blue' }}"
                                             style="width: {{ $progressPercentage }}%"></div>
                                    </div>

                                    @if($isFullyPaid)
                                        <p class="mt-3 rounded-xl bg-green-50 p-3 text-xs font-bold text-green-700">
                                            Pembayaran valid sudah lunas.
                                        </p>
                                    @elseif($waitingPaidAmount > 0)
                                        <p class="mt-3 rounded-xl bg-yellow-50 p-3 text-xs font-bold text-yellow-700">
                                            Masih ada pembayaran menunggu verifikasi.
                                        </p>
                                    @else
                                        <p class="mt-3 rounded-xl bg-blue-50 p-3 text-xs font-bold text-polmind-blue">
                                            Menunggu pelunasan daftar ulang.
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                @if($payments->count())
                                    <div class="max-h-48 w-72 space-y-3 overflow-y-auto pr-1">
                                        @foreach($payments as $payment)
                                            @php
                                                $paymentBadgeClass = match ($payment->status) {
                                                    'valid' => 'bg-green-100 text-green-700',
                                                    'waiting_verification' => 'bg-yellow-100 text-yellow-700',
                                                    'rejected' => 'bg-red-100 text-red-700',
                                                    default => 'bg-slate-100 text-slate-600',
                                                };

                                                $paymentLabel = match ($payment->status) {
                                                    'valid' => 'Valid',
                                                    'waiting_verification' => 'Menunggu',
                                                    'rejected' => 'Ditolak',
                                                    default => str_replace('_', ' ', ucfirst($payment->status)),
                                                };

                                                $proofUrl = $payment->proof_file_path
                                                    ? asset('storage/' . $payment->proof_file_path)
                                                    : null;
                                            @endphp

                                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs font-black text-polmind-blue">
                                                            {{ $payment->payment_number }}
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-500">
                                                            {{ $payment->sender_name ?? '-' }} · {{ $payment->sender_bank ?? '-' }}
                                                        </p>

                                                        <p class="mt-1 text-sm font-black text-slate-800">
                                                            Rp{{ number_format($payment->amount, 0, ',', '.') }}
                                                        </p>
                                                    </div>

                                                    <span class="rounded-full px-2 py-1 text-[10px] font-black {{ $paymentBadgeClass }}">
                                                        {{ $paymentLabel }}
                                                    </span>
                                                </div>

                                                <div class="mt-2 flex items-center justify-between gap-2">
                                                    <p class="text-[11px] text-slate-500">
                                                        {{ $payment->transfer_date?->format('d M Y') ?? '-' }}
                                                    </p>

                                                    @if($proofUrl)
                                                        <a href="{{ $proofUrl }}"
                                                           target="_blank"
                                                           class="text-[11px] font-black text-polmind-blue hover:underline">
                                                            Bukti
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs font-bold text-slate-500">
                                        Belum ada pembayaran
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$reRegistration->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$reRegistration->status] ?? $reRegistration->status }}
                                </span>

                                @if($reRegistration->validated_at)
                                    <p class="mt-2 text-xs text-slate-500">
                                        Validasi: {{ $reRegistration->validated_at->format('d M Y H:i') }}
                                    </p>
                                @endif

                                @if($reRegistration->deadline_date)
                                    <p class="mt-1 text-xs text-slate-500">
                                        Deadline: {{ $reRegistration->deadline_date->format('d M Y') }}
                                    </p>
                                @endif

                                @if($reRegistration->admin_note)
                                    <div class="mt-3 max-w-64 rounded-xl border border-red-200 bg-red-50 p-3 text-xs leading-5 text-red-700">
                                        <p class="font-black">Catatan Admin</p>
                                        <p class="mt-1">{{ $reRegistration->admin_note }}</p>
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $syncStatusClasses[$syncStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ str_replace('_', ' ', ucfirst($syncStatus)) }}
                                </span>

                                @if($reRegistration->ready_sync_at)
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $reRegistration->ready_sync_at->format('d M Y H:i') }}
                                    </p>
                                @endif

                                @if($applicant?->nim)
                                    <p class="mt-2 text-xs font-black text-green-700">
                                        NIM: {{ $applicant->nim }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $applicant?->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if($canValidate)
                                        <form action="{{ route('admin.re-registrations.validate', $reRegistration) }}"
                                              method="POST"
                                              onsubmit="return confirm('Validasi daftar ulang ini? Data akan siap sinkron ke SIAKAD.')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                                Valid DU
                                            </button>
                                        </form>
                                    @endif

                                    @if($canReadySync)
                                        <form action="{{ route('admin.re-registrations.ready-sync', $reRegistration) }}"
                                              method="POST"
                                              onsubmit="return confirm('Tandai siap sinkron ke SIAKAD?')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl bg-purple-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-purple-700">
                                                Siap Sync
                                            </button>
                                        </form>
                                    @endif

                                    @if($canReject)
                                        <button type="button"
                                                onclick="document.getElementById('reject-rereg-form-{{ $reRegistration->id }}').classList.toggle('hidden')"
                                                class="rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                                            Tolak
                                        </button>
                                    @endif

                                    @if(! $canValidate && ! $canReadySync && ! $canReject)
                                        <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">
                                            Tidak Ada Aksi
                                        </span>
                                    @endif
                                </div>

                                @if($canReject)
                                    <form id="reject-rereg-form-{{ $reRegistration->id }}"
                                          action="{{ route('admin.re-registrations.reject', $reRegistration) }}"
                                          method="POST"
                                          class="mt-3 hidden rounded-2xl border border-red-200 bg-red-50 p-3">
                                        @csrf
                                        @method('PATCH')

                                        <textarea name="admin_note"
                                                  rows="3"
                                                  required
                                                  placeholder="Tulis alasan penolakan daftar ulang..."
                                                  class="w-full rounded-xl border border-red-200 px-3 py-2 text-xs outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100">{{ old('admin_note') }}</textarea>

                                        <div class="mt-2 flex justify-end gap-2">
                                            <button type="button"
                                                    onclick="document.getElementById('reject-rereg-form-{{ $reRegistration->id }}').classList.add('hidden')"
                                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                                                Batal
                                            </button>

                                            <button type="submit"
                                                    class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">
                                                Simpan Tolak
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
                                    Data daftar ulang tidak ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Pastikan camaba sudah dinyatakan diterima agar invoice daftar ulang terbentuk.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-6">
            {{ $reRegistrations->links() }}
        </div>
    </div>

</div>
@endsection