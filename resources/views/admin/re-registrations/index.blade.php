@extends('layouts.admin')

@section('title', 'Daftar Ulang')
@section('page_title', 'Daftar Ulang')
@section('page_subtitle', 'Monitoring dan validasi daftar ulang calon mahasiswa yang diterima.')

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
                'label' => 'Camaba Diterima',
                'value' => $acceptedApplicants,
                'desc' => 'Lolos seleksi',
                'class' => 'text-polmind-blue',
            ],
            [
                'label' => 'Menunggu Daftar Ulang',
                'value' => $waitingReRegistrations,
                'desc' => 'Belum valid',
                'class' => 'text-yellow-700',
            ],
            [
                'label' => 'Daftar Ulang Valid',
                'value' => $validReRegistrations,
                'desc' => 'Sudah divalidasi',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Siap Sinkron',
                'value' => $readySyncApplicants,
                'desc' => 'Siap dikirim ke SIAKAD',
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

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Daftar Ulang</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama, nomor pendaftaran, invoice, NIK, atau asal sekolah.
            </p>
        </div>

        <form action="{{ route('admin.re-registrations.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
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
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach([
                        'belum_daftar_ulang' => 'Belum Daftar Ulang',
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'valid' => 'Valid',
                        'ditolak' => 'Ditolak',
                        'lewat_batas' => 'Lewat Batas',
                    ] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-4">
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
                    Data berasal dari tabel re_registrations, invoices, payments, dan applicants.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $reRegistrations->total() }} Data
            </span>
        </div>

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

            $invoiceStatusClasses = [
                'unpaid' => 'bg-slate-100 text-slate-600',
                'waiting_verification' => 'bg-yellow-100 text-yellow-700',
                'paid' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
                'cancelled' => 'bg-red-100 text-red-700',
            ];
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1250px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Program</th>
                        <th class="px-6 py-4">Invoice DU</th>
                        <th class="px-6 py-4">Pembayaran</th>
                        <th class="px-6 py-4">Status DU</th>
                        <th class="px-6 py-4">Sync SIAKAD</th>
                        <th class="px-6 py-4">Catatan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($reRegistrations as $reRegistration)
                        @php
                            $applicant = $reRegistration->applicant;
                            $invoice = $reRegistration->invoice;
                            $latestPayment = $invoice?->latestPayment;
                        @endphp

                        <tr class="transition hover:bg-slate-50">
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
                                        Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}
                                    </p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $invoiceStatusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
                                    </span>
                                @else
                                    <span class="text-xs font-bold text-slate-500">Belum ada invoice</span>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if($latestPayment)
                                    <p class="font-bold text-slate-800">
                                        {{ $latestPayment->payment_number }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $latestPayment->sender_name ?? '-' }} · {{ $latestPayment->sender_bank ?? '-' }}
                                    </p>
                                    <p class="mt-1 font-black text-polmind-blue">
                                        Rp{{ number_format($latestPayment->amount, 0, ',', '.') }}
                                    </p>
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
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $applicant?->sync_status === 'siap_sinkron' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ str_replace('_', ' ', ucfirst($applicant?->sync_status ?? 'belum_siap')) }}
                                </span>

                                @if($reRegistration->ready_sync_at)
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $reRegistration->ready_sync_at->format('d M Y H:i') }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-slate-600">
                                {{ $reRegistration->admin_note ?? '-' }}
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $applicant?->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if($reRegistration->status !== 'valid')
                                        <form action="{{ route('admin.re-registrations.validate', $reRegistration) }}"
                                              method="POST"
                                              onsubmit="return confirm('Validasi daftar ulang ini? Data akan siap sinkron ke SIAKAD.')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                                Valid
                                            </button>
                                        </form>
                                    @endif

                                    @if($applicant?->sync_status !== 'siap_sinkron')
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

                                    @if($reRegistration->status !== 'ditolak')
                                        <button type="button"
                                                onclick="document.getElementById('reject-rereg-form-{{ $reRegistration->id }}').classList.toggle('hidden')"
                                                class="rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                                            Tolak
                                        </button>
                                    @endif
                                </div>

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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
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