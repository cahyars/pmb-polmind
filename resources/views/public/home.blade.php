@extends('layouts.public')

@section('title', 'PMB Politeknik Mitra Industri')

@section('content')
<section class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
    <div>
        <span class="inline-flex rounded-full bg-blue-50 px-4 py-2 text-xs font-bold text-polmind-blue">
            PMB 2024/2025
        </span>

        <h1 class="mt-6 max-w-xl text-4xl font-black leading-tight text-polmind-blue sm:text-5xl">
            Pendaftaran Mahasiswa Baru Politeknik Mitra Industri
        </h1>

        <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
            Membangun talenta industri masa depan dengan kurikulum berbasis kompetensi dan link-and-match industri global.
        </p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ url('/register') }}" class="rounded-xl bg-polmind-blue px-6 py-3 text-center text-sm font-bold text-white shadow-lg shadow-blue-900/20 hover:bg-polmind-blue-dark">
                Daftar Sekarang
            </a>
            <a href="#" class="rounded-xl border border-polmind-border bg-white px-6 py-3 text-center text-sm font-bold text-polmind-blue hover:bg-slate-50">
                Konsultasi WA
            </a>
        </div>
    </div>

    <div class="rounded-3xl bg-polmind-blue p-8 shadow-2xl shadow-blue-900/20">
        <div class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-blue-950 text-center text-white">
            <div>
                <p class="text-2xl font-bold">Ilustrasi Kampus / Industri</p>
                <p class="mt-2 text-sm text-blue-100">Placeholder sesuai mockup Stitch</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-black text-polmind-blue">Alur Pendaftaran</h2>
        <p class="mt-3 text-sm text-slate-600">Proses pendaftaran transparan dan mudah diikuti.</p>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
            @foreach(['Daftar', 'Bayar', 'Biodata', 'Berkas', 'Seleksi', 'Daftar Ulang'] as $index => $step)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 font-bold text-polmind-blue">
                        {{ $index + 1 }}
                    </div>
                    <h3 class="mt-4 text-sm font-bold text-polmind-blue">{{ $step }}</h3>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection