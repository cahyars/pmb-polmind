@extends('layouts.camaba')

@section('title', 'Dashboard Camaba')
@section('page_title', 'Selamat Datang, Ahmad Fauzi!')
@section('page_subtitle', 'Belum Lengkap - Selesaikan Biodata Anda')

@section('content')
<div class="space-y-8">

    <div class="rounded-2xl border border-red-200 bg-red-50 p-6">
        <h2 class="text-lg font-bold text-red-700">Perhatian: Data Penting Belum Terisi</h2>
        <p class="mt-2 text-sm leading-6 text-red-700">
            Sistem mendeteksi bahwa NIK dan NISN Anda belum terdaftar. Segera lengkapi data ini agar proses verifikasi berkas tidak terhambat.
        </p>
        <a href="#" class="mt-4 inline-block text-sm font-bold text-red-700 hover:underline">
            Lengkapi Sekarang →
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-6">
        @foreach(['Registrasi', 'Biodata', 'Berkas', 'Pembayaran', 'Seleksi', 'Daftar Ulang'] as $index => $step)
            <div class="text-center">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full border-2
                    {{ $index === 0 ? 'border-polmind-blue bg-polmind-blue text-white' : 'border-slate-300 bg-white text-slate-500' }}">
                    {{ $index + 1 }}
                </div>
                <p class="mt-2 text-sm font-semibold {{ $index <= 1 ? 'text-polmind-blue' : 'text-slate-500' }}">
                    {{ $step }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl bg-polmind-blue p-8 text-white shadow-xl shadow-blue-900/20 lg:col-span-2">
            <h2 class="text-3xl font-black">Lengkapi Biodata</h2>
            <p class="mt-4 max-w-md leading-7 text-blue-100">
                Pastikan data diri, orang tua, dan asal sekolah sudah terisi dengan benar untuk syarat seleksi awal.
            </p>

            <a href="#" class="mt-8 inline-flex rounded-xl bg-polmind-yellow px-6 py-3 font-bold text-polmind-blue-dark hover:brightness-95">
                Isi Sekarang →
            </a>
        </div>

        <div class="grid gap-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-bold">Upload Berkas</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Ijazah, SKHUN, dan Kartu Keluarga.
                </p>
                <a href="#" class="mt-4 inline-block text-sm font-bold text-polmind-blue">Kelola File ›</a>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-bold">Lihat Tagihan</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Status biaya pendaftaran dan rincian pembayaran.
                </p>
                <a href="#" class="mt-4 inline-block text-sm font-bold text-polmind-blue">Lihat Invoice ›</a>
            </div>
        </div>
    </div>

</div>
@endsection