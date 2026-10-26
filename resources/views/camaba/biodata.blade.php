@extends('layouts.camaba')

@section('title', 'Biodata Camaba')
@section('page_title', 'Biodata Camaba')
@section('page_subtitle', 'Isi biodata langkah demi langkah. Setiap langkah tersimpan otomatis saat Anda lanjut.')

@section('content')
@php
    $parent = $applicant->parentData;
    $education = $applicant->education;
    $address = $applicant->address;

    $field = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-500';
    $label = 'text-sm font-bold text-slate-700';
    $hint = 'mt-1 text-xs leading-5 text-slate-500';
    $optional = '<span class="font-semibold text-slate-400">(opsional)</span>';
    $required = '<span class="text-red-500">*</span>';

    $stepLabels = collect($sections)->map(fn ($section) => $section['label'])->put('ringkasan', 'Ringkasan')->all();
    $stepIndex = array_search($step, $steps, true);
    $previousStep = $stepIndex > 0 ? $steps[$stepIndex - 1] : null;
    $isLastFormStep = $step === 'orangtua';

    $selectedOld = fn (string $key, $current) => (string) old($key, $current);
@endphp

<div class="space-y-6">

    {{-- Stepper --}}
    <nav aria-label="Langkah biodata" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <ol class="flex gap-2 overflow-x-auto pb-1">
            @foreach($steps as $index => $stepKey)
                @php
                    $isCurrent = $stepKey === $step;
                    $isComplete = $stepKey === 'ringkasan' ? $biodataComplete : ($sections[$stepKey]['complete'] ?? false);
                @endphp
                <li class="min-w-38 flex-1">
                    <a href="{{ route('camaba.biodata.edit', ['step' => $stepKey]) }}"
                       @if($isCurrent) aria-current="step" @endif
                       class="flex h-full items-center gap-3 rounded-2xl border px-3 py-3 transition
                       {{ $isCurrent ? 'border-polmind-blue bg-blue-50 ring-2 ring-blue-100' : ($isComplete ? 'border-green-300 bg-green-50 hover:border-green-400' : 'border-slate-200 hover:border-slate-300') }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black
                            {{ $isComplete ? 'bg-green-600 text-white' : ($isCurrent ? 'bg-polmind-blue text-white' : 'bg-slate-100 text-slate-500') }}">
                            {{ $isComplete ? '✓' : $index + 1 }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-black {{ $isCurrent ? 'text-polmind-blue' : 'text-slate-700' }}">{{ $stepLabels[$stepKey] }}</span>
                            <span class="block text-[11px] font-semibold {{ $isComplete ? 'text-green-700' : 'text-slate-400' }}">
                                {{ $isComplete ? 'Lengkap' : ($stepKey === 'ringkasan' ? 'Periksa data' : 'Belum lengkap') }}
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">
            <p class="font-bold">Periksa kembali isian berikut:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($step === 'ringkasan')
        @include('camaba.biodata-summary')
    @else
        <form action="{{ route('camaba.biodata.update') }}"
              method="POST"
              class="space-y-6"
              @if($step === 'prodi' && ! $classLocked && ! $programLocked)
                  x-data
                  @submit.prevent="window.Swal.fire({
                      icon: 'question',
                      title: 'Simpan pilihan kelas?',
                      text: 'Nomor pendaftaran final dibuat berdasarkan kelas yang dipilih. Kelas tidak dapat diubah setelah disimpan.',
                      showCancelButton: true,
                      confirmButtonText: 'Ya, simpan',
                      cancelButtonText: 'Periksa lagi',
                      confirmButtonColor: '#003B82',
                  }).then((result) => result.isConfirmed && $el.submit())"
              @endif>
            @csrf
            @method('PUT')
            <input type="hidden" name="step" value="{{ $step }}">

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-polmind-blue">Langkah {{ $stepIndex + 1 }} dari {{ count($steps) }}</p>
                    <h2 class="mt-2 text-xl font-black text-slate-900">{{ $stepLabels[$step] }}</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        {{ $sections[$step]['description'] }} Kolom bertanda {!! $required !!} wajib diisi.
                    </p>
                </div>

                {{-- ===================== DATA DIRI ===================== --}}
                @if($step === 'pribadi')
                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="full_name" class="{{ $label }}">Nama Lengkap {!! $required !!}</label>
                            <input id="full_name" type="text" name="full_name" required maxlength="255"
                                   value="{{ old('full_name', $applicant->full_name) }}"
                                   oninput="const p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p);"
                                   class="{{ $field }} uppercase">
                            <p class="{{ $hint }}">Huruf kapital, sesuai ijazah/KTP.</p>
                        </div>

                        <div>
                            <label for="nik" class="{{ $label }}">NIK {!! $required !!}</label>
                            <input id="nik" type="text" name="nik" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
                                   value="{{ old('nik', $applicant->nik) }}" placeholder="16 digit sesuai KTP/KK"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="nisn" class="{{ $label }}">NISN {!! $optional !!}</label>
                            <input id="nisn" type="text" name="nisn" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                   value="{{ old('nisn', $applicant->nisn) }}" placeholder="10 digit"
                                   class="{{ $field }}">
                            <p class="{{ $hint }}">Cek di nisn.data.kemdikbud.go.id jika lupa.</p>
                        </div>

                        <div>
                            <label for="gender" class="{{ $label }}">Jenis Kelamin {!! $required !!}</label>
                            <select id="gender" name="gender" required class="{{ $field }}">
                                <option value="">Pilih</option>
                                <option value="L" @selected(old('gender', $applicant->gender) === 'L')>Laki-laki</option>
                                <option value="P" @selected(old('gender', $applicant->gender) === 'P')>Perempuan</option>
                            </select>
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
                            <input id="birth_place" type="text" name="birth_place" required maxlength="100"
                                   value="{{ old('birth_place', $applicant->birth_place) }}" placeholder="Kota/kabupaten lahir"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="birth_date" class="{{ $label }}">Tanggal Lahir {!! $required !!}</label>
                            <input id="birth_date" type="date" name="birth_date" required max="{{ now()->subDay()->format('Y-m-d') }}"
                                   value="{{ old('birth_date', $applicant->birth_date?->format('Y-m-d')) }}"
                                   class="{{ $field }}">
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
                            <p class="{{ $hint }}">Pilih "Tidak ada" jika tidak memiliki kebutuhan khusus.</p>
                        </div>

                        <div class="md:col-span-2 border-t border-slate-200 pt-5">
                            <p class="text-sm font-black text-slate-900">Kontak</p>
                        </div>

                        <div>
                            <label for="email" class="{{ $label }}">Email {!! $required !!}</label>
                            <input id="email" type="email" name="email" required maxlength="255"
                                   value="{{ old('email', $applicant->email) }}"
                                   class="{{ $field }}">
                            <p class="{{ $hint }}">Dipakai untuk login dan informasi PMB.</p>
                        </div>

                        <div>
                            <label for="phone" class="{{ $label }}">Nomor HP / WhatsApp {!! $required !!}</label>
                            <input id="phone" type="tel" name="phone" required inputmode="tel" maxlength="16"
                                   value="{{ old('phone', $applicant->phone) }}" placeholder="08xxxxxxxxxx"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="home_phone" class="{{ $label }}">Telepon Rumah {!! $optional !!}</label>
                            <input id="home_phone" type="tel" name="home_phone" inputmode="tel" maxlength="15"
                                   value="{{ old('home_phone', $applicant->home_phone) }}" placeholder="Contoh: 0218901234"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="npwp" class="{{ $label }}">NPWP {!! $optional !!}</label>
                            <input id="npwp" type="text" name="npwp" inputmode="numeric" pattern="[0-9]{15,16}" maxlength="16"
                                   value="{{ old('npwp', $applicant->npwp) }}" placeholder="15/16 digit tanpa titik"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="transportation_id" class="{{ $label }}">Alat Transportasi ke Kampus {!! $optional !!}</label>
                            <select id="transportation_id" name="transportation_id" class="{{ $field }}">
                                <option value="">Pilih</option>
                                @foreach($references['alat_transportasi'] as $code => $name)
                                    <option value="{{ $code }}" @selected($selectedOld('transportation_id', $applicant->transportation_id) === (string) $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2"
                             x-data="{ kps: '{{ old('kps_recipient', $applicant->kps_recipient ? '1' : '0') }}' }">
                            <p class="{{ $label }}">Penerima KPS (Kartu Perlindungan Sosial) {!! $required !!}</p>
                            <div class="mt-2 flex flex-wrap gap-3">
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 has-checked:border-polmind-blue has-checked:bg-blue-50">
                                    <input type="radio" name="kps_recipient" value="0" x-model="kps" @checked(old('kps_recipient', $applicant->kps_recipient ? '1' : '0') === '0')> Bukan penerima
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 has-checked:border-polmind-blue has-checked:bg-blue-50">
                                    <input type="radio" name="kps_recipient" value="1" x-model="kps" @checked(old('kps_recipient', $applicant->kps_recipient ? '1' : '0') === '1')> Penerima KPS
                                </label>
                            </div>

                            <div x-show="kps === '1'" x-cloak class="mt-4 md:max-w-sm">
                                <label for="kps_number" class="{{ $label }}">Nomor KPS {!! $required !!}</label>
                                <input id="kps_number" type="text" name="kps_number" maxlength="50"
                                       value="{{ old('kps_number', $applicant->kps_number) }}"
                                       :required="kps === '1'"
                                       class="{{ $field }}">
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ===================== PROGRAM STUDI ===================== --}}
                @if($step === 'prodi')
                    @if($programLocked)
                        <div class="mt-6 rounded-2xl border border-yellow-200 bg-yellow-50 p-4 text-sm font-semibold text-yellow-800">
                            Pilihan program studi sudah dikunci karena hasil seleksi telah diputuskan.
                        </div>
                    @endif

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="study_program_id" class="{{ $label }}">Pilihan Prodi Pertama {!! $required !!}</label>
                            <select id="study_program_id" name="study_program_id" required @disabled($programLocked) class="{{ $field }}">
                                <option value="">Pilih program studi</option>
                                @foreach($studyPrograms as $program)
                                    <option value="{{ $program->id }}" @selected($selectedOld('study_program_id', $applicant->study_program_id) === (string) $program->id)>
                                        {{ $program->degree }} {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="second_study_program_id" class="{{ $label }}">Pilihan Prodi Kedua {!! $optional !!}</label>
                            <select id="second_study_program_id" name="second_study_program_id" @disabled($programLocked) class="{{ $field }}">
                                <option value="">Tidak memilih</option>
                                @foreach($studyPrograms as $program)
                                    <option value="{{ $program->id }}" @selected($selectedOld('second_study_program_id', $applicant->second_study_program_id) === (string) $program->id)>
                                        {{ $program->degree }} {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="{{ $hint }}">Dipertimbangkan jika kuota pilihan pertama penuh.</p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="{{ $label }}">Jenis Kelas {!! $required !!}</p>

                            @if($classLocked)
                                <div class="mt-2 rounded-2xl border border-green-300 bg-green-50 p-4">
                                    <p class="font-black text-green-800">{{ $applicant->classType?->name }}</p>
                                    <p class="mt-1 text-xs text-green-800/80">
                                        Nomor pendaftaran: <span class="font-black">{{ $applicant->registration_number }}</span>. Kelas sudah dikunci.
                                    </p>
                                </div>
                            @else
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    @foreach($classTypes as $classType)
                                        <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-300 p-4 transition hover:border-polmind-blue has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                                            <input type="radio" name="class_type_id" value="{{ $classType->id }}" required class="mt-1"
                                                   @checked($selectedOld('class_type_id', $applicant->class_type_id) === (string) $classType->id)>
                                            <span>
                                                <span class="block font-black text-slate-900">{{ $classType->name }}</span>
                                                @if($classType->description)
                                                    <span class="mt-1 block text-xs text-slate-500">{{ $classType->description }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-2 rounded-xl bg-blue-50 px-4 py-3 text-xs leading-5 text-polmind-blue">
                                    ℹ️ Nomor pendaftaran final dibuat dari kelas yang Anda pilih (contoh: A{{ preg_replace('/\D/', '', $applicant->admissionWave?->code) ?: 1 }}{{ $applicant->pmbYear?->year }}0001). Kelas tidak dapat diubah setelah disimpan.
                                </p>
                            @endif
                        </div>

                        <div>
                            <label for="registration_path" class="{{ $label }}">Jalur Pendaftaran {!! $required !!}</label>
                            <select id="registration_path" name="registration_path" required @disabled($programLocked) class="{{ $field }}">
                                @foreach(['umum' => 'Umum', 'prestasi' => 'Prestasi', 'undangan' => 'Undangan'] as $value => $name)
                                    <option value="{{ $value }}" @selected(old('registration_path', $applicant->registration_path ?? 'umum') === $value)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                {{-- ===================== ALAMAT ===================== --}}
                @if($step === 'alamat')
                    <div class="mt-6 grid gap-5 md:grid-cols-2"
                         x-data="regionPicker(@js($addressRegion), @js(route('regions.index')))">
                        <div class="md:col-span-2">
                            <label for="address" class="{{ $label }}">Jalan {!! $required !!}</label>
                            <input id="address" type="text" name="address" required maxlength="500"
                                   value="{{ old('address', $address?->address) }}" placeholder="Contoh: Jl. Merdeka No. 10, Blok A"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="hamlet" class="{{ $label }}">Dusun / Perumahan {!! $optional !!}</label>
                            <input id="hamlet" type="text" name="hamlet" maxlength="100"
                                   value="{{ old('hamlet', $address?->hamlet) }}"
                                   class="{{ $field }}">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="rt" class="{{ $label }}">RT {!! $optional !!}</label>
                                <input id="rt" type="text" name="rt" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3"
                                       value="{{ old('rt', $address?->rt) }}" placeholder="001"
                                       class="{{ $field }}">
                            </div>
                            <div>
                                <label for="rw" class="{{ $label }}">RW {!! $optional !!}</label>
                                <input id="rw" type="text" name="rw" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3"
                                       value="{{ old('rw', $address?->rw) }}" placeholder="001"
                                       class="{{ $field }}">
                            </div>
                        </div>

                        @include('camaba.partials.region-select', ['fieldName' => 'region_id'])

                        <div>
                            <label for="village_name" class="{{ $label }}">Desa / Kelurahan {!! $required !!}</label>
                            <input id="village_name" type="text" name="village_name" required maxlength="100"
                                   value="{{ old('village_name', $address?->village_name) }}"
                                   class="{{ $field }}">
                        </div>

                        <div>
                            <label for="postal_code" class="{{ $label }}">Kode Pos {!! $optional !!}</label>
                            <input id="postal_code" type="text" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5"
                                   value="{{ old('postal_code', $address?->postal_code) }}" placeholder="5 digit"
                                   class="{{ $field }}">
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
                    </div>
                @endif

                {{-- ===================== SEKOLAH ASAL ===================== --}}
                @if($step === 'sekolah')
                    <div class="mt-6 space-y-6"
                         x-data="schoolPicker(@js($schoolRegion), @js(route('regions.index')), @js(route('schools.index')), @js([
                             'manual' => (bool) old('is_manual_school', $education?->is_manual_school ?? false),
                             'npsn' => old('school_npsn', $education?->school_npsn),
                             'name' => old('school_name', $education?->school_name),
                             'type' => old('school_type', $education?->school_type),
                             'status' => old('school_status', $education?->school_status),
                         ]))">
                        <div>
                            <p class="text-sm font-black text-slate-900">1. Lokasi sekolah</p>
                            <p class="{{ $hint }}">Pilih wilayah sekolah asal Anda, lalu cari nama sekolahnya.</p>
                            <div class="mt-2 grid gap-5 md:grid-cols-3">
                                @include('camaba.partials.region-select', ['fieldName' => 'school_region_id'])
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-black text-slate-900">2. Nama sekolah {!! $required !!}</p>

                            <input type="hidden" name="is_manual_school" :value="manual ? 1 : 0">
                            <input type="hidden" name="school_npsn" :value="npsn">
                            <input type="hidden" name="school_name" :value="name">
                            <input type="hidden" name="school_type" :value="type">
                            <input type="hidden" name="school_status" :value="status">

                            {{-- Sekolah terpilih --}}
                            <div x-show="name && ! manual" x-cloak class="mt-3 flex items-start justify-between gap-4 rounded-2xl border border-green-300 bg-green-50 p-4">
                                <div>
                                    <p class="font-black text-green-900" x-text="name"></p>
                                    <p class="mt-1 text-xs text-green-800/80">
                                        NPSN <span x-text="npsn"></span> · <span x-text="type"></span> <span x-text="status"></span>
                                    </p>
                                </div>
                                <button type="button" @click="clearSchool()" class="shrink-0 text-xs font-bold text-green-800 underline">Ganti</button>
                            </div>

                            {{-- Pencarian --}}
                            <div x-show="! manual && ! name" class="mt-3">
                                <div x-show="! regency" class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
                                    Pilih provinsi dan kabupaten/kota terlebih dahulu.
                                </div>

                                <div x-show="regency" x-cloak>
                                    <input type="search" x-model.debounce.200ms="query" placeholder="Ketik nama sekolah, contoh: SMKN 1"
                                           class="{{ $field }}" aria-label="Cari nama sekolah">

                                    <p x-show="loadingSchools" class="{{ $hint }}">Memuat daftar sekolah...</p>

                                    <p x-show="! loadingSchools && ! available" class="mt-3 rounded-xl bg-yellow-50 px-4 py-3 text-xs font-semibold text-yellow-800">
                                        Daftar sekolah sedang tidak dapat dimuat. Silakan isi data sekolah secara manual di bawah.
                                    </p>

                                    <ul x-show="! loadingSchools && available" class="mt-3 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-2xl border border-slate-200">
                                        <template x-for="school in filteredSchools" :key="school.npsn">
                                            <li>
                                                <button type="button" @click="pickSchool(school)"
                                                        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-blue-50">
                                                    <span>
                                                        <span class="block text-sm font-bold text-slate-800" x-text="school.name"></span>
                                                        <span class="block text-xs text-slate-500" x-text="'NPSN ' + school.npsn + (school.address ? ' · ' + school.address : '')"></span>
                                                    </span>
                                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-black text-slate-600" x-text="school.type + ' ' + school.status"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <li x-show="filteredSchools.length === 0" class="px-4 py-5 text-center text-sm text-slate-500">
                                            Sekolah tidak ditemukan. Coba kata kunci lain atau isi manual.
                                        </li>
                                    </ul>
                                    <p x-show="district && available && ! loadingSchools" class="{{ $hint }}">
                                        <span x-show="! showAllDistricts">Menampilkan sekolah di kecamatan terpilih.</span>
                                        <button type="button" class="font-bold text-polmind-blue underline" @click="showAllDistricts = ! showAllDistricts"
                                                x-text="showAllDistricts ? 'Tampilkan kecamatan terpilih saja' : 'Tampilkan semua sekolah di kab/kota'"></button>
                                    </p>
                                </div>
                            </div>

                            <label class="mt-4 flex cursor-pointer items-start gap-3 text-sm text-slate-700">
                                <input type="checkbox" class="mt-0.5" :checked="manual" @change="setManual($event.target.checked)">
                                <span>
                                    <span class="font-bold">Sekolah saya tidak ada di daftar</span>
                                    <span class="block text-xs text-slate-500">Misalnya MA/madrasah, Paket C, atau sekolah baru.</span>
                                </span>
                            </label>

                            {{-- Isian manual --}}
                            <div x-show="manual" x-cloak class="mt-4 grid gap-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="manual_school_name" class="{{ $label }}">Nama Sekolah {!! $required !!}</label>
                                    <input id="manual_school_name" type="text" x-model="name" :required="manual" maxlength="255"
                                           placeholder="Contoh: MAN 1 KOTA BEKASI" class="{{ $field }} uppercase">
                                </div>
                                <div>
                                    <label for="manual_school_type" class="{{ $label }}">Jenis Sekolah {!! $required !!}</label>
                                    <select id="manual_school_type" x-model="type" :required="manual" class="{{ $field }}">
                                        <option value="">Pilih</option>
                                        @foreach($schoolTypes as $schoolType)
                                            <option value="{{ $schoolType }}">{{ $schoolType }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="manual_school_status" class="{{ $label }}">Status Sekolah {!! $optional !!}</label>
                                    <select id="manual_school_status" x-model="status" class="{{ $field }}">
                                        <option value="">Pilih</option>
                                        <option value="Negeri">Negeri</option>
                                        <option value="Swasta">Swasta</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="manual_school_npsn" class="{{ $label }}">NPSN {!! $optional !!}</label>
                                    <input id="manual_school_npsn" type="text" x-model="npsn" inputmode="numeric" pattern="[0-9]{8}" maxlength="8"
                                           placeholder="8 digit" class="{{ $field }}">
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-2">
                            <div>
                                <label for="major" class="{{ $label }}">Jurusan {!! $required !!}</label>
                                <input id="major" type="text" name="major" required maxlength="255"
                                       value="{{ old('major', $education?->major) }}" placeholder="Contoh: IPA / Rekayasa Perangkat Lunak"
                                       class="{{ $field }}">
                            </div>
                            <div>
                                <label for="graduation_year" class="{{ $label }}">Tahun Lulus {!! $required !!}</label>
                                <select id="graduation_year" name="graduation_year" required class="{{ $field }}">
                                    <option value="">Pilih tahun</option>
                                    @for($year = now()->year + 1; $year >= now()->year - 10; $year--)
                                        <option value="{{ $year }}" @selected((string) old('graduation_year', $education?->graduation_year) === (string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                                <p class="{{ $hint }}">Pilih {{ now()->year + 1 }} jika masih kelas XII.</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ===================== ORANG TUA / WALI ===================== --}}
                @if($step === 'orangtua')
                    @php
                        $people = [
                            'mother' => ['title' => 'Ibu Kandung', 'nameRequired' => true],
                            'father' => ['title' => 'Ayah', 'nameRequired' => false],
                        ];
                    @endphp

                    <div class="mt-6 space-y-6">
                        @foreach($people as $prefix => $person)
                            <fieldset class="rounded-2xl border border-slate-200 p-5">
                                <legend class="px-2 text-sm font-black text-polmind-blue">Data {{ $person['title'] }}</legend>
                                @include('camaba.partials.parent-fields', ['prefix' => $prefix, 'nameRequired' => $person['nameRequired'], 'withSpecialNeed' => true])
                            </fieldset>
                        @endforeach

                        <div x-data="{ open: {{ old('guardian_name', $parent?->guardian_name) ? 'true' : 'false' }} }" class="rounded-2xl border border-slate-200 p-5">
                            <label class="flex cursor-pointer items-start gap-3">
                                <input type="checkbox" x-model="open" class="mt-1">
                                <span>
                                    <span class="text-sm font-black text-polmind-blue">Saya memiliki wali</span>
                                    <span class="block text-xs text-slate-500">Isi jika Anda tinggal bersama atau dibiayai oleh wali. {!! $optional !!}</span>
                                </span>
                            </label>

                            <div x-show="open" x-cloak class="mt-5">
                                <div class="mb-5 md:max-w-sm">
                                    <label for="guardian_relation" class="{{ $label }}">Hubungan dengan Wali {!! $optional !!}</label>
                                    <input id="guardian_relation" type="text" name="guardian_relation" maxlength="100"
                                           value="{{ old('guardian_relation', $parent?->guardian_relation) }}" placeholder="Contoh: Paman, Kakak"
                                           class="{{ $field }}">
                                </div>
                                @include('camaba.partials.parent-fields', ['prefix' => 'guardian', 'nameRequired' => false, 'withSpecialNeed' => false])
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Navigasi --}}
            <div class="sticky bottom-4 z-20 rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-xl shadow-slate-300/40 backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    @if($previousStep)
                        <a href="{{ route('camaba.biodata.edit', ['step' => $previousStep]) }}"
                           class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            ← <span class="hidden sm:inline">Sebelumnya</span>
                        </a>
                    @else
                        <a href="{{ route('camaba.dashboard') }}"
                           class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            ← <span class="hidden sm:inline">Dashboard</span>
                        </a>
                    @endif

                    <button type="submit"
                            class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                        {{ $isLastFormStep ? 'Simpan & Lihat Ringkasan' : 'Simpan & Lanjutkan' }} →
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>

