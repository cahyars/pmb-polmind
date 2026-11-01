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
            'valid', 'diterima', 'paid', 'success' => 'bg-green-100 text-green-700',
            'menunggu_verifikasi', 'waiting_verification', 'pending', 'sebagian_upload' => 'bg-yellow-100 text-yellow-700',
            'ditolak', 'rejected', 'failed' => 'bg-red-100 text-red-700',
            'biodata_lengkap' => 'bg-blue-100 text-polmind-blue',
            default => 'bg-slate-100 text-slate-600',
        };
    };

    $ref = \App\Models\RefOption::class;
    $rn = $applicant->registration_number;
    $registrationInvoice = $applicant->registrationInvoice;
    $canWaive = $registrationInvoice && ! $registrationInvoice->isWaived() && $registrationInvoice->canBeWaived();

    $waitingDocuments = $applicant->documents->where('status', 'menunggu_verifikasi')->count();
    $waitingPayments = $applicant->registrationInvoice?->payments->where('status', 'waiting_verification')->count()
        + ($applicant->reRegistrationInvoice?->payments->where('status', 'waiting_verification')->count() ?? 0);

    // Menu samping; Data Pendaftar punya sub-tab sendiri. Tab aktif disimpan di #hash URL.
    $tabs = [
        'data' => ['label' => 'Data Pendaftar', 'icon' => 'user', 'count' => 0],
        'berkas' => ['label' => 'Berkas', 'icon' => 'document-text', 'count' => $waitingDocuments],
        'pembayaran' => ['label' => 'Pembayaran', 'icon' => 'card', 'count' => $waitingPayments],
        'seleksi' => ['label' => 'Seleksi & Prodi', 'icon' => 'clipboard-check', 'count' => 0],
        'lainnya' => ['label' => 'Follow Up & SIAKAD', 'icon' => 'sync', 'count' => 0],
    ];

    if ($activityLogs !== null) {
        $tabs['riwayat'] = ['label' => 'Riwayat', 'icon' => 'clock', 'count' => 0];
    }

    $subTabs = [
        'pelengkap' => 'Pelengkap',
        'domisili' => 'Domisili',
        'pendidikan' => 'Pendidikan',
        'keluarga' => 'Keluarga',
    ];

    $shortcuts = [
        ['label' => 'Verifikasi Berkas', 'desc' => $waitingDocuments ? $waitingDocuments . ' berkas menunggu' : 'Cek & validasi berkas', 'url' => route('admin.documents.show', $rn), 'icon' => 'document-text', 'color' => 'bg-polmind-blue text-white'],
        ['label' => 'Verifikasi Pembayaran', 'desc' => $waitingPayments ? $waitingPayments . ' pembayaran menunggu' : 'Riwayat & validasi bayar', 'url' => route('admin.payments.index', ['keyword' => $rn]), 'icon' => 'card', 'color' => 'bg-green-600 text-white'],
        ['label' => 'Seleksi & Keputusan', 'desc' => 'Nilai tes, set diterima', 'url' => route('admin.selections.show', $rn), 'icon' => 'clipboard-check', 'color' => 'bg-purple-600 text-white'],
        ['label' => 'Daftar Ulang', 'desc' => 'Tagihan & validasi daftar ulang', 'url' => route('admin.re-registrations.index', ['keyword' => $rn]), 'icon' => 'cap', 'color' => 'bg-teal-600 text-white'],
        ['label' => 'Generate NIM', 'desc' => $applicant->nim ? 'NIM ' . $applicant->nim : 'Bubuhkan NIM otomatis/manual', 'url' => route('admin.nim.index', ['keyword' => $rn]), 'icon' => 'badge-check', 'color' => 'bg-sky-600 text-white'],
        ['label' => 'Follow Up', 'desc' => 'Catatan & jadwal kontak', 'url' => route('admin.follow-ups.index', ['keyword' => $rn]), 'icon' => 'chat', 'color' => 'bg-polmind-yellow text-polmind-blue-dark'],
        ['label' => 'Integrasi SIAKAD', 'desc' => 'Payload & status sinkron', 'url' => route('admin.integrations.index', ['keyword' => $rn]), 'icon' => 'sync', 'color' => 'bg-slate-700 text-white'],
        ['label' => 'Edit Biodata', 'desc' => 'Data diri, alamat, sekolah, keluarga', 'url' => route('admin.applicants.edit', $rn), 'icon' => 'pencil-square', 'color' => 'bg-orange-600 text-white'],
    ];

    $initials = collect(explode(' ', trim($applicant->full_name)))->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $address = $applicant->address;
    $education = $applicant->education;
    $parent = $applicant->parentData;
