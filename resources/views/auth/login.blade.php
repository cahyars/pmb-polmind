@extends('layouts.auth')

@section('title', 'Masuk Akun PMB Polmind')

@section('content')
<div class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-200 md:grid-cols-2">
    <div class="hidden bg-polmind-blue p-10 text-white md:block">
        <h2 class="text-2xl font-bold">Wujudkan Karir Impian di Bidang Industri.</h2>
        <p class="mt-4 leading-7 text-blue-100">
            Bergabunglah dengan Politeknik Mitra Industri untuk masa depan yang lebih cerah.
        </p>

        <div class="mt-14 space-y-4">
            <div class="rounded-2xl border border-white/20 bg-white/10 p-5">
                <h3 class="font-bold">Akreditasi Unggul</h3>
                <p class="mt-1 text-sm text-blue-100">Kurikulum berbasis industri terkini.</p>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 p-5">
                <h3 class="font-bold">Link and Match</h3>
                <p class="mt-1 text-sm text-blue-100">Kerja sama dengan perusahaan mitra.</p>
            </div>
        </div>
    </div>

    <div class="p-8 sm:p-12">
        <h1 class="text-2xl font-black text-polmind-blue">Selamat Datang</h1>
        <p class="mt-2 text-slate-600">Silakan masuk ke akun pendaftaran Anda.</p>

        <form action="#" method="POST" class="mt-8 space-y-5">
            @csrf

            <div>
                <label class="text-sm font-semibold text-slate-700">Email</label>
                <input type="email" name="email" placeholder="nama@email.com"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Password</label>
                <input type="password" name="password" placeholder="••••••••"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <button type="submit" class="w-full rounded-xl bg-polmind-blue px-5 py-3 font-bold text-white shadow-lg shadow-blue-900/20 hover:bg-polmind-blue-dark">
                Masuk Sekarang
            </button>
        </form>

        <div class="my-8 border-t border-slate-200"></div>

        <p class="text-center text-slate-600">
            Belum punya akun?
            <a href="{{ url('/register') }}" class="font-bold text-polmind-blue hover:underline">
                Daftar Akun Baru
            </a>
        </p>
    </div>
</div>
@endsection