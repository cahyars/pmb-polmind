@extends('layouts.admin')

@section('title', 'Detail Camaba')
@section('page_title', 'Detail Camaba')
@section('page_subtitle', 'Detail data calon mahasiswa baru berdasarkan database PMB.')

@section('content')
@php
    $registrationStatusLabels = [
        'registrasi_awal' => 'Registrasi Awal',
        'biodata_lengkap' => 'Biodata Lengkap',
    ];

    $documentStatusLabels = [
        'belum_upload' => 'Belum Upload',
        'sebagian_upload' => 'Sebagian Upload',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
    ];

    $paymentStatusLabels = [
        'belum_bayar' => 'Belum Bayar',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
    ];

    $selectionStatusLabels = [
        'belum_diseleksi' => 'Belum Diseleksi',
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'ditolak' => 'Ditolak',
    ];

    $badgeClass = function ($status) {
        return match ($status) {
            'valid', 'diterima', 'paid', 'success', 'prestasi' => 'bg-green-100 text-green-700',
            'menunggu_verifikasi', 'waiting_verification', 'pending', 'sebagian_upload' => 'bg-yellow-100 text-yellow-700',
            'ditolak', 'rejected', 'failed' => 'bg-red-100 text-red-700',
            'biodata_lengkap', 'umum' => 'bg-blue-100 text-polmind-blue',
            'undangan' => 'bg-purple-100 text-purple-700',
            default => 'bg-slate-100 text-slate-600',
        };
    };
@endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="rounded-xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <a href="{{ url('/admin/applicants') }}"
                   class="inline-flex items-center rounded-lg bg-white/10 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                    ← Kembali ke Data Camaba
                </a>

                <div class="mt-6 flex items-center gap-5">
                    @if($applicant->photoDocument)
                        <button type="button"
                                @click="$store.preview.show(@js(route('admin.documents.file', $applicant->photoDocument)), @js(basename($applicant->photoDocument->file_path)))"
                                class="shrink-0" title="Lihat pas foto">
                            <img src="{{ route('admin.documents.file', $applicant->photoDocument) }}"
                                 alt="Foto {{ $applicant->full_name }}"
                                 class="h-24 w-20 rounded-lg bg-white object-cover ring-4 ring-white/20">
                        </button>
                    @endif

                    <div>
                        <p class="text-sm font-semibold text-blue-100">
                            {{ $applicant->registration_number }}
                        </p>

                        <h1 class="mt-2 text-3xl font-bold">
                            {{ $applicant->full_name }}
                        </h1>

                        <p class="mt-2 text-sm text-blue-100">
                            {{ $applicant->email }} · {{ $applicant->phone ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:w-[660px]">
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Program Studi</p>
                    <p class="mt-2 text-lg font-bold">
                        {{ $applicant->studyProgram?->code ?? '-' }}
                    </p>
                    <p class="text-xs text-blue-100">
                        {{ $applicant->classType?->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Gelombang</p>
                    <p class="mt-2 text-lg font-bold">
                        {{ $applicant->admissionWave?->name ?? '-' }}
                    </p>
                    <p class="text-xs text-blue-100">
                        {{ $applicant->pmbYear?->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Jalur</p>
                    <p class="mt-2 text-lg font-bold">
                        {{ $applicant->registration_path_label }}
                    </p>
                    <p class="text-xs text-blue-100">
                        Jalur Pendaftaran
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Summary --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Registrasi',
                'value' => $registrationStatusLabels[$applicant->registration_status] ?? $applicant->registration_status,
                'status' => $applicant->registration_status,
            ],
            [
                'label' => 'Berkas',
                'value' => $documentStatusLabels[$applicant->document_status] ?? $applicant->document_status,
                'status' => $applicant->document_status,
            ],
            [
                'label' => 'Pembayaran',
                'value' => $paymentStatusLabels[$applicant->payment_status] ?? $applicant->payment_status,
                'status' => $applicant->payment_status,
            ],
            [
                'label' => 'Seleksi',
                'value' => $selectionStatusLabels[$applicant->selection_status] ?? $applicant->selection_status,
                'status' => $applicant->selection_status,
            ],
        ] as $item)
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $item['label'] }}</p>
                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($item['status']) }}">
                    {{ $item['value'] }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

        {{-- Main Content --}}
        <div class="space-y-8">

            {{-- Biodata --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Biodata Camaba</h2>

                @php
                    $ref = \App\Models\RefOption::class;
                @endphp

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach([
                        'Nama Lengkap' => $applicant->full_name,
                        'NIK' => $applicant->nik ?? '-',
                        'NISN' => $applicant->nisn ?? '-',
                        'NPWP' => $applicant->npwp ?: '-',
                        'Jenis Kelamin' => $applicant->gender_label,
                        'Tempat Lahir' => $applicant->birth_place ?? '-',
                        'Tanggal Lahir' => $applicant->birth_date?->format('d M Y') ?? '-',
                        'Agama' => $applicant->religion_name ?? '-',
                        'Kewarganegaraan' => $ref::nameOf($ref::KEWARGANEGARAAN, $applicant->citizenship) ?? '-',
                        'Nomor HP' => $applicant->phone ?? '-',
                        'Telepon Rumah' => $applicant->home_phone ?: '-',
                        'Alat Transportasi' => $ref::nameOf($ref::ALAT_TRANSPORTASI, $applicant->transportation_id) ?? '-',
                        'Kebutuhan Khusus' => $ref::nameOf($ref::KEBUTUHAN_KHUSUS, $applicant->special_need_id) ?? '-',
                        'Penerima KPS' => $applicant->kps_recipient ? 'Ya (' . ($applicant->kps_number ?: '-') . ')' : 'Tidak',
                        'Jalur Pendaftaran' => $applicant->registration_path_label,
                        'Sumber Informasi' => $applicant->source_information ?? '-',
                    ] as $label => $value)
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Alamat & Pendidikan --}}
            <div class="grid gap-8 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Alamat Domisili</h2>

                    <div class="mt-6 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jalan</p>
                            <p class="mt-2 font-semibold text-slate-800">
                                {{ $applicant->address?->address ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Dusun: {{ $applicant->address?->hamlet ?: '-' }}
                                · Jenis tinggal: {{ \App\Models\RefOption::nameOf(\App\Models\RefOption::JENIS_TINGGAL, $applicant->address?->residence_type_id) ?? '-' }}
                                · ID Wilayah: {{ $applicant->address?->region_id ?? '-' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Provinsi</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->address?->province_name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Kab/Kota</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->address?->regency_name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Kecamatan</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->address?->district_name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Desa/Kelurahan</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->address?->village_name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">RT/RW</p>
                                <p class="mt-1 font-semibold text-slate-800">
                                    {{ $applicant->address?->rt ?? '-' }}/{{ $applicant->address?->rw ?? '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Kode Pos</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->address?->postal_code ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Pendidikan</h2>

                    <div class="mt-6 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Asal Sekolah</p>
                            <p class="mt-2 font-semibold text-slate-800">
                                {{ $applicant->education?->school_name ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                NPSN: {{ $applicant->education?->school_npsn ?? '-' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Jenis Sekolah</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->education?->school_type ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Status</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->education?->school_status ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Jurusan</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->education?->major ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Tahun Lulus</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $applicant->education?->graduation_year ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Orang Tua --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Data Orang Tua / Wali</h2>

                @php
                    $parent = $applicant->parentData;
                    $ref = \App\Models\RefOption::class;
                    $people = [
                        'Ayah' => 'father',
                        'Ibu Kandung' => 'mother',
                        'Wali' => 'guardian',
                    ];
                @endphp

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    @foreach($people as $title => $prefix)
                        <div class="rounded-lg bg-slate-50 p-5">
                            <p class="text-sm font-bold text-slate-900">{{ $title }}</p>
                            <p class="mt-3 text-sm font-semibold text-slate-700">{{ $parent?->{$prefix . '_name'} ?: '-' }}</p>

                            <dl class="mt-3 space-y-1 text-xs text-slate-500">
                                @if($prefix === 'guardian')
                                    <div>Hubungan: {{ $parent?->guardian_relation ?: '-' }}</div>
                                @endif
                                <div>NIK: {{ $parent?->{$prefix . '_nik'} ?: '-' }}</div>
                                <div>Tgl lahir: {{ $parent?->{$prefix . '_birth_date'}?->format('d M Y') ?? '-' }}</div>
                                <div>Pendidikan: {{ $ref::nameOf($ref::PENDIDIKAN, $parent?->{$prefix . '_education_id'}) ?? '-' }}</div>
                                <div>Pekerjaan: {{ $ref::nameOf($ref::PEKERJAAN, $parent?->{$prefix . '_job_id'}) ?? '-' }}</div>
                                <div>Penghasilan: {{ $ref::nameOf($ref::PENGHASILAN, $parent?->{$prefix . '_income_id'}) ?? '-' }}</div>
                                @if($prefix !== 'guardian')
                                    <div>Kebutuhan khusus: {{ $ref::nameOf($ref::KEBUTUHAN_KHUSUS, $parent?->{$prefix . '_special_need_id'}) ?? '-' }}</div>
                                @endif
                                <div>Telepon: {{ $parent?->{$prefix . '_phone'} ?: '-' }}</div>
                            </dl>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Dokumen --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Berkas Pendaftaran</h2>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                            <tr>
                                <th class="px-4 py-3">Jenis Berkas</th>
                                <th class="px-4 py-3">File</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Catatan</th>
                                <th class="px-4 py-3">Verifikator</th>
                            </tr>
                        </thead>
                        <tbody class="table-zebra">
                            @forelse($applicant->documents as $document)
                                <tr>
                                    <td class="px-4 py-4 font-medium text-slate-800">
                                        {{ $document->documentType?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-700">{{ $document->file_name ?? '-' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $document->file_size_kb ?? 0 }} KB</p>
                                        @if($document->file_path)
                                            <button type="button"
                                                    @click="$store.preview.show(@js(route('admin.documents.file', $document)), @js(basename($document->file_path)))"
                                                    class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-polmind-blue hover:bg-blue-100">
                                                Lihat
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($document->status) }}">
                                            {{ str_replace('_', ' ', ucfirst($document->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        {{ $document->admin_note ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        {{ $document->verified_by_name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                        Belum ada berkas yang diupload.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Invoice --}}
            <div class="grid gap-8 lg:grid-cols-2">
                @foreach([
                    'Invoice Pendaftaran' => $applicant->registrationInvoice,
                    'Invoice Daftar Ulang' => $applicant->reRegistrationInvoice,
                ] as $title => $invoice)
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>

                        @if($invoice)
                            <div class="mt-5 rounded-lg bg-slate-50 p-5">
                                <p class="text-xs font-semibold text-slate-500">Nomor Invoice</p>
                                <p class="mt-1 font-bold text-slate-900">{{ $invoice->invoice_number }}</p>

                                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500">Total</p>
                                        <p class="mt-1 font-bold text-polmind-blue">
                                            Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500">Status</p>
                                        <span class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($invoice->status) }}">
                                            {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach($invoice->items as $item)
                                    <div class="flex justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm">
                                        <span class="font-semibold text-slate-700">{{ $item->name }}</span>
                                        <span class="font-bold text-slate-900">
                                            Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-5 rounded-lg bg-slate-50 p-5 text-sm font-semibold text-slate-500">
                                Invoice belum tersedia.
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            {{-- Jalur Pendaftaran --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Jalur Pendaftaran</h2>

                <div class="mt-5 rounded-lg bg-slate-50 p-5">
                    <p class="text-xs font-semibold text-slate-500">Kategori Jalur</p>

                    <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($applicant->registration_path ?? 'umum') }}">
                        {{ $applicant->registration_path_label }}
                    </span>

                    <p class="mt-4 text-xs leading-5 text-slate-600">
                        @if(($applicant->registration_path ?? 'umum') === 'prestasi')
                            Camaba mendaftar melalui jalur prestasi. Pastikan dokumen pendukung prestasi telah diverifikasi.
                        @elseif(($applicant->registration_path ?? 'umum') === 'undangan')
                            Camaba mendaftar melalui jalur undangan. Pastikan data rekomendasi/undangan sesuai ketentuan PMB.
                        @else
                            Camaba mendaftar melalui jalur umum dengan alur seleksi reguler.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Action --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Aksi Admin</h2>

                <div class="mt-5 space-y-3">
                    @foreach([
                        ['label' => 'Validasi Berkas', 'color' => 'bg-polmind-blue text-white'],
                        ['label' => 'Validasi Pembayaran', 'color' => 'bg-green-600 text-white'],
                        ['label' => 'Set Diterima', 'color' => 'bg-purple-600 text-white'],
                        ['label' => 'Tandai Follow Up', 'color' => 'bg-polmind-yellow text-polmind-blue-dark'],
                    ] as $action)
                        <button type="button"
                                onclick="Swal.fire({
                                    title: '{{ $action['label'] }}',
                                    text: 'Aksi ini akan kita sambungkan ke controller update di tahap berikutnya.',
                                    icon: 'info',
                                    confirmButtonColor: '#003B82'
                                })"
                                class="w-full rounded-lg px-4 py-3 text-sm font-bold transition hover:brightness-95 {{ $action['color'] }}">
                            {{ $action['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Seleksi --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Status Seleksi</h2>

                @if($applicant->selection)
                    <div class="mt-5 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">Status</p>
                            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($applicant->selection->status) }}">
                                {{ $selectionStatusLabels[$applicant->selection->status] ?? $applicant->selection->status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-lg bg-slate-50 p-3 text-center">
                                <p class="text-xs text-slate-500">Tes</p>
                                <p class="mt-1 font-bold">{{ $applicant->selection->test_score ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3 text-center">
                                <p class="text-xs text-slate-500">Interview</p>
                                <p class="mt-1 font-bold">{{ $applicant->selection->interview_score ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3 text-center">
                                <p class="text-xs text-slate-500">Final</p>
                                <p class="mt-1 font-bold">{{ $applicant->selection->final_score ?? '-' }}</p>
                            </div>
                        </div>

                        <p class="rounded-lg bg-slate-50 p-4 text-xs leading-5 text-slate-600">
                            {{ $applicant->selection->note ?? 'Belum ada catatan seleksi.' }}
                        </p>
                    </div>
                @else
                    <p class="mt-5 text-sm font-semibold text-slate-500">
                        Data seleksi belum dibuat.
                    </p>
                @endif
            </div>

            {{-- Follow Up --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Follow Up Terakhir</h2>

                @if($applicant->latestFollowUp)
                    <div class="mt-5 rounded-lg bg-slate-50 p-5">
                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-700">
                            {{ str_replace('_', ' ', ucfirst($applicant->latestFollowUp->status)) }}
                        </span>

                        <p class="mt-4 text-sm leading-6 text-slate-700">
                            {{ $applicant->latestFollowUp->note }}
                        </p>

                        <p class="mt-4 text-xs font-semibold text-slate-500">
                            Petugas: {{ $applicant->latestFollowUp->officer_name ?? '-' }}
                        </p>

                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            Follow up berikutnya:
                            {{ $applicant->latestFollowUp->next_follow_up_date?->format('d M Y') ?? '-' }}
                        </p>
                    </div>
                @else
                    <p class="mt-5 text-sm font-semibold text-slate-500">
                        Belum ada riwayat follow up.
                    </p>
                @endif
            </div>

            {{-- Integrasi --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Integrasi SIAKAD</h2>

                <div class="mt-5 rounded-lg bg-slate-50 p-5">
                    <p class="text-xs font-semibold text-slate-500">Sync Status</p>
                    <p class="mt-2 font-bold text-polmind-blue">
                        {{ str_replace('_', ' ', ucfirst($applicant->sync_status)) }}
                    </p>

                    <p class="mt-4 text-xs font-semibold text-slate-500">NIM</p>
                    <p class="mt-2 font-bold text-slate-900">
                        {{ $applicant->nim ?? 'Belum tersedia' }}
                    </p>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($applicant->integrationLogs->take(3) as $log)
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold text-slate-700">
                                    {{ $log->system_name }}
                                </p>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($log->status) }}">
                                    {{ $log->status }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ $log->endpoint ?? '-' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">
                            Belum ada log integrasi.
                        </p>
                    @endforelse
                </div>
            </div>

        </aside>
    </div>
</div>
@endsection