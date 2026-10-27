@extends('layouts.auth')

@section('title', 'Login Camaba')

@section('content')
<div class="mx-auto w-full max-w-md">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/70">

        <div class="text-center">
            <p class="text-sm font-black uppercase tracking-[0.25em] text-polmind-blue">
                PMB Polmind
            </p>
            <h1 class="mt-3 text-3xl font-black text-slate-900">
                Login Camaba
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Masuk menggunakan email dan password yang sudah didaftarkan.
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

        @php
            $forgotMessage = 'Halo Admin PMB Polmind, saya lupa kata sandi akun PMB saya.';
        @endphp

        <form action="{{ route('login.store') }}" method="POST" class="mt-8 space-y-5"
              x-data="{ email: @js(old('email', '')) }">
            @csrf

            <div>
                <label class="text-sm font-bold text-slate-700">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       x-model="email"
                       required
                       placeholder="email@example.com"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Password</label>
                <input type="password"
                       name="password"
                       required
                       placeholder="Masukkan password"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Ingat saya
            </label>

            {{-- Reset kata sandi dibantu admin; email yang diketik ikut dikirim di pesan WA. --}}
            <p class="flex items-start gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm leading-6 text-slate-600">
                <x-icon name="key" class="mt-1 h-4 w-4 shrink-0 text-polmind-blue" stroke-width="2" />
                <span>
                    Lupa kata sandi? Silakan
                    <a href="{{ \App\Services\AdminWhatsApp::url($forgotMessage) }}"
                       :href="@js(\App\Services\AdminWhatsApp::url(null)) + '?text=' + encodeURIComponent(@js($forgotMessage) + (email.trim() ? ' Email akun: ' + email.trim() : ''))"
                       target="_blank"
                       rel="noopener"
                       class="font-black text-green-700 underline decoration-green-300 underline-offset-2 hover:text-green-800">chat admin</a>
                    lewat WhatsApp.
                </span>
            </p>

            <button type="submit"
                    class="w-full rounded-xl bg-polmind-blue px-6 py-4 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Login
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-black text-polmind-blue hover:underline">
                Registrasi di sini
            </a>
        </p>
    </div>
</div>
@endsection