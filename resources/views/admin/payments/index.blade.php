@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran')
@section('page_title', 'Verifikasi Pembayaran')
@section('page_subtitle', 'Validasi bukti pembayaran pendaftaran dan daftar ulang camaba.')

@section('content')
@php
    $statusLabels = [
        'waiting_verification' => 'Menunggu Validasi',
        'valid' => 'Valid',
        'rejected' => 'Ditolak',
    ];

    $statusClasses = [
        'waiting_verification' => 'bg-yellow-100 text-yellow-700',
        'valid' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];

    $invoiceTypeLabels = [
        'registration' => 'Pendaftaran',
        're_registration' => 'Daftar Ulang',
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

    $registrationPathClasses = [
        'umum' => 'bg-blue-50 text-polmind-blue',
        'prestasi' => 'bg-green-100 text-green-700',
        'undangan' => 'bg-purple-100 text-purple-700',
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
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            [
                'label' => 'Menunggu Validasi',
                'value' => $waitingPayments,
                'desc' => 'Perlu dicek admin',
                'class' => 'text-yellow-700',
            ],
            [
                'label' => 'Pembayaran Valid',
                'value' => $validPayments,
                'desc' => 'Sudah diterima',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Pembayaran Ditolak',
                'value' => $rejectedPayments,
                'desc' => 'Perlu upload ulang',
                'class' => 'text-red-700',
            ],
            [
                'label' => 'Invoice Cicilan',
                'value' => $partialInvoices,
                'desc' => 'Tagihan belum lunas',
                'class' => 'text-polmind-blue',
            ],
            [
                'label' => 'Total Masuk',
                'value' => 'Rp' . number_format($totalValidAmount, 0, ',', '.'),
                'desc' => 'Nominal valid',
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

    <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-black text-yellow-800">Total Menunggu Verifikasi</h2>
                <p class="mt-2 text-sm leading-6 text-yellow-800">
                    Nominal ini belum masuk sebagai pembayaran valid sampai admin melakukan validasi.
                </p>
            </div>

            <p class="text-3xl font-black text-yellow-800">
                Rp{{ number_format($totalWaitingAmount, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Pembayaran</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama camaba, nomor pendaftaran, invoice, nomor pembayaran, nama pengirim, atau bank.
            </p>
        </div>

        <form action="{{ route('admin.payments.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, invoice, payment number, bank..."
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jenis Tagihan</label>
                <select name="invoice_type"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Tagihan</option>
                    <option value="registration" @selected(request('invoice_type') === 'registration')>
                        Pendaftaran
                    </option>
                    <option value="re_registration" @selected(request('invoice_type') === 're_registration')>
                        Daftar Ulang
                    </option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jalur</label>
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

            <div>
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach([
                        'waiting_verification' => 'Menunggu Validasi',
                        'valid' => 'Valid',
                        'rejected' => 'Ditolak',
                    ] as $key => $label)
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

                <a href="{{ route('admin.payments.index') }}"
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
                <h2 class="text-xl font-black text-polmind-blue">Antrian Verifikasi Pembayaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Admin dapat melihat apakah pembayaran akan melunasi tagihan atau menjadi cicilan.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $payments->total() }} Data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1450px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Invoice</th>
                        <th class="px-6 py-4">Progress Tagihan</th>
                        <th class="px-6 py-4">Pembayaran Ini</th>
                        <th class="px-6 py-4">Pengirim</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($payments as $payment)
                        @php
                            $invoice = $payment->invoice;
                            $totalAmount = (float) ($invoice?->total_amount ?? 0);

                            $validPaidAmount = (float) ($invoice?->payments?->where('status', 'valid')->sum('amount') ?? 0);
                            $waitingPaidAmount = (float) ($invoice?->payments?->where('status', 'waiting_verification')->sum('amount') ?? 0);

                            $remainingNow = max(0, $totalAmount - $validPaidAmount);

                            $afterValidationPaid = $payment->status === 'waiting_verification'
                                ? $validPaidAmount + (float) $payment->amount
                                : $validPaidAmount;

                            $remainingAfterValidation = max(0, $totalAmount - $afterValidationPaid);

                            $progressPercentage = $totalAmount > 0
                                ? min(100, round(($validPaidAmount / $totalAmount) * 100))
                                : 0;

                            $afterValidationPercentage = $totalAmount > 0
                                ? min(100, round(($afterValidationPaid / $totalAmount) * 100))
                                : 0;

                            $willFullyPaid = $payment->status === 'waiting_verification' && $remainingAfterValidation <= 0;

                            $proofUrl = $payment->proof_file_path
                                ? asset('storage/' . $payment->proof_file_path)
                                : null;

                            $registrationPath = $payment->applicant?->registration_path ?? 'umum';
                        @endphp

                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $payment->applicant?->registration_number }}
                                </p>
                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $payment->applicant?->full_name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $payment->applicant?->studyProgram?->code ?? '-' }}
                                    ·
                                    {{ $payment->applicant?->classType?->name ?? '-' }}
                                </p>

                                <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $registrationPathClasses[$registrationPath] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $payment->applicant?->registration_path_label ?? 'Umum' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">
                                    {{ $invoice?->invoice_number ?? '-' }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                        {{ $invoiceTypeLabels[$invoice?->type] ?? '-' }}
                                    </span>

                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $invoiceStatusClasses[$invoice?->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $invoiceStatusLabels[$invoice?->status] ?? str_replace('_', ' ', ucfirst($invoice?->status ?? '-')) }}
                                    </span>
                                </div>

                                <p class="mt-3 text-xs text-slate-500">
                                    Total:
                                    <span class="font-black text-slate-700">
                                        Rp{{ number_format($totalAmount, 0, ',', '.') }}
                                    </span>
                                </p>
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
                                            <p class="font-bold text-slate-500">Sisa Sekarang</p>
                                            <p class="mt-1 font-black text-red-600">
                                                Rp{{ number_format($remainingNow, 0, ',', '.') }}
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
                                        <div class="h-full rounded-full bg-polmind-blue"
                                             style="width: {{ $progressPercentage }}%"></div>
                                    </div>

                                    @if($payment->status === 'waiting_verification')
                                        <div class="mt-4 rounded-xl border {{ $willFullyPaid ? 'border-green-200 bg-green-50 text-green-700' : 'border-blue-200 bg-blue-50 text-polmind-blue' }} p-3 text-xs leading-5">
                                            <p class="font-black">
                                                {{ $willFullyPaid ? 'Jika divalidasi: Tagihan Lunas' : 'Jika divalidasi: Masih Cicilan' }}
                                            </p>
                                            <p class="mt-1">
                                                Sisa setelah validasi:
                                                <span class="font-black">
                                                    Rp{{ number_format($remainingAfterValidation, 0, ',', '.') }}
                                                </span>
                                            </p>

                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/70">
                                                <div class="h-full rounded-full {{ $willFullyPaid ? 'bg-green-600' : 'bg-polmind-blue' }}"
                                                     style="width: {{ $afterValidationPercentage }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">
                                    {{ $payment->payment_number }}
                                </p>

                                <p class="mt-2 text-xs text-slate-500">
                                    Tgl Transfer:
                                    {{ $payment->transfer_date?->format('d M Y') ?? '-' }}
                                </p>

                                <p class="mt-3 text-xl font-black text-polmind-blue">
                                    Rp{{ number_format($payment->amount, 0, ',', '.') }}
                                </p>

                                @if($payment->proof_file_name)
                                    <p class="mt-2 max-w-48 truncate text-xs text-slate-500">
                                        {{ $payment->proof_file_name }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-semibold text-slate-700">
                                    {{ $payment->sender_name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $payment->sender_bank ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$payment->status] ?? $payment->status }}
                                </span>

                                @if($payment->verified_at)
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $payment->verified_at->format('d M Y H:i') }}
                                    </p>
                                @endif

                                @if($payment->admin_note)
                                    <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-xs leading-5 text-red-700">
                                        <p class="font-black">Catatan</p>
                                        <p class="mt-1">{{ $payment->admin_note }}</p>
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $payment->applicant?->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if($proofUrl)
                                        <a href="{{ $proofUrl }}"
                                           target="_blank"
                                           class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue transition hover:bg-blue-100">
                                            Bukti
                                        </a>
                                    @endif

                                    @if($payment->status === 'waiting_verification')
                                        <form action="{{ route('admin.payments.accept', $payment) }}"
                                              method="POST"
                                              onsubmit="return confirm('{{ $willFullyPaid ? 'Validasi pembayaran ini dan lunaskan tagihan?' : 'Validasi pembayaran ini sebagai cicilan?' }}')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                                Valid
                                            </button>
                                        </form>

                                        <button type="button"
                                                onclick="document.getElementById('reject-payment-form-{{ $payment->id }}').classList.toggle('hidden')"
                                                class="rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                                            Tolak
                                        </button>
                                    @else
                                        <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">
                                            Selesai
                                        </span>
                                    @endif
                                </div>

                                @if($payment->status === 'waiting_verification')
                                    <form id="reject-payment-form-{{ $payment->id }}"
                                          action="{{ route('admin.payments.reject', $payment) }}"
                                          method="POST"
                                          class="mt-3 hidden rounded-2xl border border-red-200 bg-red-50 p-3">
                                        @csrf
                                        @method('PATCH')

                                        <textarea name="admin_note"
                                                  rows="3"
                                                  required
                                                  placeholder="Tulis alasan penolakan pembayaran..."
                                                  class="w-full rounded-xl border border-red-200 px-3 py-2 text-xs outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100">{{ old('admin_note') }}</textarea>

                                        <div class="mt-2 flex justify-end gap-2">
                                            <button type="button"
                                                    onclick="document.getElementById('reject-payment-form-{{ $payment->id }}').classList.add('hidden')"
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
                            <td colspan="7" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
                                    Tidak ada data pembayaran ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba reset filter atau tunggu camaba mengupload bukti pembayaran.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-6">
            {{ $payments->links() }}
        </div>
    </div>

</div>
@endsection