<script>
    window.regionPicker = (selected, url) => ({
        province: selected.province ?? '',
        regency: '',
        district: '',
        regencies: [],
        districts: [],
        loading: false,

        async init() {
            if (this.province) {
                await this.loadRegions(this.province, 'regencies');
                await this.$nextTick();
                this.regency = selected.regency ?? '';
            }

            if (this.regency) {
                await this.loadRegions(this.regency, 'districts');
                await this.$nextTick();
                this.district = selected.district ?? '';
            }
        },

        async loadRegions(parent, target) {
            this.loading = true;

            try {
                const response = await fetch(`${url}?parent=${encodeURIComponent(parent)}`, { headers: { Accept: 'application/json' } });
                this[target] = response.ok ? await response.json() : [];
            } catch (error) {
                this[target] = [];
            } finally {
                this.loading = false;
            }
        },

        async changeProvince() {
            this.regency = '';
            this.district = '';
            this.regencies = [];
            this.districts = [];

            if (this.province) {
                await this.loadRegions(this.province, 'regencies');
            }
        },

        async changeRegency() {
            this.district = '';
            this.districts = [];

            if (this.regency) {
                await this.loadRegions(this.regency, 'districts');
            }
        },
    });

    // Samakan penulisan "SMA NEGERI 1" dengan "SMAN 1" agar mudah dicari.
    const normalizeSchoolName = (value) => (value || '')
        .toUpperCase()
        .replace(/\b(SMA|SMK)\s+NEGERI\b/g, '$1N')
        .replace(/\b(SMA|SMK)\s+SWASTA\b/g, '$1S')
        .replace(/[^A-Z0-9]+/g, ' ')
        .trim();

    window.schoolPicker = (selected, regionsUrl, schoolsUrl, initial) => {
        const base = window.regionPicker(selected, regionsUrl);

        return {
            ...base,
            schools: [],
            available: true,
            loadingSchools: false,
            showAllDistricts: false,
            query: '',
            manual: initial.manual,
            npsn: initial.npsn ?? '',
            name: initial.name ?? '',
            type: initial.type ?? '',
            status: initial.status ?? '',

            async init() {
                await base.init.call(this);

                if (this.regency) {
                    await this.loadSchools();
                }
            },

            async changeProvince() {
                await base.changeProvince.call(this);
                this.schools = [];
            },

            async changeRegency() {
                await base.changeRegency.call(this);
                await this.loadSchools();
            },

            async loadSchools() {
                this.schools = [];

                if (! this.regency) {
                    return;
                }

                this.loadingSchools = true;

                try {
                    const response = await fetch(`${schoolsUrl}?regency=${encodeURIComponent(this.regency)}`, { headers: { Accept: 'application/json' } });
                    const data = response.ok ? await response.json() : { available: false, schools: [] };

                    this.available = data.available;
                    this.schools = data.schools.map((school) => ({ ...school, search: normalizeSchoolName(school.name) }));
                } catch (error) {
                    this.available = false;
                } finally {
                    this.loadingSchools = false;
                }
            },

            get filteredSchools() {
                const tokens = normalizeSchoolName(this.query).split(' ').filter(Boolean);

                let schools = this.schools;

                if (this.district && ! this.showAllDistricts) {
                    const inDistrict = schools.filter((school) => school.district_id === this.district);
                    schools = inDistrict.length ? inDistrict : schools;
                }

                return schools
                    .filter((school) => tokens.every((token) => school.search.includes(token) || school.npsn.startsWith(token)))
                    .slice(0, 50);
            },

            pickSchool(school) {
                this.npsn = school.npsn;
                this.name = school.name;
                this.type = school.type;
                this.status = school.status;

                if (! this.district) {
                    this.district = school.district_id;
                }
            },

            clearSchool() {
                this.npsn = '';
                this.name = '';
                this.type = '';
                this.status = '';
            },

            setManual(manual) {
                this.manual = manual;
                this.clearSchool();
            },
        };
    };
</script>
@endsection
