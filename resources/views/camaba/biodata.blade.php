@extends('layouts.camaba')

@section('title', 'Biodata Camaba')
@section('page_title', 'Biodata Camaba')
@section('page_subtitle', 'Lengkapi data diri, alamat, pendidikan, dan orang tua/wali.')

@section('content')
@php
    $birthDateValue = $applicant->birth_date
        ? \Illuminate\Support\Carbon::parse($applicant->birth_date)->format('Y-m-d')
        : '';

    $currentYear = now()->year + 1;
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

    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <p class="text-sm font-bold text-blue-100">Nomor Pendaftaran</p>
        <h1 class="mt-2 text-3xl font-black">{{ $applicant->registration_number }}</h1>
        <p class="mt-3 text-sm leading-6 text-blue-100">
            Lengkapi biodata dengan benar. Data ini akan digunakan untuk proses seleksi, daftar ulang, dan integrasi SIAKAD.
        </p>
    </div>

    <form action="{{ route('camaba.biodata.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Data Diri --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-xl font-black text-polmind-blue">Data Diri</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Isi data pribadi sesuai dokumen resmi.
                </p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-slate-700">Nama Lengkap</label>
                    <input type="text" name="full_name" required
                           value="{{ old('full_name', $applicant->full_name) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Email</label>
                    <input type="email" name="email" required
                           value="{{ old('email', $applicant->email) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nomor WhatsApp</label>
                    <input type="text" name="phone" required
                           value="{{ old('phone', $applicant->phone) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">NIK</label>
                    <input type="text" name="nik" required
                           value="{{ old('nik', $applicant->nik) }}"
                           placeholder="16 digit NIK"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">NISN</label>
                    <input type="text" name="nisn"
                           value="{{ old('nisn', $applicant->nisn) }}"
                           placeholder="Opsional"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Jenis Kelamin</label>
                    <select name="gender" required
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih</option>
                        <option value="male" @selected(old('gender', $applicant->gender) === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('gender', $applicant->gender) === 'female')>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Agama</label>
                    <select name="religion" required
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih Agama</option>
                        @foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'] as $religion)
                            <option value="{{ $religion }}" @selected(old('religion', $applicant->religion) === $religion)>
                                {{ $religion }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Tempat Lahir</label>
                    <input type="text" name="birth_place" required
                           value="{{ old('birth_place', $applicant->birth_place) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Tanggal Lahir</label>
                    <input type="date" name="birth_date" required
                           value="{{ old('birth_date', $birthDateValue) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Jalur Pendaftaran</label>
                    <input type="text"
                        value="{{ $applicant->registration_path_label }}"
                        disabled
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-slate-700">Pilihan Prodi Kedua</label>
                    <select name="second_study_program_id"
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Tidak memilih</option>
                        @foreach($studyPrograms as $program)
                            <option value="{{ $program->id }}"
                                    @selected(old('second_study_program_id', $applicant->second_study_program_id) == $program->id)>
                                {{ $program->code }} - {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Alamat --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-xl font-black text-polmind-blue">Alamat Domisili</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Untuk sementara input manual dulu. Nanti bisa kita sambungkan ke dropdown wilayah Indonesia.
                </p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-slate-700">Alamat Lengkap</label>
                    <textarea name="address" rows="4" required
                              class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">{{ old('address', $applicant->address?->address) }}</textarea>
                </div>

                <input type="hidden" name="province_code" value="{{ old('province_code', $applicant->address?->province_code) }}">
                <input type="hidden" name="regency_code" value="{{ old('regency_code', $applicant->address?->regency_code) }}">
                <input type="hidden" name="district_code" value="{{ old('district_code', $applicant->address?->district_code) }}">
                <input type="hidden" name="village_code" value="{{ old('village_code', $applicant->address?->village_code) }}">

                <div>
                    <label class="text-sm font-bold text-slate-700">Provinsi</label>
                    <input type="text" name="province_name" required
                           value="{{ old('province_name', $applicant->address?->province_name) }}"
                           placeholder="Contoh: Jawa Barat"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Kabupaten/Kota</label>
                    <input type="text" name="regency_name" required
                           value="{{ old('regency_name', $applicant->address?->regency_name) }}"
                           placeholder="Contoh: Kabupaten Bekasi"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Kecamatan</label>
                    <input type="text" name="district_name" required
                           value="{{ old('district_name', $applicant->address?->district_name) }}"
                           placeholder="Contoh: Cikarang Barat"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Desa/Kelurahan</label>
                    <input type="text" name="village_name" required
                           value="{{ old('village_name', $applicant->address?->village_name) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div class="grid grid-cols-3 gap-4 md:col-span-2">
                    <div>
                        <label class="text-sm font-bold text-slate-700">RT</label>
                        <input type="text" name="rt"
                               value="{{ old('rt', $applicant->address?->rt) }}"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">RW</label>
                        <input type="text" name="rw"
                               value="{{ old('rw', $applicant->address?->rw) }}"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Kode Pos</label>
                        <input type="text" name="postal_code"
                               value="{{ old('postal_code', $applicant->address?->postal_code) }}"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>
                </div>
            </div>
        </div>

        {{-- Pendidikan --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-xl font-black text-polmind-blue">Pendidikan Terakhir</h2>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">NPSN Sekolah</label>
                    <input type="text" name="school_npsn"
                           value="{{ old('school_npsn', $applicant->education?->school_npsn) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nama Sekolah</label>
                    <input type="text" name="school_name" required
                           value="{{ old('school_name', $applicant->education?->school_name) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Jenis Sekolah</label>
                    <select name="school_type" required
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih</option>
                        @foreach(['SMA', 'SMK', 'MA', 'Paket C', 'Lainnya'] as $type)
                            <option value="{{ $type }}" @selected(old('school_type', $applicant->education?->school_type) === $type)>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Status Sekolah</label>
                    <select name="school_status"
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih</option>
                        @foreach(['Negeri', 'Swasta'] as $status)
                            <option value="{{ $status }}" @selected(old('school_status', $applicant->education?->school_status) === $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Jurusan Asal</label>
                    <input type="text" name="major" required
                           value="{{ old('major', $applicant->education?->major) }}"
                           placeholder="Contoh: Rekayasa Perangkat Lunak"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Tahun Lulus</label>
                    <select name="graduation_year" required
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih Tahun</option>
                        @for($year = $currentYear; $year >= $currentYear - 10; $year--)
                            <option value="{{ $year }}" @selected(old('graduation_year', $applicant->education?->graduation_year) == $year)>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        {{-- Orang Tua --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-xl font-black text-polmind-blue">Data Orang Tua / Wali</h2>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">Nama Ayah</label>
                    <input type="text" name="father_name"
                           value="{{ old('father_name', $applicant->parentData?->father_name) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Pekerjaan Ayah</label>
                    <input type="text" name="father_job"
                           value="{{ old('father_job', $applicant->parentData?->father_job) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nama Ibu</label>
                    <input type="text" name="mother_name"
                           value="{{ old('mother_name', $applicant->parentData?->mother_name) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Pekerjaan Ibu</label>
                    <input type="text" name="mother_job"
                           value="{{ old('mother_job', $applicant->parentData?->mother_job) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nama Wali</label>
                    <input type="text" name="guardian_name"
                           value="{{ old('guardian_name', $applicant->parentData?->guardian_name) }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Hubungan Wali</label>
                    <input type="text" name="guardian_relation"
                           value="{{ old('guardian_relation', $applicant->parentData?->guardian_relation) }}"
                           placeholder="Contoh: Paman, Kakak, Saudara"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-slate-700">Rentang Penghasilan Orang Tua</label>
                    <select name="parent_income_range"
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih rentang penghasilan</option>
                        @foreach([
                            '< Rp1.000.000',
                            'Rp1.000.000 - Rp3.000.000',
                            'Rp3.000.000 - Rp5.000.000',
                            'Rp5.000.000 - Rp10.000.000',
                            '> Rp10.000.000',
                        ] as $income)
                            <option value="{{ $income }}" @selected(old('parent_income_range', $applicant->parentData?->parent_income_range) === $income)>
                                {{ $income }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Action --}}
        <div class="sticky bottom-4 z-20 rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-xl shadow-slate-300/40 backdrop-blur">
            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <p class="text-sm font-semibold text-slate-600">
                    Pastikan data sudah benar sebelum disimpan.
                </p>

                <div class="flex gap-3">
                    <a href="{{ route('camaba.dashboard') }}"
                       class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Kembali
                    </a>

                    <button type="submit"
                            class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                        Simpan Biodata
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection