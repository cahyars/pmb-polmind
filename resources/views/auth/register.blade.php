@extends('layouts.auth')

@section('title', 'Registrasi Camaba')

@section('content')
@php
    $field = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-sm font-bold text-slate-700';
    $hint = 'mt-1 text-xs leading-5 text-slate-500';
    $required = '<span class="text-red-500">*</span>';
    $sectionTitle = 'text-xs font-black uppercase tracking-[0.2em] text-polmind-blue';
@endphp

<div class="mx-auto w-full max-w-3xl">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/70 sm:p-8">

        <div class="text-center">
            <p class="text-sm font-black uppercase tracking-[0.25em] text-polmind-blue">
                PMB Polmind
            </p>
            <h1 class="mt-3 text-3xl font-black text-slate-900">
                Registrasi Camaba
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Isi data awal pendaftaran. Biodata lengkap dan berkas dilengkapi setelah login.
            </p>
        </div>

        @if($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">
                <p class="font-bold">Periksa kembali isian berikut:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 rounded-2xl bg-blue-50 p-5">
            <p class="text-sm font-black text-polmind-blue">
                {{ $activePmbYear?->name ?? 'Tahun PMB belum aktif' }}
            </p>
            <p class="mt-1 text-xs font-semibold text-slate-600">
                Gelombang aktif:
                <span class="font-black">
                    {{ $activeWave?->name ?? 'Belum tersedia' }}
                </span>
            </p>
            @if($activeWave)
                {{-- Jadwal gelombang dari Master Data (format sama dengan landing page). --}}
                <dl class="mt-3 grid gap-x-6 gap-y-1 text-xs sm:grid-cols-2">
                    @foreach($activeWave->timeline() as $row)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ $row['label'] }}</dt>
                            <dd class="text-right font-bold text-slate-800">{{ $row['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
            <p class="mt-3 border-t border-blue-100 pt-3 text-xs leading-5 text-slate-600">
                Satu NIK hanya dapat mendaftar <span class="font-black text-polmind-blue">satu kali dalam satu gelombang</span>.
                @if($registrationFee)
                    Setelah akun dibuat, lakukan pembayaran <span class="font-black text-polmind-blue">biaya formulir Rp{{ number_format($registrationFee, 0, ',', '.') }}</span>
                    untuk membuka pengisian biodata dan berkas.
                @endif
            </p>
        </div>

        <form action="{{ route('register.store') }}"
              method="POST"
              class="mt-8 space-y-8"
              x-data
              @submit.prevent="confirmRegistration($el)">
            @csrf

            {{-- ===================== DATA DIRI ===================== --}}
            <section class="space-y-5">
                <p class="{{ $sectionTitle }}">1. Data Diri</p>

                <div>
                    <label for="full_name" class="{{ $label }}">Nama Lengkap {!! $required !!}</label>
                    <input id="full_name"
                           type="text"
                           name="full_name"
                           value="{{ old('full_name') }}"
                           required
                           maxlength="255"
                           autocomplete="name"
                           placeholder="NAMA LENGKAP SESUAI IJAZAH"
                           oninput="const p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p);"
                           class="{{ $field }} uppercase placeholder:normal-case">
                    <p class="{{ $hint }}">Ditulis dengan huruf kapital, sesuai ijazah/KTP.</p>
                </div>

                <div>
                    <label for="nik" class="{{ $label }}">NIK {!! $required !!}</label>
                    <input id="nik"
                           type="text"
                           name="nik"
                           value="{{ old('nik') }}"
                           required
                           inputmode="numeric"
                           pattern="[0-9]{16}"
                           minlength="16"
                           maxlength="16"
                           placeholder="16 digit sesuai KTP/KK"
                           title="NIK harus 16 digit angka"
                           oninput="this.value = this.value.replace(/\D/g, '').slice(0, 16)"
                           class="{{ $field }} tracking-wider">
                    <p class="{{ $hint }}">Nomor Induk Kependudukan pada KTP atau Kartu Keluarga.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="email" class="{{ $label }}">Email {!! $required !!}</label>
                        <input id="email"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               autocomplete="email"
                               placeholder="email@example.com"
                               class="{{ $field }}">
                    </div>

                    <div>
                        <label for="phone" class="{{ $label }}">Nomor WhatsApp {!! $required !!}</label>
                        <input id="phone"
                               type="text"
                               name="phone"
                               value="{{ old('phone') }}"
                               required
                               placeholder="08xxxxxxxxxx"
                               inputmode="tel"
                               autocomplete="tel"
                               class="{{ $field }}">
                    </div>
                </div>
            </section>

            {{-- ===================== JALUR PENDAFTARAN ===================== --}}
            <section id="jalur" class="scroll-mt-6 border-t border-slate-200 pt-8">
                <p class="{{ $sectionTitle }}">2. Jalur Pendaftaran</p>

                <div class="mt-5">
                    <p class="{{ $label }}">Pilih Jalur {!! $required !!}</p>

                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        @foreach($registrationPaths as $value => $path)
                            <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-300 p-4 transition hover:border-polmind-blue has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                                <input type="radio"
                                       name="registration_path"
                                       value="{{ $value }}"
                                       data-label="Jalur {{ $path['label'] }}"
                                       required
                                       class="mt-1"
                                       @checked($selectedPath === $value)>
                                <span class="min-w-0">
                                    <span class="block font-black text-slate-900">Jalur {{ $path['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-600">{{ $path['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ===================== PROGRAM STUDI & KELAS ===================== --}}
            <section class="space-y-5 border-t border-slate-200 pt-8">
                <p class="{{ $sectionTitle }}">3. Program Studi & Kelas</p>

                <div>
                    <label for="study_program_id" class="{{ $label }}">Program Studi {!! $required !!}</label>
                    <select id="study_program_id" name="study_program_id" required class="{{ $field }}">
                        <option value="">Pilih program studi</option>
                        @foreach($studyPrograms as $program)
                            <option value="{{ $program->id }}" @selected((string) old('study_program_id') === (string) $program->id)>
                                {{ $program->degree }} {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="{{ $hint }}">Pilihan prodi kedua (opsional) dapat diisi nanti di biodata.</p>
                </div>

                <div>
                    <p class="{{ $label }}">Jenis Kelas {!! $required !!}</p>

                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @forelse($classTypes as $classType)
                            <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-300 p-4 transition hover:border-polmind-blue has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                                <input type="radio"
                                       name="class_type_id"
                                       value="{{ $classType->id }}"
                                       data-label="{{ $classType->name }}"
                                       required
                                       class="mt-1"
                                       @checked((string) old('class_type_id') === (string) $classType->id)>
                                <span class="min-w-0">
                                    <span class="block font-black text-slate-900">{{ $classType->name }}</span>
                                    @if($classType->description)
                                        <span class="mt-1 block text-xs text-slate-600">{{ $classType->description }}</span>
                                    @endif
                                    @if($classType->schedule)
                                        <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-polmind-blue ring-1 ring-blue-100">
                                            <x-icon name="clock" class="h-3.5 w-3.5" stroke-width="2" /> {{ $classType->schedule }}
                                        </span>
                                    @endif
                                    @if($classType->scheme_points)
                                        <ul class="mt-3 space-y-1 text-xs leading-5 text-slate-600">
                                            @foreach($classType->scheme_points as $point)
                                                <li class="flex gap-2">
                                                    <span class="text-green-600">✓</span>
                                                    <span>{{ $point }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-300 p-5 text-sm text-slate-500 sm:col-span-2">
                                Jenis kelas belum tersedia.
                            </p>
                        @endforelse
                    </div>

                    <p class="mt-2 flex items-start gap-2 rounded-xl bg-yellow-50 px-4 py-3 text-xs leading-5 text-yellow-800">
                        <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" stroke-width="2" />
                        Nomor pendaftaran dibuat dari kelas yang Anda pilih. Kelas tidak dapat diubah setelah mendaftar.
                    </p>
                </div>
            </section>

            {{-- ===================== SEKOLAH ASAL ===================== --}}
            <section class="border-t border-slate-200 pt-8">
                <p class="{{ $sectionTitle }}">4. Sekolah Asal</p>

                <div class="mt-5 space-y-6"
                     x-data="schoolPicker(@js($schoolRegion), @js(route('regions.index')), @js(route('schools.index')), @js([
                         'manual' => (bool) old('is_manual_school', false),
                         'npsn' => old('school_npsn'),
                         'name' => old('school_name'),
                         'type' => old('school_type'),
                         'status' => old('school_status'),
                     ]))">
                    @include('camaba.partials.school-picker')
                </div>
            </section>

            {{-- ===================== AKUN ===================== --}}
            <section class="space-y-5 border-t border-slate-200 pt-8">
                <p class="{{ $sectionTitle }}">5. Akun</p>

                <div>
                    <label for="source_information" class="{{ $label }}">Sumber Informasi <span class="font-semibold text-slate-400">(opsional)</span></label>
                    <select id="source_information" name="source_information" class="{{ $field }}">
                        <option value="">Pilih sumber informasi</option>
                        @foreach(['Instagram', 'TikTok', 'Website', 'Roadshow Sekolah', 'Teman/Keluarga', 'WhatsApp', 'Lainnya'] as $source)
                            <option value="{{ $source }}" @selected(old('source_information') === $source)>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-5 md:grid-cols-2"
                     x-data="{ password: '', confirmation: '', showPassword: false, showConfirmation: false }">
                    <div>
                        <label for="password" class="{{ $label }}">Password {!! $required !!}</label>
                        <div class="relative mt-2">
                            <input id="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   type="password"
                                   name="password"
                                   x-model="password"
                                   required
                                   minlength="8"
                                   autocomplete="new-password"
                                   placeholder="Minimal 8 karakter"
                                   class="w-full rounded-xl border border-slate-300 py-3 pl-4 pr-20 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <button type="button"
                                    @click="showPassword = ! showPassword"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    class="absolute inset-y-0 right-2 my-auto h-9 rounded-lg px-3 text-xs font-bold text-polmind-blue hover:bg-blue-50"
                                    x-text="showPassword ? 'Sembunyikan' : 'Lihat'">Lihat</button>
                        </div>
                        <p class="mt-1 text-xs"
                           :class="password.length === 0 ? 'text-slate-500' : (password.length >= 8 ? 'text-green-600' : 'text-red-600')"
                           x-text="password.length === 0 ? 'Minimal 8 karakter.' : (password.length >= 8 ? '✓ Panjang password sudah cukup.' : 'Kurang ' + (8 - password.length) + ' karakter lagi.')">
                            Minimal 8 karakter.
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="{{ $label }}">Konfirmasi Password {!! $required !!}</label>
                        <div class="relative mt-2">
                            <input id="password_confirmation"
                                   :type="showConfirmation ? 'text' : 'password'"
                                   type="password"
                                   name="password_confirmation"
                                   x-model="confirmation"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Ulangi password"
                                   :class="confirmation.length === 0 ? 'border-slate-300' : (confirmation === password ? 'border-green-500 ring-4 ring-green-100' : 'border-red-400 ring-4 ring-red-100')"
                                   class="w-full rounded-xl border py-3 pl-4 pr-20 text-sm outline-none transition focus:border-polmind-blue">
                            <button type="button"
                                    @click="showConfirmation = ! showConfirmation"
                                    :aria-label="showConfirmation ? 'Sembunyikan konfirmasi password' : 'Tampilkan konfirmasi password'"
                                    class="absolute inset-y-0 right-2 my-auto h-9 rounded-lg px-3 text-xs font-bold text-polmind-blue hover:bg-blue-50"
                                    x-text="showConfirmation ? 'Sembunyikan' : 'Lihat'">Lihat</button>
                        </div>
                        <p x-show="confirmation.length > 0" x-cloak class="mt-1 text-xs font-bold"
                           :class="confirmation === password ? 'text-green-600' : 'text-red-600'"
                           x-text="confirmation === password ? '✓ Password cocok.' : '✗ Password belum cocok.'">
                        </p>
                    </div>
                </div>
            </section>

            <button type="submit"
                    class="w-full rounded-xl bg-polmind-blue px-6 py-4 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Daftar Sekarang
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-black text-polmind-blue hover:underline">
                Login di sini
            </a>
        </p>
    </div>
</div>

@include('camaba.partials.picker-scripts')

<script>
    // Prodi dan kelas tidak bisa diubah sembarangan setelah daftar, jadi minta konfirmasi dulu.
    window.confirmRegistration = (form) => {
        const schoolName = form.querySelector('input[name="school_name"]').value.trim();

        if (! schoolName) {
            window.Swal.fire({
                icon: 'warning',
                title: 'Sekolah asal belum dipilih',
                text: 'Pilih sekolah dari daftar, atau centang "Sekolah saya tidak ada di daftar" lalu isi manual.',
                confirmButtonColor: '#003B82',
            });

            return;
        }

        const program = form.querySelector('#study_program_id');
        const classType = form.querySelector('input[name="class_type_id"]:checked');
        const path = form.querySelector('input[name="registration_path"]:checked');
        const escape = (value) => String(value).replace(/[&<>"']/g, (char) => `&#${char.charCodeAt(0)};`);

        window.Swal.fire({
            icon: 'question',
            title: 'Pastikan pilihan Anda sudah benar',
            html: `<div style="text-align:left;font-size:14px;line-height:1.7">
                    <div>Jalur: <b>${escape(path?.dataset.label ?? '-')}</b></div>
                    <div>Program studi: <b>${escape(program.options[program.selectedIndex].text.trim())}</b></div>
                    <div>Kelas: <b>${escape(classType?.dataset.label ?? '-')}</b></div>
                    <div>Sekolah asal: <b>${escape(schoolName.toUpperCase())}</b></div>
                    <p style="margin-top:8px;font-size:12px;color:#92400e">Kelas tidak dapat diubah setelah mendaftar.</p>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Ya, daftar',
            cancelButtonText: 'Periksa lagi',
            confirmButtonColor: '#003B82',
        }).then((result) => result.isConfirmed && form.submit());
    };
</script>
@endsection
