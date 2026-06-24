@extends('layouts.public')

@section('title', 'PMB Politeknik Mitra Industri')
@section('meta_description', 'Pendaftaran Mahasiswa Baru Politeknik Mitra Industri. Kuliah vokasi berbasis industri untuk masa depan karier yang lebih siap.')

@section('content')

{{-- HERO --}}
<section class="relative overflow-hidden bg-polmind-bg">
    <div class="absolute inset-0 opacity-50">
        <div class="absolute -right-20 top-20 h-72 w-72 rounded-full bg-blue-100 blur-3xl"></div>
        <div class="absolute -left-24 bottom-0 h-72 w-72 rounded-full bg-yellow-100 blur-3xl"></div>
    </div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-20">
        <div>
            <span class="inline-flex items-center rounded-full border border-blue-100 bg-white px-4 py-2 text-xs font-bold tracking-wide text-polmind-blue shadow-sm">
                PMB 2024/2025
            </span>

            <h1 class="mt-6 max-w-xl text-4xl font-black leading-tight tracking-tight text-polmind-blue sm:text-5xl lg:text-6xl">
                Pendaftaran Mahasiswa Baru Politeknik Mitra Industri
            </h1>

            <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                Membangun talenta industri masa depan dengan kurikulum berbasis kompetensi,
                pembelajaran praktis, dan pendekatan link-and-match industri.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/register') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Daftar Sekarang
                    <span class="ml-2">→</span>
                </a>

                <a href="https://wa.me/6281234567890"
                   target="_blank"
                   class="inline-flex items-center justify-center rounded-xl border border-polmind-border bg-white px-6 py-3 text-sm font-bold text-polmind-blue shadow-sm transition hover:bg-slate-50">
                    Konsultasi WA
                </a>
            </div>

            <div class="mt-8 grid max-w-lg grid-cols-3 gap-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-2xl font-black text-polmind-blue">95%</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Lulusan terserap industri</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-2xl font-black text-polmind-blue">3</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Program studi unggulan</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-2xl font-black text-polmind-blue">100+</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Mitra industri</p>
                </div>
            </div>
        </div>

        <div class="relative">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-polmind-blue p-6 shadow-2xl shadow-blue-900/20">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-800 via-polmind-blue to-blue-950"></div>

                <div class="relative flex aspect-[4/3] items-center justify-center rounded-2xl border border-white/10 bg-white/5 p-8 text-center">
                    <div>
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 text-4xl">
                            🏭
                        </div>
                        <h2 class="mt-6 text-2xl font-black text-white">
                            Vokasi Berbasis Industri
                        </h2>
                        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-blue-100">
                            Pembelajaran praktis, project nyata, dan orientasi kesiapan kerja sejak awal perkuliahan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="absolute -bottom-6 left-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-yellow-100 text-xl">
                        🎓
                    </div>
                    <div>
                        <p class="text-sm font-black text-polmind-blue">Kampus Vokasi</p>
                        <p class="text-xs text-slate-500">Siap kerja, siap berkarya</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ALUR PENDAFTARAN --}}
<section id="alur" class="bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-black tracking-tight text-polmind-blue sm:text-4xl">
                Alur Pendaftaran
            </h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Proses pendaftaran dibuat sederhana, transparan, dan mudah diikuti oleh calon mahasiswa.
            </p>
        </div>

        <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
            @foreach([
                ['title' => 'Daftar', 'desc' => 'Registrasi akun pada portal PMB Polmind.', 'icon' => '👤'],
                ['title' => 'Bayar', 'desc' => 'Lakukan pembayaran biaya pendaftaran.', 'icon' => '💳'],
                ['title' => 'Biodata', 'desc' => 'Lengkapi data diri dan riwayat pendidikan.', 'icon' => '📝'],
                ['title' => 'Berkas', 'desc' => 'Unggah dokumen pendukung wajib.', 'icon' => '📁'],
                ['title' => 'Seleksi', 'desc' => 'Ikuti proses seleksi sesuai ketentuan.', 'icon' => '✅'],
                ['title' => 'Daftar Ulang', 'desc' => 'Finalisasi administrasi mahasiswa baru.', 'icon' => '🎉'],
            ] as $index => $step)
                <div class="group rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-xl transition group-hover:bg-polmind-blue group-hover:text-white">
                        {{ $step['icon'] }}
                    </div>
                    <p class="mt-4 text-xs font-black text-polmind-blue">
                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}. {{ $step['title'] }}
                    </p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        {{ $step['desc'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PROGRAM STUDI --}}
<section id="program-studi" class="bg-polmind-bg py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-3xl font-black tracking-tight text-polmind-blue sm:text-4xl">
                    Program Studi Unggulan
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Kurikulum dirancang khusus untuk memenuhi kebutuhan industri masa depan.
                </p>
            </div>

            <a href="#" class="text-sm font-bold text-polmind-blue hover:underline">
                Detail Kurikulum ↗
            </a>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach([
                [
                    'name' => 'D4 Teknologi Rekayasa Perangkat Lunak',
                    'desc' => 'Mencetak software engineer yang siap membangun aplikasi dan solusi digital industri.',
                    'tags' => ['Software Dev', 'Cloud Computing'],
                    'icon' => '💻'
                ],
                [
                    'name' => 'D4 Bisnis Digital',
                    'desc' => 'Mempelajari strategi bisnis digital, data, dan pemasaran berbasis teknologi.',
                    'tags' => ['Data Analytics', 'Digital Marketing'],
                    'icon' => '📊'
                ],
                [
                    'name' => 'D4 Teknologi Rekayasa Manufaktur',
                    'desc' => 'Fokus pada otomasi, mesin, kontrol, dan teknologi manufaktur modern.',
                    'tags' => ['Robotics', 'Industrial IoT'],
                    'icon' => '🤖'
                ],
            ] as $prodi)
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="relative flex h-48 items-center justify-center bg-gradient-to-br from-blue-900 to-blue-950">
                        <div class="absolute right-4 top-4 rounded-full bg-white px-3 py-1 text-[10px] font-black text-polmind-blue">
                            JENJANG D4
                        </div>
                        <div class="text-6xl">{{ $prodi['icon'] }}</div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-lg font-black leading-6 text-polmind-blue">
                            {{ $prodi['name'] }}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ $prodi['desc'] }}
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach($prodi['tags'] as $tag)
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-polmind-blue">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- JADWAL & BIAYA --}}
<section id="biaya" class="bg-polmind-blue py-20">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="rounded-3xl bg-white/10 p-6 shadow-xl ring-1 ring-white/10 sm:p-8">
            <h2 class="text-2xl font-black text-white">Jadwal Gelombang</h2>

            <div class="mt-6 space-y-4">
                @foreach([
                    ['name' => 'Gelombang 1', 'desc' => 'Beasiswa Prestasi & Unggulan', 'date' => '1 Jan - 31 Mar', 'status' => 'Sedang Berjalan'],
                    ['name' => 'Gelombang 2', 'desc' => 'Reguler & Jalur Mandiri', 'date' => '1 Apr - 30 Jun', 'status' => 'Akan Datang'],
                    ['name' => 'Gelombang 3', 'desc' => 'Reguler Akhir', 'date' => '1 Jul - 31 Agt', 'status' => 'Akan Datang'],
                ] as $wave)
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-5 text-white">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <h3 class="font-black">{{ $wave['name'] }}</h3>
                                <p class="mt-1 text-sm text-blue-100">{{ $wave['desc'] }}</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-sm font-bold">{{ $wave['date'] }}</p>
                                <span class="mt-2 inline-flex rounded-full bg-white/10 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-blue-100">
                                    {{ $wave['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-3xl bg-polmind-yellow p-6 shadow-xl sm:p-8">
            <h2 class="text-2xl font-black text-polmind-blue-dark">Rincian Biaya</h2>
            <p class="mt-3 text-sm leading-6 text-polmind-blue-dark/80">
                Investasi pendidikan terjangkau dengan jaminan kualitas industri.
            </p>

            <div class="mt-8 space-y-5">
                <div class="flex justify-between border-b border-yellow-500/40 pb-4">
                    <span class="font-semibold text-polmind-blue-dark">Pendaftaran</span>
                    <span class="font-black text-polmind-blue-dark">Rp 350.000</span>
                </div>
                <div class="flex justify-between border-b border-yellow-500/40 pb-4">
                    <span class="font-semibold text-polmind-blue-dark">UKT per Semester</span>
                    <span class="font-black text-polmind-blue-dark">Rp 7.500.000*</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-polmind-blue-dark">SPI Sekali Bayar</span>
                    <span class="font-black text-polmind-blue-dark">Rp 15.000.000</span>
                </div>
            </div>

            <p class="mt-8 rounded-2xl bg-white/40 p-4 text-xs leading-5 text-polmind-blue-dark">
                * Tersedia beasiswa, cicilan, dan program potongan biaya berdasarkan ketentuan yang berlaku.
            </p>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="bg-polmind-bg py-20">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-black text-polmind-blue sm:text-4xl">
                Pertanyaan Umum
            </h2>
            <p class="mt-3 text-sm text-slate-600">
                Informasi singkat seputar pendaftaran mahasiswa baru.
            </p>
        </div>

        <div class="mt-10 space-y-4" x-data="{ active: 1 }">
            @foreach([
                ['q' => 'Apakah lulusan SMK bisa mendaftar?', 'a' => 'Bisa. Lulusan SMA, SMK, MA, dan Paket C dapat mendaftar sesuai ketentuan PMB yang berlaku.'],
                ['q' => 'Bagaimana porsi praktik dan teori?', 'a' => 'Pembelajaran dirancang berbasis vokasi dengan porsi praktik yang kuat dan didukung project berbasis kebutuhan industri.'],
                ['q' => 'Apakah ada jaminan kerja?', 'a' => 'Polmind memiliki pendekatan link-and-match industri untuk meningkatkan kesiapan kerja lulusan, namun proses penempatan tetap mengikuti ketentuan dan kebutuhan mitra industri.'],
            ] as $index => $faq)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <button type="button"
                            class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                            @click="active = active === {{ $index + 1 }} ? null : {{ $index + 1 }}">
                        <span class="font-bold text-polmind-blue">{{ $faq['q'] }}</span>
                        <span class="text-xl text-slate-400">⌄</span>
                    </button>

                    <div x-show="active === {{ $index + 1 }}" x-collapse class="px-6 pb-5 text-sm leading-6 text-slate-600">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-br from-polmind-blue to-polmind-blue-dark p-8 text-center shadow-2xl shadow-blue-900/20 sm:p-12">
            <h2 class="text-3xl font-black text-white sm:text-4xl">
                Siap Bergabung dengan Polmind?
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                Daftarkan diri Anda sekarang dan mulai langkah menuju karier masa depan di dunia industri.
            </p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ url('/register') }}"
                   class="rounded-xl bg-polmind-yellow px-7 py-3 text-sm font-black text-polmind-blue-dark transition hover:brightness-95">
                    Daftar Sekarang
                </a>
                <a href="https://wa.me/6281234567890"
                   target="_blank"
                   class="rounded-xl border border-white/20 bg-white/10 px-7 py-3 text-sm font-black text-white transition hover:bg-white/20">
                    Konsultasi Admin PMB
                </a>
            </div>
        </div>
    </div>
</section>

@endsection