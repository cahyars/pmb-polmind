@extends('layouts.camaba')

@section('title', 'Ganti Password')
@section('page_title', 'Ganti Password')
@section('page_subtitle', 'Ganti password akun PMB Anda agar tidak mudah ditebak orang lain.')

@section('content')
@php
    $field = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-sm font-bold text-slate-700';
@endphp

<div class="mx-auto max-w-2xl space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($usingDefaultPin)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
            <x-icon name="key" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" stroke-width="2" />
            <p>Anda masih memakai <span class="font-bold">PIN dari admin (tanggal lahir)</span>. Segera ganti dengan password Anda sendiri.</p>
        </div>
    @endif

    <form action="{{ route('camaba.account.password') }}" method="POST"
          class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
          x-data="{ show: false }">
        @csrf
        @method('PUT')

        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Login dengan <span class="font-bold text-slate-800">{{ $applicant->email }}</span>
            atau ID Pendaftar <span class="font-bold text-slate-800">{{ $applicant->registration_number }}</span>.
        </div>

        <div>
            <label for="current_password" class="{{ $label }}">Password / PIN saat ini</label>
            <input id="current_password" :type="show ? 'text' : 'password'" name="current_password" required autocomplete="current-password" class="{{ $field }}">
            @error('current_password')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="{{ $label }}">Password baru</label>
            <input id="password" :type="show ? 'text' : 'password'" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
            <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter dan bukan tanggal lahir.</p>
            @error('password')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="{{ $label }}">Ulangi password baru</label>
            <input id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation" required minlength="8" autocomplete="new-password" class="{{ $field }}">
        </div>

        <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
            <input type="checkbox" x-model="show" class="rounded border-slate-300"> Tampilkan password
        </label>

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Simpan Password Baru
            </button>
        </div>
    </form>
</div>
@endsection
