@extends('layouts.camaba')

@section('title', 'Dashboard Camaba')
@section('page_title', 'Dashboard Camaba')
@section('page_subtitle', 'Pantau progres pendaftaran mahasiswa baru Anda.')

@section('content')
<div class="space-y-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                    Portal Calon Mahasiswa Baru
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Halo, Ahmad Fauzi 👋
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Selamat datang di dashboard PMB Politeknik Mitra Industri.
                    Silakan lengkapi seluruh tahapan pendaftaran agar proses seleksi dapat dilanjutkan.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        No. Pendaftaran: PMB20240982
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Status: Dalam Proses
                    </span>
                </div>
            </div>

            <div class="rounded-3xl bg-white/10 p-6">
                <p class="text-sm font-bold text-blue-100">Progress Pendaftaran</p>
                <p class="mt-3 text-5xl font-black">60%</p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full w-[60%] rounded-full bg-polmind-yellow"></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Lengkapi biodata, berkas, dan pembayaran agar bisa lanjut ke tahap seleksi.
                </p>
            </div>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Biodata',
                'value' => 'Lengkap',
                'desc' => 'Data pribadi sudah diisi',
                'url' => '/camaba/biodata',
                'class' => 'bg-green-100 text-green-700',
            ],
            [
                'label' => 'Berkas',
                'value' => '3/5',
                'desc' => 'Sebagian berkas sudah diupload',
                'url' => '/camaba/berkas',
                'class' => 'bg-yellow-100 text-yellow-700',
            ],
            [
                'label' => 'Pembayaran',
                'value' => 'Belum',
                'desc' => 'Menunggu pembayaran pendaftaran',
                'url' => '/camaba/pembayaran',
                'class' => 'bg-red-100 text-red-700',
            ],
            [
                'label' => 'Seleksi',
                'value' => 'Proses',
                'desc' => 'Menunggu verifikasi admin PMB',
                'url' => '/camaba/status-seleksi',
                'class' => 'bg-blue-100 text-polmind-blue',
            ],
        ] as $card)
            <a href="{{ url($card['url']) }}"
               class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-3 text-3xl font-black text-polmind-blue">
                            {{ $card['value'] }}
                        </p>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            {{ $card['desc'] }}
                        </p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        Detail
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Main Grid --}}
    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Left Column --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Next Action --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-yellow-700">
                            Tindakan Berikutnya
                        </p>

                        <h2 class="mt-2 text-xl font-black text-yellow-900">
                            Lengkapi Upload Berkas Pendaftaran
                        </h2>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-yellow-800">
                            Masih ada beberapa dokumen yang belum lengkap. Silakan lengkapi seluruh berkas wajib
                            agar admin PMB dapat melakukan verifikasi.
                        </p>
                    </div>

                    <a href="{{ url('/camaba/berkas') }}"
                       class="inline-flex justify-center rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Lengkapi Berkas
                    </a>
                </div>
            </div>

            {{-- Registration Timeline --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Timeline Pendaftaran
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Tahapan proses PMB yang perlu Anda selesaikan.
                    </p>
                </div>

                @php
                    $timelines = [
                        [
                            'title' => 'Registrasi Akun',
                            'desc' => 'Akun PMB berhasil dibuat.',
                            'date' => '20 Juni 2026',
                            'status' => 'done',
                        ],
                        [
                            'title' => 'Pengisian Biodata',
                            'desc' => 'Data pribadi, alamat, pendidikan, dan orang tua telah dilengkapi.',
                            'date' => '21 Juni 2026',
                            'status' => 'done',
                        ],
                        [
                            'title' => 'Upload Berkas',
                            'desc' => 'Beberapa dokumen masih perlu dilengkapi atau diperbaiki.',
                            'date' => '22 Juni 2026',
                            'status' => 'active',
                        ],
                        [
                            'title' => 'Pembayaran Pendaftaran',
                            'desc' => 'Lakukan pembayaran dan upload bukti transfer.',
                            'date' => 'Menunggu',
                            'status' => 'waiting',
                        ],
                        [
                            'title' => 'Seleksi',
                            'desc' => 'Admin PMB akan memproses data setelah berkas dan pembayaran valid.',
                            'date' => 'Menunggu',
                            'status' => 'waiting',
                        ],
                        [
                            'title' => 'Pengumuman & Daftar Ulang',
                            'desc' => 'Jika diterima, Anda dapat melanjutkan daftar ulang.',
                            'date' => 'Menunggu',
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
                            'label' => 'Sedang Berjalan',
                        ],
                        'waiting' => [
                            'circle' => 'bg-slate-200 text-slate-500',
                            'line' => 'bg-slate-200',
                            'badge' => 'bg-slate-100 text-slate-600',
                            'label' => 'Menunggu',
                        ],
                    ];
                @endphp

                <div class="mt-6 space-y-6">
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

            {{-- Program Info --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Informasi Pendaftaran
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Ringkasan data pilihan program studi dan jalur pendaftaran.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach([
                        ['label' => 'Nama Camaba', 'value' => 'Ahmad Fauzi'],
                        ['label' => 'Nomor Pendaftaran', 'value' => 'PMB20240982'],
                        ['label' => 'Program Studi Pilihan', 'value' => 'D4 Teknologi Rekayasa Perangkat Lunak'],
                        ['label' => 'Kelas', 'value' => 'Reguler A'],
                        ['label' => 'Gelombang', 'value' => 'Gelombang 2'],
                        ['label' => 'Status Akhir', 'value' => 'Dalam Proses Pendaftaran'],
                    ] as $item)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                {{ $item['label'] }}
                            </p>

                            <p class="mt-2 text-sm font-bold leading-6 text-slate-900">
                                {{ $item['value'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <aside class="space-y-6">

            {{-- Quick Menu --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Menu Cepat
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Akses cepat ke tahapan pendaftaran.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach([
                        ['label' => 'Isi / Cek Biodata', 'url' => '/camaba/biodata', 'icon' => '👤'],
                        ['label' => 'Upload Berkas', 'url' => '/camaba/berkas', 'icon' => '📄'],
                        ['label' => 'Tagihan Pembayaran', 'url' => '/camaba/pembayaran', 'icon' => '💳'],
                        ['label' => 'Cek Status Seleksi', 'url' => '/camaba/status-seleksi', 'icon' => '✅'],
                        ['label' => 'Pengumuman', 'url' => '/camaba/pengumuman', 'icon' => '🎓'],
                    ] as $menu)
                        <a href="{{ url($menu['url']) }}"
                           class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-polmind-blue hover:bg-blue-50">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg shadow-sm">
                                {{ $menu['icon'] }}
                            </div>

                            <span class="text-sm font-bold text-slate-700">
                                {{ $menu['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Payment --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    💳
                </div>

                <h3 class="mt-5 text-lg font-black text-polmind-blue">
                    Tagihan Pendaftaran
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Tagihan biaya pendaftaran masih menunggu pembayaran.
                </p>

                <div class="mt-5 rounded-2xl bg-polmind-blue p-5 text-white">
                    <p class="text-sm font-bold text-blue-100">
                        Total Tagihan
                    </p>
                    <p class="mt-2 text-3xl font-black">
                        Rp350.000
                    </p>
                </div>

                <a href="{{ url('/camaba/pembayaran') }}"
                   class="mt-5 inline-flex w-full justify-center rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark transition hover:brightness-95">
                    Lihat Pembayaran
                </a>
            </div>

            {{-- Notes --}}
            <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-polmind-blue">
                    Catatan Penting
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                    <li>• Pastikan semua data biodata sudah benar.</li>
                    <li>• Upload dokumen dengan kualitas jelas.</li>
                    <li>• Pembayaran akan diverifikasi oleh admin PMB.</li>
                    <li>• Cek dashboard secara berkala untuk melihat status terbaru.</li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="rounded-3xl border border-green-200 bg-green-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-100 text-2xl">
                    💬
                </div>

                <h3 class="mt-5 text-lg font-black text-green-800">
                    Butuh Bantuan?
                </h3>

                <p class="mt-2 text-sm leading-6 text-green-800">
                    Hubungi admin PMB jika mengalami kendala saat mengisi data atau upload berkas.
                </p>

                <a href="https://wa.me/6281234567890?text={{ urlencode('Halo Admin PMB Polmind, saya ingin bertanya terkait proses pendaftaran.') }}"
                   target="_blank"
                   class="mt-5 inline-flex w-full justify-center rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-700">
                    Hubungi Admin PMB
                </a>
            </div>

        </aside>
    </div>

</div>
@endsection