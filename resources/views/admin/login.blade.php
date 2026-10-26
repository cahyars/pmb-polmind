@extends('layouts.auth')

@section('title', 'Login Admin PMB')

@section('content')
<div class="mx-auto w-full max-w-md">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/70">

        <div class="text-center">
            <p class="text-sm font-black uppercase tracking-[0.25em] text-polmind-blue">
                PMB Polmind
            </p>
            <h1 class="mt-3 text-3xl font-black text-slate-900">
                Login Admin
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Masuk untuk mengelola sistem penerimaan mahasiswa baru.
            </p>
        </div>

        @if($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.store') }}" method="POST" class="mt-8 space-y-5">
            @csrf

            <div>
                <label class="text-sm font-bold text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Password</label>
                <input type="password" name="password" required
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Ingat saya
            </label>

            <button type="submit"
                    class="w-full rounded-xl bg-polmind-blue px-6 py-4 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Login
            </button>
        </form>
    </div>
</div>
@endsection
