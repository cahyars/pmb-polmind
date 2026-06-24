@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran')
@section('page_title', 'Verifikasi Pembayaran')
@section('page_subtitle', 'Validasi bukti pembayaran pendaftaran dan daftar ulang camaba.')

@section('content')
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
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
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
                'label' => 'Total Masuk',
                'value' => 'Rp' . number_format($totalValidAmount, 0, ',', '.'),
                'desc' => 'Nominal pembayaran valid',
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
            <h2 class="text-xl font-black text-polmind-blue">Filter Pembayaran</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama camaba, nomor pendaftaran, invoice, nomor pembayaran, nama pengirim, atau bank.
            </p>
        </div>

        <form action="{{ route('admin.payments.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
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

            <div class="flex items-end gap-3 xl:col-span-5">
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
                    Data pembayaran diambil dari tabel payments dan invoice.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $payments->total() }} Data
            </span>
        </div>

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
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1150px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Invoice</th>
                        <th class="px-6 py-4">Pembayaran</th>
                        <th class="px-6 py-4">Pengirim</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Catatan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($payments as $payment)
                        <tr class="transition hover:bg-slate-50">
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
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">
                                    {{ $payment->invoice?->invoice_number ?? '-' }}
                                </p>
                                <span class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $invoiceTypeLabels[$payment->invoice?->type] ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">
                                    {{ $payment->payment_number }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Tgl Transfer:
                                    {{ $payment->transfer_date?->format('d M Y') ?? '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $payment->proof_file_name ?? 'Belum ada bukti' }}
                                </p>
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
                                <p class="font-black text-polmind-blue">
                                    Rp{{ number_format($payment->amount, 0, ',', '.') }}
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
                            </td>

                            <td class="px-6 py-5 text-slate-600">
                                {{ $payment->admin_note ?? '-' }}
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $payment->applicant?->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if($payment->proof_file_path)
                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Preview Bukti Bayar',
                                                    text: 'Preview file akan kita sambungkan setelah storage upload aktif. File path: {{ $payment->proof_file_path }}',
                                                    icon: 'info',
                                                    confirmButtonColor: '#003B82'
                                                })"
                                                class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue transition hover:bg-blue-100">
                                            Preview
                                        </button>
                                    @endif

                                    @if($payment->status !== 'valid')
                                        <form action="{{ route('admin.payments.accept', $payment) }}" method="POST"
                                              onsubmit="return confirm('Validasi pembayaran ini?')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                                Valid
                                            </button>
                                        </form>
                                    @endif

                                    @if($payment->status !== 'rejected')
                                        <button type="button"
                                                onclick="document.getElementById('reject-payment-form-{{ $payment->id }}').classList.toggle('hidden')"
                                                class="rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                                            Tolak
                                        </button>
                                    @endif
                                </div>

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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
                                    Tidak ada data pembayaran ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba reset filter atau jalankan seeder transaksi.
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