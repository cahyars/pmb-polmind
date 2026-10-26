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
                Buat akun terlebih dahulu. Pilihan program studi dan data lainnya dilengkapi setelah login.
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
            @if($registrationFee)
                <p class="mt-3 border-t border-blue-100 pt-3 text-xs leading-5 text-slate-600">
                    Setelah akun dibuat, lakukan pembayaran <span class="font-black text-polmind-blue">biaya formulir Rp{{ number_format($registrationFee, 0, ',', '.') }}</span>
                    untuk membuka pengisian biodata dan berkas.
                </p>
            @endif
        </div>

        <form action="{{ route('register.store') }}" method="POST" class="mt-8 space-y-5">
            @csrf

            <div>
                <label class="text-sm font-bold text-slate-700">Nama Lengkap</label>
                <input type="text"
                       name="full_name"
                       value="{{ old('full_name') }}"
                       required
                       maxlength="255"
                       autocomplete="name"
                       placeholder="NAMA LENGKAP SESUAI IJAZAH"
                       oninput="const p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p);"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase outline-none transition placeholder:normal-case focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                <p class="mt-1 text-xs text-slate-500">Ditulis dengan huruf kapital, sesuai ijazah/KTP.</p>
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
                           inputmode="tel"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Sumber Informasi <span class="font-semibold text-slate-400">(opsional)</span></label>
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

            <div class="grid gap-5 md:grid-cols-2"
                 x-data="{ password: '', confirmation: '', showPassword: false, showConfirmation: false }">
                <div>
                    <label for="password" class="text-sm font-bold text-slate-700">Password</label>
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
                    <label for="password_confirmation" class="text-sm font-bold text-slate-700">Konfirmasi Password</label>
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