@endphp

<div class="space-y-6"
     x-data="{
         tabs: @js(array_keys($tabs)),
         subs: @js(array_keys($subTabs)),
         tab: 'data',
         sub: 'pelengkap',
         waiver: @js($errors->has('waiver_reason')),
         key: @js('pmb.admin.applicant-tab.' . $rn),
         init() {
             let saved = location.hash.slice(1);
             if (! saved) { try { saved = sessionStorage.getItem(this.key) } catch (error) {} }
             this.open(@js(old('_installment_form') ? 'pembayaran' : null) ?? saved ?? 'data');
             window.addEventListener('hashchange', () => this.open(location.hash.slice(1)));
         },
         open(name) {
             if (this.subs.includes(name)) { this.tab = 'data'; this.sub = name; }
             else if (this.tabs.includes(name)) { this.tab = name; }
             const current = this.tab === 'data' ? this.sub : this.tab;
             history.replaceState(null, '', '#' + current);
             try { sessionStorage.setItem(this.key, current) } catch (error) {}
         },
     }">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if((session('walk_in_account') || session('login_reset')) && $loginPin)
        <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <x-icon name="key" class="h-5 w-5 text-amber-600" stroke-width="2" />
                Kirim informasi login ke camaba
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Camaba login dengan ID Pendaftar <span class="font-mono font-semibold">{{ $applicant->registration_number }}</span>
                (atau email {{ $applicant->email }}) dan PIN <span class="font-mono font-semibold">{{ $loginPin }}</span> (tanggal lahir).
            </p>
            @include('admin.applicants.partials.login-info-button', ['large' => true])
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[210px_1fr] lg:grid-rows-[auto_1fr_auto]">

        {{-- Kolom kiri: foto dan menu --}}
        <aside class="min-w-0 space-y-5">
            <div class="hidden lg:block">
                @if($applicant->photoDocument)
                    <button type="button"
                            @click="$store.preview.show(@js(route('admin.documents.file', $applicant->photoDocument)), @js(basename($applicant->photoDocument->file_path)))"
                            class="block w-full" title="Lihat pas foto">
                        <img src="{{ route('admin.documents.file', $applicant->photoDocument) }}"
                             alt="Foto {{ $applicant->full_name }}"
                             class="aspect-[3/4] w-full rounded-lg bg-slate-100 object-cover">
                    </button>
                @else
                    <div class="flex aspect-[3/4] w-full items-center justify-center rounded-lg bg-polmind-blue text-4xl font-semibold text-white" aria-hidden="true">
                        {{ $initials }}
                    </div>
                @endif
            </div>

            <nav class="-mx-1 flex gap-1 overflow-x-auto px-1 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0" role="tablist" aria-label="Kelompok data camaba">
                @foreach($tabs as $key => $item)
                    <button type="button" role="tab"
                            @click="open(@js($key))"
                            :aria-selected="tab === @js($key)"
                            :class="tab === @js($key)
                                ? 'border-polmind-blue bg-blue-50 font-bold text-polmind-blue'
                                : 'border-transparent bg-slate-50 font-medium text-slate-600 hover:bg-slate-100'"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-left text-sm transition lg:border-b-0 lg:border-l-[3px]">
                        <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" stroke-width="1.8" />
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if($item['count'])
                            <span class="rounded-full bg-yellow-100 px-1.5 text-[11px] font-bold text-yellow-700" title="menunggu verifikasi">{{ $item['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>

        </aside>

        {{-- Kolom kanan: identitas ringkas + isi tab --}}
        <div class="min-w-0 lg:col-start-2 lg:row-span-2 lg:row-start-1">
            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-start">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-slate-900">{{ $applicant->full_name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        <span class="font-mono font-semibold text-slate-700">{{ $applicant->registration_number }}</span>
                        · {{ $applicant->email }} · {{ $applicant->phone ?? '-' }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ url('/admin/applicants') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-polmind-blue hover:bg-blue-50">
                        <x-icon name="arrow-left" class="h-3.5 w-3.5" stroke-width="2" />
                        Kembali
                    </a>
                    <a href="{{ route('admin.applicants.edit', $rn) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-polmind-yellow px-3 py-2 text-xs font-bold text-polmind-blue-dark hover:brightness-95">
                        <x-icon name="pencil-square" class="h-3.5 w-3.5" stroke-width="2" />
                        Edit Biodata
                    </a>
                    @if($canWaive)
                        <button type="button" @click="waiver = true"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                            <x-icon name="badge-check" class="h-3.5 w-3.5" stroke-width="2" />
                            Gratiskan Formulir
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid gap-x-6 gap-y-1.5 rounded-lg border-l-4 border-blue-300 bg-blue-50/70 px-4 py-3 text-sm sm:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    'Periode' => $applicant->pmbYear?->name,
                    'Gelombang' => $applicant->admissionWave?->name,
                    'Jalur' => $applicant->registration_path_label,
                    'Sistem Kuliah' => $applicant->classType?->name,
                    'Prodi' => $applicant->enrolled_study_program?->code,
                    'Cara Daftar' => $applicant->registered_via === 'kampus'
                        ? 'Datang ke kampus, diinput oleh ' . ($applicant->registered_by_name ?? 'admin')
                        : 'Online',
                ] as $label => $value)
                    <div class="flex gap-3">
                        <span class="w-24 shrink-0 text-xs font-semibold leading-5 text-polmind-blue/70">{{ $label }}</span>
                        <span class="min-w-0 text-slate-800">{{ $value ?: '-' }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                @foreach([
                    'Registrasi' => [$registrationStatusLabels[$applicant->registration_status] ?? $applicant->registration_status, $applicant->registration_status],
                    'Berkas' => [$documentStatusLabels[$applicant->document_status] ?? $applicant->document_status, $applicant->document_status],
                    'Pembayaran' => [$paymentStatusLabels[$applicant->payment_status] ?? $applicant->payment_status, $applicant->payment_status],
                    'Seleksi' => [$selectionStatusLabels[$applicant->selection_status] ?? $applicant->selection_status, $applicant->selection_status],
                ] as $label => [$value, $status])
                    <span class="inline-flex items-center gap-1.5">
                        {{ $label }}
                        <span class="rounded-full px-2.5 py-0.5 font-bold {{ $badgeClass($status) }}">{{ $value }}</span>
                    </span>
                @endforeach
                @if($registrationInvoice?->isWaived())
                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-bold text-green-700" title="{{ $registrationInvoice->waiver_reason }}">Formulir Gratis</span>
                @endif
            </div>

            {{-- Tab: Data Pendaftar --}}
            <div x-show="tab === 'data'" x-cloak class="mt-5 overflow-hidden rounded-lg border border-slate-200">
                <div class="grid md:grid-cols-2 md:divide-x md:divide-slate-100">
                    @include('admin.applicants.partials.kv', ['rows' => [
                        'Nama Lengkap' => $applicant->full_name,
                        'Tempat Lahir' => $applicant->birth_place,
                        'Tanggal Lahir' => $applicant->birth_date?->translatedFormat('d/m/Y'),
                        'Jenis Kelamin' => $applicant->gender_label,
                        'Agama' => $applicant->religion_name,
                        'Kewarganegaraan' => $ref::nameOf($ref::KEWARGANEGARAAN, $applicant->citizenship),
                    ]])
                    @include('admin.applicants.partials.kv', ['rows' => [
                        'Tanggal Daftar' => $applicant->created_at?->translatedFormat('d/m/Y H:i'),
                        'NIM' => $applicant->nim,
                        'Sumber Informasi' => $applicant->source_information,
                        'Kebutuhan Khusus' => $ref::nameOf($ref::KEBUTUHAN_KHUSUS, $applicant->special_need_id),
                        'Penerima KPS' => $applicant->kps_recipient ? 'Ya (' . ($applicant->kps_number ?: '-') . ')' : 'Tidak',
                        'Ukuran Jas Almamater' => $applicant->almamater_size,
                    ]])
                </div>

                <div class="flex gap-1 overflow-x-auto bg-slate-50 px-4 pt-3" role="tablist" aria-label="Detail data pendaftar">
                    @foreach($subTabs as $key => $label)
                        <button type="button" role="tab"
                                @click="open(@js($key))"
                                :aria-selected="sub === @js($key)"
                                :class="sub === @js($key) ? 'bg-polmind-blue text-white' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
                                class="shrink-0 rounded-t-md px-4 py-2 text-xs font-semibold transition">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="border-t-2 border-polmind-blue">
                    <div x-show="sub === 'pelengkap'" class="grid md:grid-cols-2 md:divide-x md:divide-slate-100">
                        @include('admin.applicants.partials.kv', ['rows' => [
                            'NIK' => $applicant->nik,
                            'NISN' => $applicant->nisn,
                            'NPWP' => $applicant->npwp,
                            'Email' => $applicant->email,
                        ]])
                        @include('admin.applicants.partials.kv', ['rows' => [
                            'Nomor HP' => $applicant->phone,
                            'Telepon Rumah' => $applicant->home_phone,
                            'Alat Transportasi' => $ref::nameOf($ref::ALAT_TRANSPORTASI, $applicant->transportation_id),
                        ]])
                    </div>

                    <div x-show="sub === 'domisili'" x-cloak class="grid md:grid-cols-2 md:divide-x md:divide-slate-100">
                        @include('admin.applicants.partials.kv', ['rows' => [
                            'Jalan' => $address?->address,
                            'Dusun' => $address?->hamlet,
                            'RT/RW' => ($address?->rt ?? '-') . '/' . ($address?->rw ?? '-'),
                            'Desa/Kelurahan' => $address?->village_name,
                            'Kecamatan' => $address?->district_name,
                        ]])
                        @include('admin.applicants.partials.kv', ['rows' => [
                            'Kab/Kota' => $address?->regency_name,
                            'Provinsi' => $address?->province_name,
                            'Kode Pos' => $address?->postal_code,
                            'Jenis Tinggal' => $ref::nameOf($ref::JENIS_TINGGAL, $address?->residence_type_id),
                            'ID Wilayah' => $address?->region_id,
                        ]])
                    </div>

                    <div x-show="sub === 'pendidikan'" x-cloak class="grid md:grid-cols-2 md:divide-x md:divide-slate-100">
                        @include('admin.applicants.partials.kv', ['rows' => [
                            'Asal Sekolah' => $education?->school_name,
                            'NPSN' => $education?->school_npsn,
                            'Jenis Sekolah' => $education?->school_type,
                        ]])
                        @include('admin.applicants.partials.kv', ['rows' => [
                            'Status Sekolah' => $education?->school_status,
                            'Jurusan' => $education?->major,
                            'Tahun Lulus' => $education?->graduation_year,
                        ]])
                    </div>

                    <div x-show="sub === 'keluarga'" x-cloak class="overflow-x-auto">
                        <table class="w-full min-w-[820px] text-left text-sm">
                            <thead class="bg-polmind-blue text-xs text-white [&_th]:font-semibold">
                                <tr>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5">Nama</th>
                                    <th class="px-4 py-2.5">NIK</th>
                                    <th class="px-4 py-2.5">Tgl Lahir</th>
                                    <th class="px-4 py-2.5">Pendidikan</th>
                                    <th class="px-4 py-2.5">Pekerjaan</th>
                                    <th class="px-4 py-2.5">Penghasilan</th>
                                    <th class="px-4 py-2.5">Telepon</th>
                                </tr>
                            </thead>
                            <tbody class="table-zebra">
                                @foreach(['Ayah' => 'father', 'Ibu Kandung' => 'mother', 'Wali' => 'guardian'] as $title => $prefix)
                                    <tr>
                                        <td class="px-4 py-2.5 font-semibold text-slate-700">
                                            {{ $title }}
                                            @if($prefix === 'guardian' && $parent?->guardian_relation)
                                                <span class="block text-xs font-normal text-slate-500">{{ $parent->guardian_relation }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-800">{{ $parent?->{$prefix . '_name'} ?: '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $parent?->{$prefix . '_nik'} ?: '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $parent?->{$prefix . '_birth_date'}?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $ref::nameOf($ref::PENDIDIKAN, $parent?->{$prefix . '_education_id'}) ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $ref::nameOf($ref::PEKERJAAN, $parent?->{$prefix . '_job_id'}) ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $ref::nameOf($ref::PENGHASILAN, $parent?->{$prefix . '_income_id'}) ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $parent?->{$prefix . '_phone'} ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Tab: Berkas --}}
            <div x-show="tab === 'berkas'" x-cloak class="mt-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Berkas Pendaftaran</h2>
                    <a href="{{ route('admin.documents.show', $rn) }}"
                       class="rounded-lg bg-polmind-blue px-3 py-2 text-xs font-semibold text-white hover:brightness-110">
                        Verifikasi Berkas
                    </a>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-polmind-blue text-xs text-white [&_th]:font-semibold">
                            <tr>
                                <th class="px-4 py-2.5">No</th>
                                <th class="px-4 py-2.5">Jenis Berkas</th>
                                <th class="px-4 py-2.5">File</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5">Catatan</th>
                                <th class="px-4 py-2.5">Verifikator</th>
                            </tr>
                        </thead>
                        <tbody class="table-zebra">
                            @forelse($applicant->documents as $document)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">
                                        {{ $document->documentType?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($document->file_path)
                                            <button type="button"
                                                    @click="$store.preview.show(@js(route('admin.documents.file', $document)), @js(basename($document->file_path)))"
                                                    class="text-left font-semibold text-green-700 hover:underline">
                                                {{ $document->file_name ?? basename($document->file_path) }}
                                            </button>
                                        @else
                                            <p class="font-semibold text-slate-700">{{ $document->file_name ?? '-' }}</p>
                                        @endif
                                        <p class="text-xs text-slate-500">{{ $document->file_size_kb ?? 0 }} KB</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($document->status) }}">
                                            {{ str_replace('_', ' ', ucfirst($document->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $document->admin_note ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $document->verified_by_name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                        Belum ada berkas yang diupload.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: Pembayaran --}}
            <div x-show="tab === 'pembayaran'" x-cloak class="mt-5 space-y-6">
                @if($canWaive)
                    <div class="flex flex-col justify-between gap-3 rounded-lg border border-green-200 bg-green-50 p-4 sm:flex-row sm:items-center">
                        <p class="text-sm leading-6 text-green-900">
                            Biaya formulir bisa dibebaskan atas perintah pimpinan. Camaba langsung dapat mengisi biodata tanpa mengunggah bukti bayar.
                        </p>
                        <button type="button" @click="waiver = true"
                                class="shrink-0 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700">
                            Gratiskan Formulir
                        </button>
                    </div>
                @elseif($registrationInvoice?->isWaived())
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm leading-6 text-green-800">
                        <p class="font-semibold">Biaya formulir sudah dibebaskan.</p>
                        <p class="mt-1 text-xs">{{ $registrationInvoice->waiver_reason }}</p>
                    </div>
                @endif

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach([
                        'Invoice Pendaftaran' => $applicant->registrationInvoice,
                        'Invoice Daftar Ulang' => $applicant->reRegistrationInvoice,
                    ] as $title => $invoice)
                        <div class="rounded-lg border border-slate-200 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>

                                @if($invoice)
                                    @php
                                        // Ikon mengacu ke pembayaran terakhir (cicilan bisa lebih dari satu pembayaran).
                                        $payments = $invoice->payments->sortByDesc('id');
                                        $proofPayment = $payments->first(fn ($payment) => $payment->proof_file_path);
                                        $receiptPayment = $payments->firstWhere('status', 'valid');
                                        $latest = $payments->count() > 1 ? ' terakhir' : '';
                                    @endphp
                                    <div class="flex shrink-0 gap-1.5">
                                        @include('admin.applicants.partials.icon-action', [
                                            'icon' => 'eye',
                                            'label' => 'Lihat bukti bayar' . $latest,
                                            'url' => $proofPayment ? route('admin.payments.proof', $proofPayment) : null,
                                            'previewName' => $proofPayment ? basename($proofPayment->proof_file_path) : null,
                                            'disabledLabel' => $invoice->isWaived() ? 'Formulir gratis, tanpa bukti bayar' : 'Belum ada bukti bayar',
                                        ])
                                        @include('admin.applicants.partials.icon-action', [
                                            'icon' => 'printer',
                                            'label' => 'Cetak kwitansi' . $latest,
                                            'url' => $receiptPayment ? route('admin.payments.receipt', $receiptPayment) : null,
                                            'newTab' => true,
                                            'disabledLabel' => $invoice->isWaived() ? 'Formulir gratis, tanpa kwitansi' : 'Kwitansi tersedia setelah pembayaran divalidasi',
                                        ])
                                    </div>
                                @endif
                            </div>

                            @if($invoice)
                                <div class="mt-4 rounded-lg bg-slate-50 p-4">
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
                                                {{ $invoice->isWaived() ? 'Gratis (dibebaskan)' : str_replace('_', ' ', ucfirst($invoice->status)) }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($invoice->isWaived())
                                        <p class="mt-4 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-600">
                                            Dibebaskan oleh <span class="font-semibold">{{ $invoice->waived_by_name }}</span>
                                            pada {{ $invoice->waived_at->translatedFormat('d/m/Y H:i') }}.
                                            Dasar: {{ $invoice->waiver_reason }}
                                        </p>
                                    @endif
                                </div>

                                <div class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200">
                                    @foreach($invoice->items as $item)
                                        <div class="flex justify-between gap-3 px-4 py-2.5 text-sm">
                                            <span class="font-semibold text-slate-700">{{ $item->name }}</span>
                                            <span class="font-bold text-slate-900">
                                                Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-4 rounded-lg bg-slate-50 p-4 text-sm font-semibold text-slate-500">
                                    Invoice belum tersedia.
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($applicant->reRegistrationInvoice && auth('web')->user()?->isAdmin())
                    @include('admin.applicants.partials.installments', ['invoice' => $applicant->reRegistrationInvoice])
                @endif
            </div>

            {{-- Tab: Seleksi & Prodi --}}
            <div x-show="tab === 'seleksi'" x-cloak class="mt-5 space-y-6">
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="bg-polmind-blue text-xs text-white [&_th]:font-semibold">
                            <tr>
                                <th class="w-20 px-4 py-2.5 text-center">Pilihan</th>
                                <th class="px-4 py-2.5">Program Studi</th>
                                <th class="px-4 py-2.5">Status Lulusan</th>
                            </tr>
                        </thead>
                        <tbody class="table-zebra">
                            @forelse(array_filter([1 => $applicant->studyProgram, 2 => $applicant->secondStudyProgram]) as $choice => $program)
                                <tr>
                                    <td class="px-4 py-2.5 text-center text-slate-600">{{ $choice }}</td>
                                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ $program->degree }} - {{ $program->name }}</td>
                                    <td class="px-4 py-2.5">
                                        @if($applicant->acceptedChoice() === $choice)
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Diterima</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">Belum memilih prodi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-slate-900">Status Seleksi</h2>
                            <a href="{{ route('admin.selections.show', $rn) }}" class="text-xs font-semibold text-polmind-blue hover:underline">Seleksi &amp; Keputusan</a>
                        </div>

                        @if($applicant->selection)
                            <div class="mt-4 space-y-4 text-sm">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass($applicant->selection->status) }}">
                                    {{ $selectionStatusLabels[$applicant->selection->status] ?? $applicant->selection->status }}
                                </span>

                                <dl class="divide-y divide-slate-100 rounded-lg bg-slate-50 px-3">
                                    @foreach($applicant->selection->scores->sortBy(fn ($score) => $score->component?->sort_order) as $score)
                                        <div class="flex justify-between gap-3 py-2 text-xs">
                                            <dt class="text-slate-500">{{ $score->component?->name ?? '-' }}</dt>
                                            <dd class="font-bold text-slate-800">{{ $score->score }}</dd>
                                        </div>
                                    @endforeach
                                    <div class="flex justify-between gap-3 py-2 text-xs">
                                        <dt class="font-semibold text-polmind-blue">Nilai Akhir</dt>
                                        <dd class="font-bold text-polmind-blue">{{ $applicant->selection->final_score ?? '-' }}</dd>
                                    </div>
                                </dl>

                                <p class="rounded-lg bg-slate-50 p-4 text-xs leading-5 text-slate-600">
                                    {{ $applicant->selection->note ?? 'Belum ada catatan seleksi.' }}
                                </p>
                            </div>
                        @else
                            <p class="mt-4 text-sm font-semibold text-slate-500">
                                Data seleksi belum dibuat.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-lg border border-slate-200 p-5">
                        <h2 class="text-base font-semibold text-slate-900">Jalur Pendaftaran</h2>
                        <span class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ \App\Models\RegistrationPath::badgeClass($applicant->registration_path) }}">
                            {{ $applicant->registration_path_label }}
                        </span>
                        <p class="mt-3 text-xs leading-5 text-slate-600">
                            {{ \App\Models\RegistrationPath::forCode($applicant->registration_path)?->description ?? 'Keterangan jalur belum diisi di Master Data.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tab: Follow Up & SIAKAD --}}
            <div x-show="tab === 'lainnya'" x-cloak class="mt-5 grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900">Follow Up Terakhir</h2>
                        <a href="{{ route('admin.follow-ups.index', ['keyword' => $rn]) }}" class="text-xs font-semibold text-polmind-blue hover:underline">Buka Follow Up</a>
                    </div>

                    @if($applicant->latestFollowUp)
                        <div class="mt-4 rounded-lg bg-slate-50 p-4">
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
                                {{ $applicant->latestFollowUp->next_follow_up_date?->format('d/m/Y') ?? '-' }}
                            </p>
                        </div>
                    @else
                        <p class="mt-4 text-sm font-semibold text-slate-500">
                            Belum ada riwayat follow up.
                        </p>
                    @endif
                </div>

                <div class="rounded-lg border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900">Integrasi SIAKAD</h2>
                        <a href="{{ route('admin.integrations.index', ['keyword' => $rn]) }}" class="text-xs font-semibold text-polmind-blue hover:underline">Buka Integrasi</a>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4 rounded-lg bg-slate-50 p-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">Sync Status</p>
                            <p class="mt-1 font-bold text-polmind-blue">
                                {{ str_replace('_', ' ', ucfirst($applicant->sync_status)) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500">NIM</p>
                            <p class="mt-1 font-bold text-slate-900">
                                {{ $applicant->nim ?? 'Belum tersedia' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
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
            </div>

            {{-- Tab: Riwayat (Log Aktivitas camaba ini, khusus Admin PMB) --}}
            @if($activityLogs !== null)
                <div x-show="tab === 'riwayat'" x-cloak class="mt-5">
                    <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Perubahan</h2>
                        <p class="text-xs text-slate-500">
                            {{ $activityLogs->count() >= 200 ? '200 aktivitas terakhir' : $activityLogs->count() . ' aktivitas' }} ·
                            <a href="{{ route('admin.activity-logs.index', ['keyword' => $applicant->registration_number]) }}" class="font-semibold text-polmind-blue hover:underline">buka di Log Aktivitas</a>
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200">
                        @include('admin.activity-logs.partials.table', ['logs' => $activityLogs, 'showSubject' => false])
                    </div>
                </div>
            @endif
        </div>

        {{-- Akun camaba: di bawah menu (desktop), turun ke bawah konten (HP) --}}
        <div class="min-w-0 lg:col-start-1 lg:row-start-2">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Akun Camaba</p>
            @include('admin.applicants.partials.login-info-button', ['large' => false])
        </div>

        {{-- Pintasan ke halaman proses, selebar kartu di bagian bawah --}}
        <div class="min-w-0 border-t border-slate-200 pt-5 lg:col-span-2 lg:row-start-3">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Buka Proses</p>
            <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($shortcuts as $shortcut)
                    <a href="{{ $shortcut['url'] }}"
                       class="flex items-center gap-3 rounded-lg px-4 py-3 transition hover:brightness-95 {{ $shortcut['color'] }}">
                        <x-icon :name="$shortcut['icon']" class="h-5 w-5 shrink-0" stroke-width="1.8" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold">{{ $shortcut['label'] }}</span>
                            <span class="block text-xs opacity-80">{{ $shortcut['desc'] }}</span>
                        </span>
                        <x-icon name="arrow-up-right" class="h-4 w-4 shrink-0 opacity-70" stroke-width="2" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Modal Formulir Gratis --}}
    @if($canWaive)
        <div x-show="waiver" x-cloak
             @keydown.escape.window="waiver = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.outside="waiver = false"
                 class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl"
                 role="dialog" aria-modal="true" aria-labelledby="waiver_title">
                <h2 id="waiver_title" class="text-base font-semibold text-slate-900">Gratiskan Formulir</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Bebaskan biaya formulir <span class="font-semibold">{{ $applicant->full_name }}</span> atas perintah pimpinan,
                    contoh untuk pendaftar dari asal sekolah tertentu. Camaba langsung dapat mengisi biodata tanpa mengunggah bukti bayar.
                </p>

                <form action="{{ route('admin.applicants.waive-registration-fee', $rn) }}"
                      method="POST"
                      class="mt-5 space-y-3">
                    @csrf
                    @method('PATCH')

                    <label for="waiver_reason" class="text-sm font-semibold text-slate-700">Dasar pembebasan</label>
                    <textarea id="waiver_reason" name="waiver_reason" rows="3" required maxlength="1000"
                              placeholder="Contoh: Perintah Direktur, siswa SMK Negeri 1 Batam digratiskan formulir"
                              class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">{{ old('waiver_reason', $education?->school_name ? 'Perintah pimpinan: pendaftar dari ' . $education->school_name : '') }}</textarea>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="waiver = false"
                                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700">
                            Gratiskan Formulir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
