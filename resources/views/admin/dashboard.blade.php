@extends('layouts.admin')

@section('title', 'Dashboard Admin PMB')
@section('page_title', 'Dashboard Admin PMB')
@section('page_subtitle', 'Ringkasan aktivitas Pendaftaran Mahasiswa Baru')

@section('content')
<div class="space-y-8">

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Pendaftar', 'value' => '128'],
            ['label' => 'Pendaftar Hari Ini', 'value' => '12'],
            ['label' => 'Berkas Menunggu', 'value' => '34'],
            ['label' => 'Pembayaran Menunggu', 'value' => '18'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-polmind-blue">Pendaftar Terbaru</h2>
                <a href="#" class="text-sm font-bold text-polmind-blue hover:underline">Lihat Semua</a>
            </div>

            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">No. Pendaftaran</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Prodi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach(['Ahmad Fauzi', 'Siti Aminah', 'Budi Santoso'] as $name)
                            <tr>
                                <td class="px-4 py-4 font-semibold text-polmind-blue">PMB2024{{ $loop->iteration }}</td>
                                <td class="px-4 py-4">{{ $name }}</td>
                                <td class="px-4 py-4">TRPL</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-700">
                                        Biodata
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <a href="#" class="font-bold text-polmind-blue hover:underline">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-polmind-blue">Quick Action</h2>
            <div class="mt-5 space-y-3">
                <a href="#" class="block rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold hover:bg-slate-50">
                    Verifikasi Berkas
                </a>
                <a href="#" class="block rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold hover:bg-slate-50">
                    Verifikasi Pembayaran
                </a>
                <a href="#" class="block rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold hover:bg-slate-50">
                    Export Laporan
                </a>
            </div>
        </div>
    </div>

</div>
@endsection