@extends('layouts.camaba')

@section('title', 'Status Seleksi')
@section('page_title', 'Status Seleksi')
@section('page_subtitle', 'Pantau status seleksi dan pengumuman hasil PMB Anda.')

@section('content')
<div class="space-y-8">

    {{-- Main Status Card --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                    Status Seleksi Saat Ini
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Dalam Proses Seleksi
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Data pendaftaran, berkas, dan pembayaran Anda sedang dalam proses pengecekan oleh tim PMB
                    Politeknik Mitra Industri. Silakan pantau halaman ini secara berkala.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        No. Pendaftaran: PMB20240982
                    </span>
                    <span class="rounded-full bg-yellow-400 px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Menunggu Pengumuman
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-center">
                <div class="rounded-3xl bg-white/10 p-8 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 text-5xl">
                        ⏳
                    </div>
                    <p class="mt-5 text-sm font-bold text-blue-100">
                        Estimasi Pengumuman
                    </p>
                    <p class="mt-2 text-2xl font-black">
                        25 Juni 2026
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Options Preview --}}
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                ⏳
            </div>
            <h3 class="mt-5 text-lg font-black text-yellow-800">
                Dalam Proses
            </h3>
            <p class="mt-2 text-sm leading-6 text-yellow-800">
                Data sedang dicek oleh admin PMB dan tim seleksi.
            </p>
        </div>

        <div class="rounded-3xl border border-green-200 bg-green-50 p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-100 text-2xl">
                ✅
            </div>
            <h3 class="mt-5 text-lg font-black text-green-800">
                Diterima
            </h3>
            <p class="mt-2 text-sm leading-6 text-green-800">
                Jika diterima, Anda dapat melanjutkan proses daftar ulang.
            </p>
        </div>

        <div class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-2xl">
                ❌
            </div>
            <h3 class="mt-5 text-lg font-black text-red-800">
                Tidak Diterima
            </h3>
            <p class="mt-2 text-sm leading-6 text-red-800">
                Jika belum diterima, informasi lebih lanjut akan disampaikan oleh admin PMB.
            </p>
        </div>
    </div>

    {{-- Selection Timeline --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">
                Timeline Proses Seleksi
            </h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Berikut tahapan proses pendaftaran dan seleksi Anda.
            </p>
        </div>

        <div class="mt-6 space-y-6">
            @php
                $timelines = [
                    [
                        'title' => 'Registrasi Akun',
                        'date' => '20 Juni 2026',
                        'desc' => 'Akun pendaftaran berhasil dibuat.',
                        'status' => 'done',
                    ],
                    [
                        'title' => 'Pengisian Biodata',
                        'date' => '21 Juni 2026',
                        'desc' => 'Data diri, alamat, pendidikan, dan orang tua telah dilengkapi.',
                        'status' => 'done',
                    ],
                    [
                        'title' => 'Upload Berkas',
                        'date' => '22 Juni 2026',
                        'desc' => 'Dokumen persyaratan telah diunggah dan menunggu verifikasi.',
                        'status' => 'done',
                    ],
                    [
                        'title' => 'Verifikasi Pembayaran',
                        'date' => '23 Juni 2026',
                        'desc' => 'Bukti pembayaran sedang diverifikasi oleh admin/keuangan PMB.',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Pengumuman Hasil Seleksi',
                        'date' => '25 Juni 2026',
                        'desc' => 'Hasil seleksi akan diumumkan melalui dashboard camaba.',
                        'status' => 'waiting',
                    ],
                    [
                        'title' => 'Daftar Ulang',
                        'date' => 'Setelah Diterima',
                        'desc' => 'Camaba yang dinyatakan diterima dapat melanjutkan daftar ulang.',
                        'status' => 'waiting',
                    ],
                ];

                $timelineClasses = [
                    'done' => [
                        'circle' => 'bg-green-600 text-white',
                        'line' => 'bg-green-200',
                        'badge' => 'bg-green-100 text-green-700',
                        'label' => 'Selesai',
                    ],
                    'active' => [
                        'circle' => 'bg-polmind-blue text-white',
                        'line' => 'bg-blue-200',
                        'badge' => 'bg-blue-100 text-polmind-blue',
                        'label' => 'Berjalan',
                    ],
                    'waiting' => [
                        'circle' => 'bg-slate-200 text-slate-500',
                        'line' => 'bg-slate-200',
                        'badge' => 'bg-slate-100 text-slate-600',
                        'label' => 'Menunggu',
                    ],
                ];
            @endphp

            @foreach($timelines as $timeline)
                <div class="relative flex gap-4">
                    @if(!$loop->last)
                        <div class="absolute left-5 top-12 h-full w-0.5 {{ $timelineClasses[$timeline['status']]['line'] }}"></div>
                    @endif

                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-black {{ $timelineClasses[$timeline['status']]['circle'] }}">
                        @if($timeline['status'] === 'done')
                            ✓
                        @elseif($timeline['status'] === 'active')
                            !
                        @else
                            {{ $loop->iteration }}
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                            <div>
                                <h3 class="font-black text-slate-900">
                                    {{ $timeline['title'] }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $timeline['date'] }}
                                </p>
                            </div>

                            <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $timelineClasses[$timeline['status']]['badge'] }}">
                                {{ $timelineClasses[$timeline['status']]['label'] }}
                            </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ $timeline['desc'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Announcement Box --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-start">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Informasi Pengumuman Seleksi
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Pengumuman hasil seleksi akan ditampilkan langsung pada halaman ini. Pastikan Anda rutin mengecek dashboard
                    dan memperhatikan instruksi lanjutan dari admin PMB.
                </p>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                            Program Studi Pilihan
                        </p>
                        <p class="mt-2 text-sm font-bold text-slate-900">
                            D4 Teknologi Rekayasa Perangkat Lunak
                        </p>
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                            Gelombang
                        </p>
                        <p class="mt-2 text-sm font-bold text-slate-900">
                            Gelombang 2
                        </p>
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                            Jalur Pendaftaran
                        </p>
                        <p class="mt-2 text-sm font-bold text-slate-900">
                            Reguler
                        </p>
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                            Status Berkas
                        </p>
                        <p class="mt-2 text-sm font-bold text-slate-900">
                            Menunggu Verifikasi
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 lg:w-80">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>
                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Catatan Penting
                </h3>
                <p class="mt-2 text-sm leading-6 text-yellow-800">
                    Jika dinyatakan diterima, segera lakukan daftar ulang sesuai batas waktu yang ditentukan agar status penerimaan tidak dibatalkan.
                </p>
            </div>
        </div>
    </div>

    {{-- Accepted State Preview --}}
    <div class="rounded-3xl border border-green-200 bg-green-50 p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-green-700">
                    Tampilan Jika Diterima
                </p>
                <h3 class="mt-2 text-xl font-black text-green-800">
                    Selamat! Anda Dinyatakan Diterima
                </h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-green-800">
                    Jika status berubah menjadi diterima, tombol daftar ulang akan aktif dan Anda dapat melanjutkan proses administrasi berikutnya.
                </p>
            </div>

            <a href="{{ url('/camaba/pengumuman') }}"
               class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-green-900/10 transition hover:bg-green-700">
                Lihat Pengumuman →
            </a>
        </div>
    </div>

</div>
@endsection