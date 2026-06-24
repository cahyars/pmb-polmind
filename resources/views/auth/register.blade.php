@extends('layouts.auth')

@section('title', 'Daftar Akun PMB Polmind')

@section('content')
<div class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-200 md:grid-cols-2">
    <div class="hidden bg-polmind-blue p-10 text-white md:block">
        <h2 class="text-2xl font-bold">Mulai Pendaftaran Anda</h2>
        <p class="mt-4 leading-7 text-blue-100">
            Buat akun PMB untuk mengisi biodata, upload berkas, melihat tagihan, dan memantau status seleksi.
        </p>

        <div class="mt-14 space-y-4">
            <div class="rounded-2xl border border-white/20 bg-white/10 p-5">
                <h3 class="font-bold">Proses Mudah</h3>
                <p class="mt-1 text-sm text-blue-100">Pendaftaran dapat dilakukan secara online.</p>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 p-5">
                <h3 class="font-bold">Pantau Status</h3>
                <p class="mt-1 text-sm text-blue-100">Cek progres pendaftaran langsung dari dashboard.</p>
            </div>
        </div>
    </div>

    <div class="p-8 sm:p-12">
        <h1 class="text-2xl font-black text-polmind-blue">Daftar Akun Baru</h1>
        <p class="mt-2 text-slate-600">Silakan lengkapi data awal untuk membuat akun PMB.</p>

        <form action="#" method="POST" class="mt-8 space-y-5">
            @csrf

            <div>
                <label class="text-sm font-semibold text-slate-700">Nama Lengkap</label>
                <input type="text" name="name" placeholder="Nama sesuai ijazah"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Email</label>
                <input type="email" name="email" placeholder="nama@email.com"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Nomor WhatsApp</label>
                <input type="text" name="phone" placeholder="+62 812xxxxxxx"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Password</label>
                <input type="password" name="password" placeholder="Minimal 8 karakter"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <button type="submit" class="w-full rounded-xl bg-polmind-yellow px-5 py-3 font-bold text-polmind-blue-dark shadow-lg shadow-yellow-900/10 hover:brightness-95">
                Daftar Sekarang
            </button>
        </form>

        <div class="my-8 border-t border-slate-200"></div>

        <p class="text-center text-slate-600">
            Sudah punya akun?
            <a href="{{ url('/login') }}" class="font-bold text-polmind-blue hover:underline">
                Masuk
            </a>
        </p>
    </div>
</div>
@endsection