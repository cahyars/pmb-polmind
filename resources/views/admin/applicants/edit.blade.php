@extends('layouts.admin')

@section('title', 'Edit Biodata Camaba')
@section('page_title', 'Edit Biodata Camaba')
@section('page_subtitle', 'Betulkan biodata camaba yang daftar online maupun di kampus.')

@section('content')
@php
    $field = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100';
    $label = 'text-sm font-semibold text-slate-700';
    $hint = 'mt-1 text-xs leading-5 text-slate-500';
    $optional = '<span class="font-normal text-slate-400">(opsional)</span>';
    $required = '<span class="text-red-500">*</span>';
    $card = 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
    $choice = 'flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:text-polmind-blue';
    $sectionTitle = fn (string $number, string $title) => '<div class="border-b border-slate-200 pb-4"><p class="text-xs font-bold uppercase tracking-widest text-polmind-blue">' . $number . '</p><h2 class="mt-1 text-base font-semibold text-slate-900">' . $title . '</h2></div>';
    $selectedOld = fn (string $key, $current) => (string) old($key, $current);
    $address = $applicant->address;
    $education = $applicant->education;
    $parent = $applicant->parentData;
    $showUrl = route('admin.applicants.show', $applicant->registration_number);
    $people = [
        'father' => 'Ayah',
        'mother' => 'Ibu Kandung',
        'guardian' => 'Wali',
    ];
@endphp

