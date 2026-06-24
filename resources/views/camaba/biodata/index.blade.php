@extends('layouts.camaba')

@section('title', 'Biodata Camaba')
@section('page_title', 'Lengkapi Biodata')
@section('page_subtitle', 'Isi data diri, alamat, pendidikan, dan orang tua/wali dengan benar.')

@section('content')
<div x-data="{ step: 1 }" class="space-y-8">

    {{-- Header Progress --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div>
                <h2 class="text-2xl font-black text-polmind-blue">Form Biodata Camaba</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Lengkapi seluruh data berikut sebagai syarat proses seleksi PMB Politeknik Mitra Industri.
                    Pastikan data sesuai dokumen resmi.
                </p>
            </div>

            <div class="rounded-2xl bg-blue-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kelengkapan Data</p>
                <p class="mt-1 text-2xl font-black text-polmind-blue">25%</p>
            </div>
        </div>

        {{-- Stepper --}}
        <div class="mt-8 grid gap-3 md:grid-cols-5">
            <button type="button"
                    @click="step = 1"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="step === 1 ? 'border-polmind-blue bg-polmind-blue text-white shadow-lg shadow-blue-900/10' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-black"
                          :class="step === 1 ? 'bg-white text-polmind-blue' : 'bg-slate-100 text-slate-500'">
                        1
                    </span>
                    <div>
                        <p class="text-sm font-black">Data Pribadi</p>
                        <p class="mt-1 text-xs opacity-80">Identitas utama</p>
                    </div>
                </div>
            </button>

            <button type="button"
                    @click="step = 2"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="step === 2 ? 'border-polmind-blue bg-polmind-blue text-white shadow-lg shadow-blue-900/10' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-black"
                          :class="step === 2 ? 'bg-white text-polmind-blue' : 'bg-slate-100 text-slate-500'">
                        2
                    </span>
                    <div>
                        <p class="text-sm font-black">Alamat</p>
                        <p class="mt-1 text-xs opacity-80">Domisili camaba</p>
                    </div>
                </div>
            </button>

            <button type="button"
                    @click="step = 3"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="step === 3 ? 'border-polmind-blue bg-polmind-blue text-white shadow-lg shadow-blue-900/10' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-black"
                          :class="step === 3 ? 'bg-white text-polmind-blue' : 'bg-slate-100 text-slate-500'">
                        3
                    </span>
                    <div>
                        <p class="text-sm font-black">Pendidikan</p>
                        <p class="mt-1 text-xs opacity-80">Sekolah asal</p>
                    </div>
                </div>
            </button>

            <button type="button"
                    @click="step = 4"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="step === 4 ? 'border-polmind-blue bg-polmind-blue text-white shadow-lg shadow-blue-900/10' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-black"
                          :class="step === 4 ? 'bg-white text-polmind-blue' : 'bg-slate-100 text-slate-500'">
                        4
                    </span>
                    <div>
                        <p class="text-sm font-black">Orang Tua</p>
                        <p class="mt-1 text-xs opacity-80">Data wali</p>
                    </div>
                </div>
            </button>

            <button type="button"
                    @click="step = 5"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="step === 5 ? 'border-polmind-blue bg-polmind-blue text-white shadow-lg shadow-blue-900/10' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-black"
                          :class="step === 5 ? 'bg-white text-polmind-blue' : 'bg-slate-100 text-slate-500'">
                        5
                    </span>
                    <div>
                        <p class="text-sm font-black">Review</p>
                        <p class="mt-1 text-xs opacity-80">Cek data</p>
                    </div>
                </div>
            </button>
        </div>
    </div>

    <form action="#" method="POST" class="space-y-8">
        @csrf

        {{-- STEP 1 --}}
        <section x-show="step === 1" x-transition>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h3 class="text-xl font-black text-polmind-blue">Data Pribadi</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Isi data sesuai KTP, Kartu Keluarga, atau dokumen resmi lainnya.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-bold text-slate-700">Nama Lengkap</label>
                        <input type="text" name="name" placeholder="Masukkan nama lengkap"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">NIK</label>
                        <input type="text" name="nik" placeholder="16 digit NIK"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <p class="mt-2 text-xs text-slate-500">NIK harus sesuai KTP/Kartu Keluarga.</p>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">NISN</label>
                        <input type="text" name="nisn" placeholder="Masukkan NISN"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Tempat Lahir</label>
                        <input type="text" name="birth_place" placeholder="Contoh: Subang"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Tanggal Lahir</label>
                        <input type="date" name="birth_date"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Jenis Kelamin</label>
                        <select name="gender"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih jenis kelamin</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Agama</label>
                        <select name="religion"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih agama</option>
                            <option>Islam</option>
                            <option>Kristen</option>
                            <option>Katolik</option>
                            <option>Hindu</option>
                            <option>Buddha</option>
                            <option>Konghucu</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Email</label>
                        <input type="email" name="email" placeholder="nama@email.com"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Nomor WhatsApp</label>
                        <input type="text" name="phone" placeholder="08xxxxxxxxxx"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>
                </div>
            </div>
        </section>

        {{-- STEP 2 --}}
        <section x-show="step === 2" x-transition>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h3 class="text-xl font-black text-polmind-blue">Alamat Domisili</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Pilih wilayah secara bertingkat agar data alamat lebih rapi dan mudah disinkronkan.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold text-slate-700">Provinsi</label>
                        <select name="province_code"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih provinsi</option>
                            <option>Jawa Barat</option>
                            <option>DKI Jakarta</option>
                            <option>Banten</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Kabupaten/Kota</label>
                        <select name="regency_code"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih kabupaten/kota</option>
                            <option>Kabupaten Bekasi</option>
                            <option>Kabupaten Subang</option>
                            <option>Kota Bekasi</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Kecamatan</label>
                        <select name="district_code"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih kecamatan</option>
                            <option>Cikarang Barat</option>
                            <option>Subang</option>
                            <option>Tambun Selatan</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Desa/Kelurahan</label>
                        <select name="village_code"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih desa/kelurahan</option>
                            <option>Gandasari</option>
                            <option>Soklat</option>
                            <option>Jatimulya</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-bold text-slate-700">Alamat Lengkap</label>
                        <textarea name="address_detail" rows="4" placeholder="Nama jalan, nomor rumah, blok, patokan alamat"
                                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"></textarea>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">RT</label>
                        <input type="text" name="rt" placeholder="001"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">RW</label>
                        <input type="text" name="rw" placeholder="002"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Kode Pos</label>
                        <input type="text" name="postal_code" placeholder="17530"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>
                </div>
            </div>
        </section>

        {{-- STEP 3 --}}
        <section x-show="step === 3" x-transition>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h3 class="text-xl font-black text-polmind-blue">Data Pendidikan</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Cari sekolah berdasarkan nama sekolah atau NPSN. Detail sekolah akan tampil otomatis setelah dipilih.
                    </p>
                </div>

                <div class="mt-6 space-y-6">
                    <div>
                        <label class="text-sm font-bold text-slate-700">Cari Sekolah / NPSN</label>
                        <input type="text" name="school_search" placeholder="Contoh: SMKN 1 Subang atau 20233677"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <p class="mt-2 text-xs text-slate-500">
                            Nanti field ini akan diubah menjadi searchable select menggunakan Tom Select.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Detail Sekolah Terpilih</p>
                                <h4 class="mt-2 text-lg font-black text-slate-900">SMKN 1 Subang</h4>
                                <p class="mt-1 text-sm text-slate-600">NPSN: 20233677</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-polmind-blue">
                                SMK Negeri
                            </span>
                        </div>

                        <div class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                            <div>
                                <p class="font-bold text-slate-700">Jenjang</p>
                                <p class="mt-1 text-slate-600">SMK</p>
                            </div>
                            <div>
                                <p class="font-bold text-slate-700">Status</p>
                                <p class="mt-1 text-slate-600">Negeri</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="font-bold text-slate-700">Alamat Sekolah</p>
                                <p class="mt-1 text-slate-600">
                                    Jl. Arief Rahman Hakim No.35, Subang, Jawa Barat
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-bold text-slate-700">Jurusan Asal</label>
                            <select name="major_origin"
                                    class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                <option value="">Pilih jurusan asal</option>
                                <option>Rekayasa Perangkat Lunak</option>
                                <option>Teknik Komputer dan Jaringan</option>
                                <option>Teknik Mesin</option>
                                <option>Akuntansi</option>
                                <option>IPA</option>
                                <option>IPS</option>
                                <option>Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-bold text-slate-700">Tahun Lulus</label>
                            <input type="number" name="graduation_year" placeholder="2026"
                                   class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                    </div>

                    <button type="button" class="text-sm font-bold text-polmind-blue hover:underline">
                        Sekolah tidak ditemukan? Ajukan input manual
                    </button>
                </div>
            </div>
        </section>

        {{-- STEP 4 --}}
        <section x-show="step === 4" x-transition>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h3 class="text-xl font-black text-polmind-blue">Data Orang Tua / Wali</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Data ini digunakan untuk kebutuhan administrasi dan komunikasi resmi PMB.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold text-slate-700">Nama Ayah</label>
                        <input type="text" name="father_name" placeholder="Nama ayah"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Pekerjaan Ayah</label>
                        <input type="text" name="father_job" placeholder="Pekerjaan ayah"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Nama Ibu</label>
                        <input type="text" name="mother_name" placeholder="Nama ibu"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Pekerjaan Ibu</label>
                        <input type="text" name="mother_job" placeholder="Pekerjaan ibu"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Penghasilan Orang Tua</label>
                        <select name="parent_income"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih rentang penghasilan</option>
                            <option>Kurang dari Rp 2.000.000</option>
                            <option>Rp 2.000.000 - Rp 5.000.000</option>
                            <option>Rp 5.000.000 - Rp 10.000.000</option>
                            <option>Lebih dari Rp 10.000.000</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Nomor HP Orang Tua/Wali</label>
                        <input type="text" name="parent_phone" placeholder="08xxxxxxxxxx"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    </div>
                </div>
            </div>
        </section>

        {{-- STEP 5 --}}
        <section x-show="step === 5" x-transition>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h3 class="text-xl font-black text-polmind-blue">Review & Pilihan Program Studi</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Cek kembali data dan tentukan pilihan program studi sebelum melanjutkan ke upload berkas.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold text-slate-700">Pilihan Prodi Utama</label>
                        <select name="study_program_id"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih program studi</option>
                            <option>D4 Teknologi Rekayasa Perangkat Lunak</option>
                            <option>D4 Bisnis Digital</option>
                            <option>D4 Teknologi Rekayasa Manufaktur</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Pilihan Prodi Cadangan</label>
                        <select name="backup_study_program_id"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih program studi cadangan</option>
                            <option>D4 Teknologi Rekayasa Perangkat Lunak</option>
                            <option>D4 Bisnis Digital</option>
                            <option>D4 Teknologi Rekayasa Manufaktur</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Jalur Pendaftaran</label>
                        <select name="admission_path_id"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Pilih jalur pendaftaran</option>
                            <option>Reguler</option>
                            <option>Prestasi</option>
                            <option>Beasiswa</option>
                            <option>Rekomendasi Sekolah</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Sumber Informasi</label>
                        <select name="information_source_id"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option value="">Tahu Polmind dari mana?</option>
                            <option>Instagram</option>
                            <option>TikTok</option>
                            <option>Website</option>
                            <option>Guru BK</option>
                            <option>Alumni</option>
                            <option>Teman/Keluarga</option>
                            <option>Kunjungan Sekolah</option>
                            <option>WhatsApp</option>
                        </select>
                    </div>
                </div>

                <div class="mt-8 rounded-2xl border border-yellow-200 bg-yellow-50 p-5">
                    <h4 class="font-black text-yellow-800">Catatan Penting</h4>
                    <p class="mt-2 text-sm leading-6 text-yellow-800">
                        Setelah data dikirim, admin PMB akan melakukan pengecekan. Pastikan data yang dimasukkan benar,
                        karena akan digunakan untuk proses seleksi dan sinkronisasi ke sistem akademik.
                    </p>
                </div>
            </div>
        </section>

        {{-- Navigation Button --}}
        <div class="flex flex-col justify-between gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row">
            <button type="button"
                    @click="step = Math.max(step - 1, 1)"
                    class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                ← Sebelumnya
            </button>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button type="button"
                        class="rounded-xl border border-polmind-blue bg-white px-6 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                    Simpan Draft
                </button>

                <button type="button"
                        x-show="step < 5"
                        @click="step = Math.min(step + 1, 5)"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Lanjutkan →
                </button>

                <button type="submit"
                        x-show="step === 5"
                        class="rounded-xl bg-polmind-yellow px-6 py-3 text-sm font-black text-polmind-blue-dark shadow-lg shadow-yellow-900/10 transition hover:brightness-95">
                    Kirim Biodata
                </button>
            </div>
        </div>
    </form>
</div>
@endsection