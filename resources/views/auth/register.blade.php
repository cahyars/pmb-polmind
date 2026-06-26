@extends('layouts.auth')

@section('title', 'Registrasi Camaba')

@section('content')
<div class="mx-auto w-full max-w-2xl">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/70">

        <div class="text-center">
            <p class="text-sm font-black uppercase tracking-[0.25em] text-polmind-blue">
                PMB Polmind
            </p>
            <h1 class="mt-3 text-3xl font-black text-slate-900">
                Registrasi Camaba
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Isi data awal untuk membuat akun pendaftaran mahasiswa baru.
            </p>
        </div>

        @if($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
                {{ $errors->first() }}
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
        </div>

        <form action="{{ route('register.store') }}" method="POST" class="mt-8 space-y-5">
            @csrf

            <div>
                <label class="text-sm font-bold text-slate-700">Nama Lengkap</label>
                <input type="text"
                       name="full_name"
                       value="{{ old('full_name') }}"
                       required
                       placeholder="Masukkan nama lengkap"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">Email</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           placeholder="email@example.com"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nomor WhatsApp</label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone') }}"
                           required
                           placeholder="08xxxxxxxxxx"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">Pilihan Program Studi</label>
                    <select name="study_program_id"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih Program Studi</option>
                        @foreach($studyPrograms as $program)
                            <option value="{{ $program->id }}" @selected(old('study_program_id') == $program->id)>
                                {{ $program->code }} - {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Jenis Kelas</label>
                    <select name="class_type_id"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        <option value="">Pilih Kelas</option>
                        @foreach($classTypes as $classType)
                            <option value="{{ $classType->id }}" @selected(old('class_type_id') == $classType->id)>
                                {{ $classType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jalur Pendaftaran</label>
                <select name="registration_path"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Pilih Jalur Pendaftaran</option>
                    <option value="umum" @selected(old('registration_path') === 'umum')>
                        Umum
                    </option>
                    <option value="prestasi" @selected(old('registration_path') === 'prestasi')>
                        Prestasi
                    </option>
                    <option value="undangan" @selected(old('registration_path') === 'undangan')>
                        Undangan
                    </option>
                </select>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Pilih jalur pendaftaran sesuai kategori calon mahasiswa.
                </p>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Sumber Informasi</label>
                <select name="source_information"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Pilih sumber informasi</option>
                    @foreach(['Instagram', 'TikTok', 'Website', 'Roadshow Sekolah', 'Teman/Keluarga', 'WhatsApp', 'Lainnya'] as $source)
                        <option value="{{ $source }}" @selected(old('source_information') === $source)>
                            {{ $source }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">Password</label>
                    <input type="password"
                           name="password"
                           required
                           placeholder="Minimal 8 karakter"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Konfirmasi Password</label>
                    <input type="password"
                           name="password_confirmation"
                           required
                           placeholder="Ulangi password"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>
            </div>

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
@endsection