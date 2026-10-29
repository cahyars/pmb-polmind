@extends('layouts.admin')

@section('title', 'Tambah Pendaftar')
@section('page_title', 'Tambah Pendaftar')
@section('page_subtitle', 'Input pendaftar yang datang langsung ke kampus, mengikuti urutan Formulir Pendaftaran fisik.')

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
    $people = [
        'father' => 'Ayah',
        'mother' => 'Ibu',
        'guardian' => 'Wali',
    ];
@endphp

<form action="{{ route('admin.applicants.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
    @csrf

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

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-slate-700">
        Pendaftar masuk {{ $pmbYear->name }}. Akun login camaba memakai <span class="font-semibold">email</span> yang diisi,
        dengan password awal <span class="font-semibold">tanggal lahir (ddmmyyyy)</span>, contoh lahir 16 Maret 2007 menjadi <span class="font-mono font-semibold">16032007</span>.
        Bagian bertanda {!! $required !!} wajib diisi; bagian lain boleh dilengkapi camaba sendiri lewat akunnya.
    </div>

    {{-- ===================== PENDAFTARAN ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 1', 'Data Pendaftaran') !!}

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="admission_wave_id" class="{{ $label }}">Gelombang {!! $required !!}</label>
                <select id="admission_wave_id" name="admission_wave_id" required class="{{ $field }}">
                    @foreach($waves as $wave)
                        <option value="{{ $wave->id }}" @selected((string) old('admission_wave_id', $defaultWaveId) === (string) $wave->id)>
                            {{ $wave->name }}{{ $wave->status === 'closed' ? ' (ditutup)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="{{ $label }}">Jalur Masuk {!! $required !!}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($registrationPaths as $path)
                        <label class="{{ $choice }}">
                            <input type="radio" name="registration_path" value="{{ $path->code }}" required @checked(old('registration_path') === $path->code)>
                            {{ $path->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="{{ $label }}">Pilihan Kelas {!! $required !!}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($classTypes as $classType)
                        <label class="{{ $choice }}">
                            <input type="radio" name="class_type_id" value="{{ $classType->id }}" required @checked((string) old('class_type_id') === (string) $classType->id)>
                            {{ $classType->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="source_information" class="{{ $label }}">Sumber Informasi {!! $optional !!}</label>
                <select id="source_information" name="source_information" class="{{ $field }}">
                    <option value="">Pilih</option>
                    @foreach(\App\Models\Applicant::SOURCE_OPTIONS as $source)
                        <option value="{{ $source }}" @selected(old('source_information') === $source)>{{ $source }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="study_program_id" class="{{ $label }}">Program Studi Pilihan 1 {!! $required !!}</label>
                <select id="study_program_id" name="study_program_id" required class="{{ $field }}">
                    <option value="">Pilih program studi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected((string) old('study_program_id') === (string) $program->id)>{{ $program->degree }} {{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="second_study_program_id" class="{{ $label }}">Program Studi Pilihan 2 {!! $optional !!}</label>
                <select id="second_study_program_id" name="second_study_program_id" class="{{ $field }}">
                    <option value="">Tidak memilih</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected((string) old('second_study_program_id') === (string) $program->id)>{{ $program->degree }} {{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            @include('camaba.partials.almamater-size', ['current' => null])
        </div>
    </section>

    {{-- ===================== BIODATA ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 2', 'Biodata Mahasiswa') !!}

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="full_name" class="{{ $label }}">Nama Lengkap {!! $required !!}</label>
                <input id="full_name" type="text" name="full_name" required maxlength="255" value="{{ old('full_name') }}"
                       oninput="const p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p);"
                       class="{{ $field }} uppercase">
            </div>

            <div>
                <label for="nik" class="{{ $label }}">NIK {!! $required !!}</label>
                <input id="nik" type="text" name="nik" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
                       value="{{ old('nik') }}" placeholder="16 digit sesuai KTP/KK" class="{{ $field }}">
            </div>

            <div>
                <p class="{{ $label }}">Jenis Kelamin {!! $required !!}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach(['L' => 'Laki-laki', 'P' => 'Perempuan'] as $value => $text)
                        <label class="{{ $choice }}">
                            <input type="radio" name="gender" value="{{ $value }}" required @checked(old('gender') === $value)> {{ $text }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="religion_id" class="{{ $label }}">Agama {!! $required !!}</label>
                <select id="religion_id" name="religion_id" required class="{{ $field }}">
                    <option value="">Pilih agama</option>
                    @foreach($references['agama'] as $code => $name)
                        <option value="{{ $code }}" @selected((string) old('religion_id') === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="birth_place" class="{{ $label }}">Tempat Lahir {!! $required !!}</label>
                <input id="birth_place" type="text" name="birth_place" required maxlength="100" value="{{ old('birth_place') }}" class="{{ $field }}">
            </div>

            <div>
                <label for="birth_date" class="{{ $label }}">Tanggal Lahir {!! $required !!}</label>
                <input id="birth_date" type="date" name="birth_date" required max="{{ now()->subDay()->format('Y-m-d') }}"
                       value="{{ old('birth_date') }}" class="{{ $field }}">
                <p class="{{ $hint }}">Menjadi password awal camaba (ddmmyyyy).</p>
            </div>

            <div>
                <label for="citizenship" class="{{ $label }}">Kewarganegaraan {!! $required !!}</label>
                <select id="citizenship" name="citizenship" required class="{{ $field }}">
                    @foreach($references['kewarganegaraan'] as $code => $name)
                        <option value="{{ $code }}" @selected((string) old('citizenship', 'ID') === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="nisn" class="{{ $label }}">NISN {!! $optional !!}</label>
                <input id="nisn" type="text" name="nisn" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" value="{{ old('nisn') }}" class="{{ $field }}">
            </div>

            <div>
                <label for="email" class="{{ $label }}">Email {!! $required !!}</label>
                <input id="email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" class="{{ $field }}">
                <p class="{{ $hint }}">Dipakai camaba untuk login.</p>
            </div>

            <div>
                <label for="phone" class="{{ $label }}">No. HP / WhatsApp {!! $required !!}</label>
                <input id="phone" type="tel" name="phone" required inputmode="tel" maxlength="16" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" class="{{ $field }}">
            </div>

            <details class="md:col-span-2 rounded-lg border border-slate-200 p-4" @if(old('npwp') || old('home_phone') || old('transportation_id') || old('kps_recipient')) open @endif>
                <summary class="cursor-pointer text-sm font-semibold text-polmind-blue">Data tambahan (tidak ada di formulir fisik)</summary>

                <div class="mt-4 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="special_need_id" class="{{ $label }}">Kebutuhan Khusus {!! $required !!}</label>
                        <select id="special_need_id" name="special_need_id" required class="{{ $field }}">
                            @foreach($references['kebutuhan_khusus'] as $code => $name)
                                <option value="{{ $code }}" @selected((string) old('special_need_id', 0) === (string) $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="kps_recipient" class="{{ $label }}">Penerima KPS {!! $required !!}</label>
                        <select id="kps_recipient" name="kps_recipient" required class="{{ $field }}">
                            <option value="0" @selected(old('kps_recipient', '0') === '0')>Bukan penerima</option>
                            <option value="1" @selected(old('kps_recipient') === '1')>Penerima KPS</option>
                        </select>
                    </div>

                    <div>
                        <label for="kps_number" class="{{ $label }}">Nomor KPS {!! $optional !!}</label>
                        <input id="kps_number" type="text" name="kps_number" maxlength="50" value="{{ old('kps_number') }}" class="{{ $field }}">
                    </div>

                    <div>
                        <label for="npwp" class="{{ $label }}">NPWP {!! $optional !!}</label>
                        <input id="npwp" type="text" name="npwp" inputmode="numeric" pattern="[0-9]{15,16}" maxlength="16" value="{{ old('npwp') }}" class="{{ $field }}">
                    </div>

                    <div>
                        <label for="home_phone" class="{{ $label }}">Telepon Rumah {!! $optional !!}</label>
                        <input id="home_phone" type="tel" name="home_phone" inputmode="tel" maxlength="15" value="{{ old('home_phone') }}" class="{{ $field }}">
                    </div>

                    <div>
                        <label for="transportation_id" class="{{ $label }}">Alat Transportasi {!! $optional !!}</label>
                        <select id="transportation_id" name="transportation_id" class="{{ $field }}">
                            <option value="">Pilih</option>
                            @foreach($references['alat_transportasi'] as $code => $name)
                                <option value="{{ $code }}" @selected((string) old('transportation_id') === (string) $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </details>
        </div>
    </section>

    {{-- ===================== ALAMAT ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 3', 'Alamat') !!}
        <p class="mt-4 {{ $hint }}">Boleh dikosongkan bila formulir belum diisi; camaba bisa melengkapinya di akunnya. Bila jalan diisi, kelurahan dan kecamatan wajib diisi.</p>

        <div class="mt-4 grid gap-5 md:grid-cols-2" x-data="regionPicker(@js($addressRegion), @js(route('regions.index')))">
            <div class="md:col-span-2">
                <label for="address" class="{{ $label }}">Jalan dan Nomor Rumah</label>
                <input id="address" type="text" name="address" maxlength="500" value="{{ old('address') }}" placeholder="Contoh: Jl. Merdeka No. 10" class="{{ $field }}">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="rt" class="{{ $label }}">RT</label>
                    <input id="rt" type="text" name="rt" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" value="{{ old('rt') }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="rw" class="{{ $label }}">RW</label>
                    <input id="rw" type="text" name="rw" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" value="{{ old('rw') }}" class="{{ $field }}">
                </div>
            </div>

            <div>
                <label for="hamlet" class="{{ $label }}">Dusun</label>
                <input id="hamlet" type="text" name="hamlet" maxlength="100" value="{{ old('hamlet') }}" class="{{ $field }}">
            </div>

            <div>
                <label for="village_name" class="{{ $label }}">Kelurahan</label>
                <input id="village_name" type="text" name="village_name" maxlength="100" value="{{ old('village_name') }}" class="{{ $field }}">
            </div>

            <div>
                <label for="postal_code" class="{{ $label }}">Kode Pos</label>
                <input id="postal_code" type="text" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('postal_code') }}" class="{{ $field }}">
            </div>

            @include('camaba.partials.region-select', ['fieldName' => 'region_id', 'regionRequired' => false])
        </div>
    </section>

    {{-- ===================== SEKOLAH ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 4', 'Data Sekolah') !!}

        <div class="mt-6 space-y-6"
             x-data="schoolPicker(@js($schoolRegion), @js(route('regions.index')), @js(route('schools.index')), @js([
                 'manual' => (bool) old('is_manual_school', false),
                 'npsn' => old('school_npsn'),
                 'name' => old('school_name'),
                 'type' => old('school_type'),
                 'status' => old('school_status'),
             ]))">
            @include('camaba.partials.school-picker')

            <div class="grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-3">
                <div>
                    <label for="major" class="{{ $label }}">Jurusan {!! $optional !!}</label>
                    <input id="major" type="text" name="major" maxlength="255" value="{{ old('major') }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="graduation_year" class="{{ $label }}">Tahun Lulus {!! $optional !!}</label>
                    <select id="graduation_year" name="graduation_year" class="{{ $field }}">
                        <option value="">Pilih tahun</option>
                        @for($year = now()->year + 1; $year >= now()->year - 10; $year--)
                            <option value="{{ $year }}" @selected((string) old('graduation_year') === (string) $year)>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="school_address" class="{{ $label }}">Alamat Sekolah {!! $optional !!}</label>
                    <input id="school_address" type="text" name="school_address" maxlength="500" value="{{ old('school_address') }}" class="{{ $field }}">
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== ORANG TUA / WALI ===================== --}}
    <section class="{{ $card }}">
        {!! $sectionTitle('Bagian 5', 'Biodata Orang Tua / Wali') !!}
        <p class="mt-4 {{ $hint }}">Semua isian boleh dikosongkan. Nama ibu kandung wajib dilengkapi sebelum data dikirim ke SIAKAD.</p>

        <div class="mt-4 space-y-5">
            @foreach($people as $prefix => $title)
                <details class="rounded-lg border border-slate-200 p-5" @if($prefix !== 'guardian' || old('guardian_name')) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-polmind-blue">{{ $title }}</summary>
                    <div class="mt-4">
                        @if($prefix === 'guardian')
                            <div class="mb-5 md:max-w-sm">
                                <label for="guardian_relation" class="{{ $label }}">Hubungan dengan Wali {!! $optional !!}</label>
                                <input id="guardian_relation" type="text" name="guardian_relation" maxlength="100" value="{{ old('guardian_relation') }}" placeholder="Contoh: Paman" class="{{ $field }}">
                            </div>
                        @endif
                        @include('camaba.partials.parent-fields', ['prefix' => $prefix, 'nameRequired' => false, 'withSpecialNeed' => $prefix !== 'guardian'])
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- ===================== PEMBAYARAN FORMULIR ===================== --}}
    <section class="{{ $card }}" x-data="{ paid: @js(old('registration_paid', '0')) }">
        {!! $sectionTitle('Bagian 6', 'Pembayaran Formulir') !!}

        <div class="mt-6 grid gap-3 md:grid-cols-2">
            <label class="{{ str_replace('items-center', 'items-start', $choice) }}">
                <input type="radio" name="registration_paid" value="0" x-model="paid" class="mt-1">
                <span>
                    <span class="block">Belum bayar</span>
                    <span class="block text-xs font-normal text-slate-500">Camaba membayar nanti dan upload bukti lewat akunnya, lalu diverifikasi seperti biasa.</span>
                </span>
            </label>
            <label class="{{ str_replace('items-center', 'items-start', $choice) }}">
                <input type="radio" name="registration_paid" value="1" x-model="paid" class="mt-1">
                <span>
                    <span class="block">Sudah bayar{{ $registrationFee ? ' Rp' . number_format((float) $registrationFee, 0, ',', '.') : '' }}</span>
                    <span class="block text-xs font-normal text-slate-500">Langsung tercatat valid dan kwitansi terbit. Camaba bisa langsung melengkapi biodata dan berkas.</span>
                </span>
            </label>
        </div>

        <div x-show="paid === '1'" x-cloak class="mt-5 grid gap-5 rounded-lg border border-slate-200 bg-slate-50 p-5 md:grid-cols-3">
            <div>
                <label for="payment_method_id" class="{{ $label }}">Tujuan Pembayaran {!! $required !!}</label>
                <select id="payment_method_id" name="payment_method_id" :required="paid === '1'" class="{{ $field }}">
                    <option value="">Pilih</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->label }}</option>
                    @endforeach
                </select>
                @if($paymentMethods->isEmpty())
                    <p class="{{ $hint }} text-red-600">Belum ada tujuan pembayaran aktif. Tambahkan di menu Tujuan Pembayaran.</p>
                @endif
            </div>
            <div>
                <label for="paid_at" class="{{ $label }}">Tanggal Bayar {!! $required !!}</label>
                <input id="paid_at" type="date" name="paid_at" max="{{ now()->format('Y-m-d') }}" value="{{ old('paid_at', now()->format('Y-m-d')) }}" :required="paid === '1'" class="{{ $field }}">
            </div>
            <div>
                <label for="payment_proof" class="{{ $label }}">Bukti Bayar {!! $optional !!}</label>
                <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" class="{{ $field }} file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-polmind-blue">
                <p class="{{ $hint }}">JPG, PNG, atau PDF, maks. 2 MB.</p>
            </div>
        </div>
    </section>

    <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
        <a href="{{ route('admin.applicants.index') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:brightness-110">
            Simpan Pendaftar
        </button>
    </div>
</form>

@include('camaba.partials.picker-scripts')
@endsection