<form action="{{ route('admin.applicants.update', $applicant->registration_number) }}" method="POST" class="space-y-8">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">
            <p class="font-semibold">Periksa kembali isian berikut:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col justify-between gap-4 rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-slate-700 md:flex-row md:items-center">
        <div>
            <p class="font-semibold text-slate-900">{{ $applicant->full_name }}</p>
            <p>
                <span class="font-mono font-semibold">{{ $applicant->registration_number }}</span>
                · {{ $applicant->admissionWave?->name ?? '-' }}
                · {{ $applicant->studyProgram?->code ?? '-' }} {{ $applicant->classType?->name }}
                · {{ $applicant->registered_via === 'kampus' ? 'Daftar di kampus' : 'Daftar online' }}
            </p>
        </div>
        <p class="text-xs text-slate-600 md:max-w-sm">
            Gelombang, prodi, kelas, dan jalur tidak diubah di sini karena menentukan nomor pendaftaran dan seleksi.
        </p>
    </div>

    @if(in_array($applicant->sync_status, ['proses_sinkron', 'sudah_sinkron'], true))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
            Data camaba ini sudah dikirim ke SIAKAD. Perubahan biodata di sini perlu diambil ulang oleh SIAKAD.
        </div>
    @endif

    {{-- ===================== BIODATA ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 1', 'Data Diri dan Kontak') !!}

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="full_name" class="{{ $label }}">Nama Lengkap {!! $required !!}</label>
                <input id="full_name" type="text" name="full_name" required maxlength="255" value="{{ old('full_name', $applicant->full_name) }}"
                       oninput="const p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p);"
                       class="{{ $field }} uppercase">
            </div>

            <div>
                <label for="nik" class="{{ $label }}">NIK {!! $required !!}</label>
                <input id="nik" type="text" name="nik" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
                       value="{{ old('nik', $applicant->nik) }}" placeholder="16 digit sesuai KTP/KK" class="{{ $field }}">
            </div>

            <div>
                <label for="nisn" class="{{ $label }}">NISN {!! $optional !!}</label>
                <input id="nisn" type="text" name="nisn" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                       value="{{ old('nisn', $applicant->nisn) }}" class="{{ $field }}">
            </div>

            <div>
                <p class="{{ $label }}">Jenis Kelamin {!! $required !!}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach(['L' => 'Laki-laki', 'P' => 'Perempuan'] as $value => $text)
                        <label class="{{ $choice }}">
                            <input type="radio" name="gender" value="{{ $value }}" required @checked(old('gender', $applicant->gender) === $value)> {{ $text }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="religion_id" class="{{ $label }}">Agama {!! $required !!}</label>
                <select id="religion_id" name="religion_id" required class="{{ $field }}">
                    <option value="">Pilih agama</option>
                    @foreach($references['agama'] as $code => $name)
                        <option value="{{ $code }}" @selected($selectedOld('religion_id', $applicant->religion_id) === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="birth_place" class="{{ $label }}">Tempat Lahir {!! $required !!}</label>
                <input id="birth_place" type="text" name="birth_place" required maxlength="100" value="{{ old('birth_place', $applicant->birth_place) }}" class="{{ $field }}">
            </div>

            <div>
                <label for="birth_date" class="{{ $label }}">Tanggal Lahir {!! $required !!}</label>
                <input id="birth_date" type="date" name="birth_date" required max="{{ now()->subDay()->format('Y-m-d') }}"
                       value="{{ old('birth_date', $applicant->birth_date?->format('Y-m-d')) }}" class="{{ $field }}">
                <p class="{{ $hint }}">Bila camaba masih memakai PIN awal, PIN ikut berganti ke tanggal lahir baru.</p>
            </div>

            <div>
                <label for="citizenship" class="{{ $label }}">Kewarganegaraan {!! $required !!}</label>
                <select id="citizenship" name="citizenship" required class="{{ $field }}">
                    @foreach($references['kewarganegaraan'] as $code => $name)
                        <option value="{{ $code }}" @selected($selectedOld('citizenship', $applicant->citizenship ?: 'ID') === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="special_need_id" class="{{ $label }}">Kebutuhan Khusus {!! $required !!}</label>
                <select id="special_need_id" name="special_need_id" required class="{{ $field }}">
                    @foreach($references['kebutuhan_khusus'] as $code => $name)
                        <option value="{{ $code }}" @selected($selectedOld('special_need_id', $applicant->special_need_id ?? 0) === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            @include('camaba.partials.almamater-size', ['current' => $applicant->almamater_size])

            <div class="md:col-span-2 border-t border-slate-200 pt-5">
                <p class="text-sm font-semibold text-slate-900">Kontak</p>
            </div>

            <div>
                <label for="email" class="{{ $label }}">Email {!! $required !!}</label>
                <input id="email" type="email" name="email" required maxlength="255" value="{{ old('email', $applicant->email) }}" class="{{ $field }}">
                <p class="{{ $hint }}">Dipakai camaba untuk login, selain ID Pendaftar.</p>
            </div>

            <div>
                <label for="phone" class="{{ $label }}">No. HP / WhatsApp {!! $required !!}</label>
                <input id="phone" type="tel" name="phone" required inputmode="tel" maxlength="16" value="{{ old('phone', $applicant->phone) }}" placeholder="08xxxxxxxxxx" class="{{ $field }}">
            </div>

            <div>
                <label for="home_phone" class="{{ $label }}">Telepon Rumah {!! $optional !!}</label>
                <input id="home_phone" type="tel" name="home_phone" inputmode="tel" maxlength="15" value="{{ old('home_phone', $applicant->home_phone) }}" class="{{ $field }}">
            </div>

            <div>
                <label for="npwp" class="{{ $label }}">NPWP {!! $optional !!}</label>
                <input id="npwp" type="text" name="npwp" inputmode="numeric" pattern="[0-9]{15,16}" maxlength="16" value="{{ old('npwp', $applicant->npwp) }}" class="{{ $field }}">
            </div>

            <div>
                <label for="transportation_id" class="{{ $label }}">Alat Transportasi {!! $optional !!}</label>
                <select id="transportation_id" name="transportation_id" class="{{ $field }}">
                    <option value="">Pilih</option>
                    @foreach($references['alat_transportasi'] as $code => $name)
                        <option value="{{ $code }}" @selected($selectedOld('transportation_id', $applicant->transportation_id) === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2" x-data="{ kps: @js(old('kps_recipient', $applicant->kps_recipient ? '1' : '0')) }">
                <p class="{{ $label }}">Penerima KPS {!! $required !!}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <label class="{{ $choice }}"><input type="radio" name="kps_recipient" value="0" x-model="kps"> Bukan penerima</label>
                    <label class="{{ $choice }}"><input type="radio" name="kps_recipient" value="1" x-model="kps"> Penerima KPS</label>
                </div>
                <div x-show="kps === '1'" x-cloak class="mt-4 md:max-w-sm">
                    <label for="kps_number" class="{{ $label }}">Nomor KPS {!! $required !!}</label>
                    <input id="kps_number" type="text" name="kps_number" maxlength="50" value="{{ old('kps_number', $applicant->kps_number) }}"
                           :required="kps === '1'" class="{{ $field }}">
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== ALAMAT ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 2', 'Alamat Domisili') !!}
        @unless($addressRequired)
            <p class="mt-4 {{ $hint }}">Alamat belum diisi dan boleh tetap dikosongkan. Bila jalan diisi, kelurahan dan kecamatan wajib diisi.</p>
        @endunless

        <div class="mt-4 grid gap-5 md:grid-cols-2" x-data="regionPicker(@js($addressRegion), @js(route('regions.index')))">
            <div class="md:col-span-2">
                <label for="address" class="{{ $label }}">Jalan dan Nomor Rumah {!! $addressRequired ? $required : '' !!}</label>
                <input id="address" type="text" name="address" maxlength="500" @required($addressRequired)
                       value="{{ old('address', $address?->address) }}" placeholder="Contoh: Jl. Merdeka No. 10" class="{{ $field }}">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="rt" class="{{ $label }}">RT</label>
                    <input id="rt" type="text" name="rt" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" value="{{ old('rt', $address?->rt) }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="rw" class="{{ $label }}">RW</label>
                    <input id="rw" type="text" name="rw" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" value="{{ old('rw', $address?->rw) }}" class="{{ $field }}">
                </div>
            </div>

            <div>
                <label for="hamlet" class="{{ $label }}">Dusun / Perumahan</label>
                <input id="hamlet" type="text" name="hamlet" maxlength="100" value="{{ old('hamlet', $address?->hamlet) }}" class="{{ $field }}">
            </div>

            <div>
                <label for="village_name" class="{{ $label }}">Desa / Kelurahan {!! $addressRequired ? $required : '' !!}</label>
                <input id="village_name" type="text" name="village_name" maxlength="100" @required($addressRequired)
                       value="{{ old('village_name', $address?->village_name) }}" class="{{ $field }}">
            </div>

            <div>
                <label for="postal_code" class="{{ $label }}">Kode Pos</label>
                <input id="postal_code" type="text" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('postal_code', $address?->postal_code) }}" class="{{ $field }}">
            </div>

            <div>
                <label for="residence_type_id" class="{{ $label }}">Jenis Tinggal {!! $optional !!}</label>
                <select id="residence_type_id" name="residence_type_id" class="{{ $field }}">
                    <option value="">Pilih</option>
                    @foreach($references['jenis_tinggal'] as $code => $name)
                        <option value="{{ $code }}" @selected($selectedOld('residence_type_id', $address?->residence_type_id) === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            @include('camaba.partials.region-select', ['fieldName' => 'region_id', 'regionRequired' => $addressRequired])
        </div>
    </section>

    {{-- ===================== SEKOLAH ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 3', 'Pendidikan') !!}
        @unless($schoolRequired)
            <p class="mt-4 {{ $hint }}">Sekolah asal belum diisi dan boleh tetap dikosongkan.</p>
        @endunless

        <div class="mt-6 space-y-6"
             x-data="schoolPicker(@js($schoolRegion), @js(route('regions.index')), @js(route('schools.index')), @js([
                 'manual' => (bool) old('is_manual_school', $education?->is_manual_school ?? false),
                 'npsn' => old('school_npsn', $education?->school_npsn),
                 'name' => old('school_name', $education?->school_name),
                 'type' => old('school_type', $education?->school_type),
                 'status' => old('school_status', $education?->school_status),
             ]))">
            @include('camaba.partials.school-picker', ['schoolRegionRequired' => $schoolRequired])

            <div class="grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-3">
                <div>
                    <label for="major" class="{{ $label }}">Jurusan {!! $optional !!}</label>
                    <input id="major" type="text" name="major" maxlength="255" value="{{ old('major', $education?->major) }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="graduation_year" class="{{ $label }}">Tahun Lulus {!! $optional !!}</label>
                    <select id="graduation_year" name="graduation_year" class="{{ $field }}">
                        <option value="">Pilih tahun</option>
                        @for($year = now()->year + 1; $year >= now()->year - 10; $year--)
                            <option value="{{ $year }}" @selected((string) old('graduation_year', $education?->graduation_year) === (string) $year)>{{ $year }}</option>
                        @endfor
                        @if($education?->graduation_year && $education->graduation_year < now()->year - 10)
                            <option value="{{ $education->graduation_year }}" @selected((string) old('graduation_year', $education->graduation_year) === (string) $education->graduation_year)>{{ $education->graduation_year }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label for="school_address" class="{{ $label }}">Alamat Sekolah {!! $optional !!}</label>
                    <input id="school_address" type="text" name="school_address" maxlength="500" value="{{ old('school_address', $education?->school_address) }}" class="{{ $field }}">
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== ORANG TUA / WALI ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 4', 'Keluarga (Orang Tua / Wali)') !!}
        <p class="mt-4 {{ $hint }}">Nama ibu kandung wajib dilengkapi sebelum data dikirim ke SIAKAD.</p>

        <div class="mt-4 space-y-5">
            @foreach($people as $prefix => $title)
                <details class="rounded-lg border border-slate-200 p-5" @if($prefix !== 'guardian' || old('guardian_name', $parent?->guardian_name)) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-polmind-blue">{{ $title }}</summary>
                    <div class="mt-4">
                        @if($prefix === 'guardian')
                            <div class="mb-5 md:max-w-sm">
                                <label for="guardian_relation" class="{{ $label }}">Hubungan dengan Wali {!! $optional !!}</label>
                                <input id="guardian_relation" type="text" name="guardian_relation" maxlength="100" value="{{ old('guardian_relation', $parent?->guardian_relation) }}" placeholder="Contoh: Paman" class="{{ $field }}">
                            </div>
                        @endif
                        @include('camaba.partials.parent-fields', [
                            'prefix' => $prefix,
                            'nameRequired' => $prefix === 'mother' && $motherRequired,
                            'withSpecialNeed' => $prefix !== 'guardian',
                        ])
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
        <a href="{{ $showUrl }}" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:brightness-110">
            Simpan Perubahan
        </button>
    </div>
</form>

@include('camaba.partials.picker-scripts')
@endsection
