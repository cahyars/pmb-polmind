@extends('layouts.public')

@section('title', 'PMB Politeknik Mitra Industri')
@section('meta_description', 'Pendaftaran Mahasiswa Baru Politeknik Mitra Industri. Kuliah vokasi berbasis industri untuk masa depan karier yang lebih siap.')

@section('content')

@php
    $academicYear = $pmbYear ? 'TA ' . $pmbYear->year . '/' . ($pmbYear->year + 1) : null;
    $admissionName = 'Pendaftaran Mahasiswa Baru' . ($pmbYear ? ' ' . $pmbYear->year : '');

    $openingText = match (true) {
        (bool) $currentWave => "{$admissionName} {$currentWave->name} telah dibuka. Daftarkan diri Anda sekarang!",
        (bool) $nextWave?->start_date => "{$admissionName} {$nextWave->name} dibuka mulai " . $nextWave->start_date->translatedFormat('j F Y') . '. Siapkan diri Anda dan daftar sejak hari pertama!',
        default => "{$admissionName} Politeknik Mitra Industri. Pantau jadwal gelombang pendaftaran di bawah.",
    };

    $heroSlides = [
        [
            'title' => 'Raih Impian Anda Bersama Polmind',
            'text' => $openingText,
            'image' => 'object-[50%_45%] scale-100',
        ],
        [
            'title' => 'Kuliah Vokasi, Langsung Terhubung Industri',
            'text' => 'Belajar dengan konsep Project-based Learning melalui Teaching Factory dan langsung mengerjakan proyek riil dari industri sejak awal perkuliahan.',
            'image' => 'object-[30%_40%] scale-110',
        ],
        [
            'title' => 'Pilih Kelas Sesuai Kebutuhan Anda',
            'text' => 'Tersedia kelas reguler untuk lulusan baru dan kelas khusus karyawan dengan jadwal yang menyesuaikan jam kerja.',
            'image' => 'object-[70%_40%] scale-105',
        ],
    ];

    $heroWave = $currentWave ?? $nextWave;
@endphp

{{-- HERO --}}
<section class="relative isolate overflow-hidden bg-polmind-blue-dark"
         x-data="heroSlider({{ count($heroSlides) }})"
         @mouseenter="stop()"
         @mouseleave="start()"
         @focusin="stop()"
         aria-roledescription="carousel"
         aria-label="Informasi PMB Polmind">
    <picture>
        <source srcset="{{ asset('images/kampus-polmind.webp') }}" type="image/webp">
        <img src="{{ asset('images/kampus-polmind.jpg') }}"
             alt="Gedung kampus Politeknik Mitra Industri"
             fetchpriority="high"
             class="absolute inset-0 -z-20 h-full w-full object-cover transition-all duration-[1600ms] ease-out"
             :class="@js(array_column($heroSlides, 'image'))[active]">
    </picture>

    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-polmind-blue-dark/95 via-polmind-blue-dark/65 to-polmind-blue-dark/10"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-polmind-blue-dark/70 to-transparent"></div>

    <div class="mx-auto flex min-h-[560px] max-w-7xl flex-col justify-center px-4 py-20 sm:px-6 lg:min-h-[640px] lg:px-8">
        <div class="max-w-2xl">
            @if($pmbYear)
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-bold tracking-wide text-white ring-1 ring-white/25 backdrop-blur">
                    <span class="h-2 w-2 rounded-full {{ $currentWave ? 'bg-green-400' : 'bg-polmind-yellow' }}"></span>
                    {{ $pmbYear->name }}{{ $academicYear ? ' · ' . $academicYear : '' }}
                </span>
            @endif

            {{-- Teks slide ditumpuk di satu sel grid agar tinggi tidak melompat saat berganti. --}}
            <div class="mt-6 grid">
                @foreach($heroSlides as $index => $slide)
                    <div class="[grid-area:1/1] transition duration-700"
                         :class="active === {{ $index }} ? 'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 translate-y-3'"
                         :aria-hidden="active !== {{ $index }}"
                         role="group"
                         aria-roledescription="slide"
                         aria-label="{{ $index + 1 }} dari {{ count($heroSlides) }}"
                         @if($index > 0) x-cloak @endif>
                        @if($index === 0)
                            <h1 class="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">{{ $slide['title'] }}</h1>
                        @else
                            <p class="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">{{ $slide['title'] }}</p>
                        @endif
                        <p class="mt-5 max-w-xl text-base leading-7 text-blue-50 sm:text-lg">{{ $slide['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/register') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-polmind-yellow px-6 py-3.5 text-sm font-black text-polmind-blue-dark shadow-lg shadow-black/20 transition hover:brightness-95">
                    Daftar Sekarang
                    <span class="ml-2" aria-hidden="true">→</span>
                </a>
                <a href="{{ \App\Services\AdminWhatsApp::url() }}"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex items-center justify-center rounded-xl bg-white/10 px-6 py-3.5 text-sm font-bold text-white ring-1 ring-white/30 backdrop-blur transition hover:bg-white/20">
                    Konsultasi WA
                </a>
            </div>
        </div>

        <div class="mt-12 flex items-center gap-3">
            <button type="button"
                    @click="prev()"
                    aria-label="Slide sebelumnya"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-polmind-blue shadow-lg transition hover:scale-105">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.83 10l3.94 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
            </button>
            <button type="button"
                    @click="next()"
                    aria-label="Slide berikutnya"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-polmind-blue text-white shadow-lg ring-1 ring-white/30 transition hover:scale-105">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
            </button>

            <div class="ml-2 flex items-center gap-2">
                @foreach($heroSlides as $index => $slide)
                    <button type="button"
                            @click="go({{ $index }})"
                            :aria-current="active === {{ $index }}"
                            aria-label="Tampilkan slide {{ $index + 1 }}"
                            class="h-2 rounded-full transition-all"
                            :class="active === {{ $index }} ? 'w-8 bg-polmind-yellow' : 'w-2 bg-white/50 hover:bg-white/80'"></button>
                @endforeach
            </div>
        </div>
    </div>

    @if($heroWave)
        <div class="absolute bottom-28 right-8 hidden w-72 rounded-2xl bg-white/15 p-5 text-white ring-1 ring-white/25 backdrop-blur-md xl:block">
            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-polmind-yellow">
                {{ $currentWave ? 'Gelombang Dibuka' : 'Gelombang Berikutnya' }}
            </p>
            <p class="mt-2 text-xl font-black">{{ $heroWave->name }}</p>
            @if($heroWave->start_date && $heroWave->end_date)
                <p class="mt-1 text-sm text-blue-50">
                    {{ $heroWave->start_date->translatedFormat('j M Y') }} – {{ $heroWave->end_date->translatedFormat('j M Y') }}
                </p>
            @endif
            @if($registrationFee)
                <p class="mt-4 border-t border-white/20 pt-4 text-xs text-blue-50">
                    Biaya formulir <span class="block text-lg font-black text-white">Rp{{ number_format($registrationFee, 0, ',', '.') }}</span>
                </p>
            @endif
        </div>
    @endif
</section>

{{-- JALUR MASUK --}}
<section id="jalur-masuk" class="scroll-mt-20 bg-white py-20">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div>
            <h2 class="text-3xl font-black leading-tight tracking-tight text-polmind-blue sm:text-4xl">
                Jalur Masuk Pendaftaran Kuliah
            </h2>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Berikut jalur masuk pendaftaran mahasiswa baru Politeknik Mitra Industri. Jalur dipilih saat melengkapi biodata.
            </p>

            @if($brochures->isNotEmpty())
                {{-- Satu brosur langsung diunduh; lebih dari satu diarahkan ke daftar brosur. --}}
                <a href="{{ $brochures->count() === 1 ? route('brochures.download', $brochures->first()) : '#brosur' }}"
                   class="mt-8 inline-flex items-center gap-2 rounded-xl border border-polmind-border bg-white px-5 py-3 text-sm font-bold text-polmind-blue shadow-sm transition hover:bg-blue-50">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                    Download Brosur
                </a>
            @else
                <a href="https://polmind.ac.id/pmb"
                   target="_blank"
                   rel="noopener"
                   class="mt-8 inline-flex items-center gap-2 rounded-xl border border-polmind-border bg-white px-5 py-3 text-sm font-bold text-polmind-blue shadow-sm transition hover:bg-blue-50">
                    Info Lengkap PMB
                    <x-icon name="arrow-up-right" class="h-4 w-4" stroke-width="2" />
                </a>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
            @foreach(\App\Models\Applicant::REGISTRATION_PATHS as $key => $path)
                {{-- Membuka form registrasi dengan jalur ini sudah terpilih. --}}
                <a href="{{ route('register', ['jalur' => $key]) }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-polmind-blue hover:shadow-lg">
                    <p class="text-sm font-black text-polmind-blue">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <h3 class="text-lg font-black text-slate-900 transition group-hover:text-polmind-blue">Jalur {{ $path['label'] }}</h3>
                        <span class="text-lg font-black text-polmind-yellow opacity-0 transition group-hover:opacity-100" aria-hidden="true">»</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $path['description'] }}</p>
                    <p class="mt-4 text-xs font-black text-polmind-blue opacity-0 transition group-hover:opacity-100">Daftar lewat jalur ini →</p>
                </a>
            @endforeach

            <a href="{{ \App\Services\AdminWhatsApp::url() }}"
               target="_blank"
               rel="noopener"
               class="group flex flex-col justify-between rounded-2xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20 transition hover:-translate-y-0.5 hover:bg-polmind-blue-dark">
                <p class="text-sm font-black text-polmind-yellow">04</p>
                <div>
                    <h3 class="mt-2 text-lg font-black">Belum yakin jalur yang cocok?</h3>
                    <p class="mt-2 text-sm leading-6 text-blue-100">Konsultasi gratis dengan admin PMB lewat WhatsApp.</p>
                </div>
                <span class="mt-4 text-sm font-black text-polmind-yellow">Chat Admin PMB →</span>
            </a>
        </div>
    </div>
</section>

{{-- ALUR PENDAFTARAN --}}
<section id="alur" class="scroll-mt-20 bg-polmind-bg py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-black tracking-tight text-polmind-blue sm:text-4xl">
                Alur Pendaftaran
            </h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Proses pendaftaran dibuat sederhana, transparan, dan mudah diikuti oleh calon mahasiswa.
            </p>
        </div>

        @php
            // Warna tiap tahap; garis penghubung bergradasi ke warna tahap berikutnya.
            $steps = [
                [
                    'title' => 'Pendaftaran',
                    'desc' => 'Buat akun dengan NIK, sekolah asal, jalur, serta pilihan prodi dan kelas.',
                    'circle' => 'bg-blue-600 ring-blue-100 shadow-blue-600/30',
                    'title_class' => 'text-blue-700',
                    'line' => 'from-blue-600 to-sky-500',
                ],
                [
                    'title' => 'Bayar',
                    'desc' => 'Bayar biaya formulir' . ($registrationFee ? ' Rp' . number_format($registrationFee, 0, ',', '.') : '') . ' lalu unggah bukti transfer.',
                    'circle' => 'bg-sky-500 ring-sky-100 shadow-sky-500/30',
                    'title_class' => 'text-sky-700',
                    'line' => 'from-sky-500 to-emerald-500',
                ],
                [
                    'title' => 'Biodata',
                    'desc' => 'Lengkapi data diri, alamat, sekolah asal, dan data orang tua.',
                    'circle' => 'bg-emerald-500 ring-emerald-100 shadow-emerald-500/30',
                    'title_class' => 'text-emerald-700',
                    'line' => 'from-emerald-500 to-amber-500',
                ],
                [
                    'title' => 'Berkas',
                    'desc' => 'Unggah dokumen persyaratan dan tunggu verifikasi admin PMB.',
                    'circle' => 'bg-amber-500 ring-amber-100 shadow-amber-500/30',
                    'title_class' => 'text-amber-700',
                    'line' => 'from-amber-500 to-violet-500',
                ],
                [
                    'title' => 'Seleksi',
                    'desc' => 'Ikuti tes seleksi sesuai jadwal gelombang, lalu pantau pengumuman.',
                    'circle' => 'bg-violet-500 ring-violet-100 shadow-violet-500/30',
                    'title_class' => 'text-violet-700',
                    'line' => 'from-violet-500 to-rose-500',
                ],
                [
                    'title' => 'Daftar Ulang',
                    'desc' => 'Selesaikan daftar ulang untuk resmi menjadi mahasiswa Polmind.',
                    'circle' => 'bg-rose-500 ring-rose-100 shadow-rose-500/30',
                    'title_class' => 'text-rose-700',
                    'line' => null,
                ],
            ];
        @endphp

        {{-- Stepper: vertikal di HP, horizontal di desktop (struktur sama). --}}
        <ol class="mx-auto mt-14 max-w-xl lg:grid lg:max-w-none lg:grid-cols-6">
            @foreach($steps as $step)
                <li class="group relative flex gap-5 pb-10 last:pb-0 lg:flex-col lg:items-center lg:gap-0 lg:px-3 lg:pb-0 lg:text-center">
                    @if($step['line'])
                        {{-- Garis ke tahap berikutnya: turun di HP, ke kanan di desktop. --}}
                        <span aria-hidden="true"
                              class="absolute left-7 top-14 bottom-0 w-1 -translate-x-1/2 rounded-full bg-gradient-to-b {{ $step['line'] }}
                                     lg:left-[calc(50%+2.25rem)] lg:right-[calc(-50%+2.25rem)] lg:top-7 lg:bottom-auto lg:h-1 lg:w-auto lg:translate-x-0 lg:-translate-y-1/2 lg:bg-gradient-to-r"></span>
                    @endif

                    <span class="relative z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-xl font-black text-white shadow-lg ring-8 transition duration-300 group-hover:scale-110 {{ $step['circle'] }}">
                        {{ $loop->iteration }}
                    </span>

                    <div class="min-w-0 pt-2.5 lg:mt-6 lg:pt-0">
                        <h3 class="text-lg font-black {{ $step['title_class'] }}">{{ $step['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ $step['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- PROGRAM STUDI --}}
<section id="program-studi" class="scroll-mt-20 bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-3xl font-black tracking-tight text-polmind-blue sm:text-4xl">
                    Program Studi Unggulan
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    @php
                        $degreeNames = ['D3' => 'Diploma Tiga (D3)', 'D4' => 'Sarjana Terapan (D4)', 'S1' => 'Sarjana (S1)'];
                        $degrees = $studyPrograms->pluck('degree')->unique()->map(fn ($degree) => $degreeNames[$degree] ?? $degree);
                    @endphp
                    {{ $degrees->isNotEmpty() ? 'Program ' . $degrees->implode(', ') . '.' : '' }} Kuota penerimaan terbatas.
                </p>
            </div>

            <a href="https://polmind.ac.id/pmb" target="_blank" class="text-sm font-bold text-polmind-blue hover:underline">
                Info Lengkap <x-icon name="arrow-up-right" class="inline h-4 w-4 align-[-2px]" stroke-width="2" />
            </a>
        </div>

        @php
            // Hiasan kartu per kode prodi; isi utama (nama, jenjang, deskripsi, kuota) dari Master Data.
            $programDecor = [
                'TRPL' => ['icon' => 'computer', 'tags' => ['IT', 'Magang Industri']],
                'BD' => ['icon' => 'presentation', 'tags' => ['Bisnis Digital', 'Magang Industri']],
                'TRM' => ['icon' => 'wrench', 'tags' => ['Manufaktur', 'Magang Industri']],
            ];
        @endphp

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($studyPrograms as $program)
                @php
                    $decor = $programDecor[$program->code] ?? ['icon' => 'cap', 'tags' => ['Magang Industri']];
                    // Foto prodi: public/images/prodi/{kode}.webp, contoh bd.webp. Tanpa foto, tampil ikon.
                    $photo = 'images/prodi/' . strtolower($program->code) . '.webp';
                    $hasPhoto = file_exists(public_path($photo));
                @endphp
                <article id="prodi-{{ strtolower($program->code) }}" class="group scroll-mt-28 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="relative flex h-52 items-center justify-center overflow-hidden bg-gradient-to-br from-blue-900 to-blue-950">
                        @if($hasPhoto)
                            <img src="{{ asset($photo) }}"
                                 alt="Kegiatan mahasiswa {{ $program->degree }} {{ $program->name }} Polmind"
                                 loading="lazy"
                                 width="1280"
                                 height="853"
                                 class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-950/50 via-transparent to-transparent"></div>
                        @else
                            <x-icon :name="$decor['icon']" class="h-20 w-20 text-white/90" stroke-width="1" />
                        @endif

                        <div class="absolute right-4 top-4 rounded-full bg-white px-3 py-1 text-[10px] font-black text-polmind-blue shadow-sm">
                            JENJANG {{ $program->degree }}
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-lg font-black leading-6 text-polmind-blue">
                            {{ $program->degree }} {{ $program->name }}
                        </h3>
                        @if($program->description)
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ $program->description }}
                            </p>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-2">
                            {{-- Kuota di card program studi di-hide sementara --}}
                            @foreach($decor['tags'] as $tag)
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-polmind-blue">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 md:col-span-2 lg:col-span-3">
                    Informasi program studi segera diumumkan.
                </p>
            @endforelse
        </div>
    </div>
</section>

{{-- PILIHAN KELAS (skema diatur admin di Master Data) --}}
@if($classTypes->isNotEmpty())
<section id="kelas" class="scroll-mt-20 bg-polmind-bg py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-black tracking-tight text-polmind-blue sm:text-4xl">
                Pilihan Kelas
            </h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Pilih kelas yang sesuai dengan kondisi Anda saat registrasi. Kelas tidak dapat diubah setelah mendaftar.
            </p>
        </div>

        <div class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-2">
            @foreach($classTypes as $classType)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-black text-polmind-blue">{{ $classType->name }}</h3>
                            @if($classType->description)
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $classType->description }}</p>
                            @endif
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-polmind-yellow text-lg font-black text-polmind-blue-dark">
                            {{ $classType->registration_code ?? mb_substr($classType->name, -1) }}
                        </span>
                    </div>

                    @if($classType->schedule)
                        <p class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-black text-polmind-blue">
                            <x-icon name="clock" class="h-4 w-4" stroke-width="2" /> {{ $classType->schedule }}
                        </p>
                    @endif

                    @if($classType->scheme_points)
                        <ul class="mt-5 space-y-2 text-sm leading-6 text-slate-600">
                            @foreach($classType->scheme_points as $point)
                                <li class="flex gap-3">
                                    <span class="font-black text-green-600" aria-hidden="true">✓</span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- JADWAL & BIAYA --}}
<section id="biaya" class="scroll-mt-20 bg-polmind-blue py-20">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="rounded-3xl bg-white/10 p-6 shadow-xl ring-1 ring-white/10 sm:p-8">
            <h2 class="text-2xl font-black text-white">Jadwal Gelombang</h2>

            @php
                $waveStatus = [
                    'open' => ['Dibuka', 'bg-green-400 text-green-950'],
                    'upcoming' => ['Akan Datang', 'bg-white/15 text-blue-50'],
                    'closed' => ['Ditutup', 'bg-white/5 text-blue-200'],
                ];
            @endphp

            <div class="mt-6 space-y-4">
                @forelse($waves as $wave)
                    @php
                        $status = $wave->publicStatus($currentWave);
                        [$statusLabel, $statusClass] = $waveStatus[$status];
                    @endphp
                    <div @class([
                        'rounded-2xl border p-5 text-white',
                        'border-green-300/60 bg-white/15 ring-2 ring-green-300/40' => $status === 'open',
                        'border-white/20 bg-white/10' => $status === 'upcoming',
                        'border-white/10 bg-white/5 opacity-70' => $status === 'closed',
                    ])>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-black">{{ $wave->name }}</h3>
                            <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wide {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <dl class="mt-3 grid gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                            @foreach($wave->timeline() as $row)
                                <div class="flex justify-between gap-3 sm:block">
                                    <dt class="text-blue-200">{{ $row['label'] }}</dt>
                                    <dd class="text-right font-bold sm:text-left">{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @empty
                    <p class="rounded-2xl border border-white/20 bg-white/10 p-5 text-sm text-blue-100">Jadwal gelombang pendaftaran segera diumumkan.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl bg-polmind-yellow p-6 shadow-xl sm:p-8">
            <h2 class="text-2xl font-black text-polmind-blue-dark">Rincian Biaya</h2>
            <p class="mt-3 text-sm leading-6 text-polmind-blue-dark/80">
                Rincian biaya perkuliahan{{ $pmbYear ? ' TA ' . $pmbYear->year . '/' . ($pmbYear->year + 1) : '' }} sesuai ketetapan institusi.
            </p>

            @if($registrationFee)
                <div class="mt-8 rounded-2xl bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">Biaya Formulir Pendaftaran</p>
                    <p class="mt-2 text-3xl font-black text-polmind-blue-dark">Rp{{ number_format($registrationFee, 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-600">Dibayar setelah membuat akun, sebelum mengisi biodata dan mengunggah berkas.</p>
                </div>
            @endif

            <div class="mt-8 divide-y divide-yellow-500/40">
                @forelse($reRegistrationFees as $fee)
                    <div class="flex items-baseline justify-between gap-4 py-4 first:pt-0 last:pb-0">
                        <span class="font-semibold text-polmind-blue-dark">{{ $fee->name }}</span>
                        <span class="shrink-0 font-black text-polmind-blue-dark">
                            {{ $fee->amount > 0 ? 'Rp' . number_format($fee->amount, 0, ',', '.') : 'Segera diumumkan' }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm font-semibold text-polmind-blue-dark">Rincian SPI dan UKT segera diumumkan.</p>
                @endforelse
            </div>

            <p class="mt-8 rounded-2xl bg-white/40 p-4 text-xs leading-5 text-polmind-blue-dark">
                SPI dan UKT dibayar setelah dinyatakan diterima sebagai mahasiswa baru. Tersedia keringanan biaya SPI bagi pendaftar gelombang awal.
            </p>
        </div>
    </div>
</section>

{{-- BROSUR (diunggah admin di menu Brosur) --}}
@if($brochures->isNotEmpty())
<section id="brosur" class="scroll-mt-20 bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-black text-polmind-blue sm:text-4xl">
                Brosur PMB
            </h2>
            <p class="mt-3 text-sm text-slate-600">
                Unduh brosur untuk informasi lengkap program studi, jalur masuk, dan biaya kuliah.
            </p>
        </div>

        {{-- Lebar grid mengikuti jumlah brosur agar kartu tetap di tengah. --}}
        <div class="mx-auto mt-10 grid gap-5 {{ match (min($brochures->count(), 3)) { 1 => 'max-w-sm', 2 => 'max-w-3xl sm:grid-cols-2', default => 'max-w-5xl sm:grid-cols-2 lg:grid-cols-3' } }}">
            @foreach($brochures as $brochure)
                <a href="{{ route('brochures.download', $brochure) }}"
                   class="group flex flex-col rounded-2xl border border-slate-200 bg-polmind-bg p-6 transition hover:border-polmind-blue hover:bg-blue-50">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-polmind-blue shadow-sm">
                        <x-icon name="book" class="h-6 w-6" />
                    </span>
                    <span class="mt-5 text-lg font-black text-slate-900">{{ $brochure->title }}</span>
                    @if($brochure->description)
                        <span class="mt-2 text-sm leading-6 text-slate-600">{{ $brochure->description }}</span>
                    @endif
                    <span class="mt-auto flex items-center justify-between gap-3 pt-6 text-sm">
                        <span class="font-semibold text-slate-500">{{ $brochure->extension }} · {{ $brochure->size_label }}</span>
                        <span class="inline-flex items-center gap-1.5 font-bold text-polmind-blue">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                            Unduh
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- FAQ --}}
<section id="faq" class="scroll-mt-20 bg-polmind-bg py-20">
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
                ['q' => 'Siapa yang dapat mendaftar?', 'a' => 'Lulusan SMA/SMK/MA atau sederajat.'],
                ['q' => 'Dokumen apa saja yang harus disiapkan?', 'a' => $documentTypes->isEmpty()
                    ? 'Daftar dokumen akan diinformasikan setelah Anda membuat akun.'
                    : 'Wajib: ' . $documentTypes->where('is_required', true)->pluck('name')->implode(', ') . '.'
                        . ($documentTypes->where('is_required', false)->isNotEmpty() ? ' Opsional: ' . $documentTypes->where('is_required', false)->pluck('name')->implode(', ') . '.' : '')],
                ['q' => 'Kapan pembayaran SPI dan UKT dilakukan?', 'a' => 'SPI dan UKT dibayar setelah pendaftar dinyatakan diterima sebagai mahasiswa baru. Nominalnya tercantum di bagian Rincian Biaya.'],
                ['q' => 'Apakah ada keringanan biaya?', 'a' => 'Tersedia program keringanan biaya SPI untuk pendaftar gelombang awal. Hubungi admin PMB untuk detailnya.'],
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
                <a href="{{ \App\Services\AdminWhatsApp::url() }}"
                   target="_blank"
                   class="rounded-xl border border-white/20 bg-white/10 px-7 py-3 text-sm font-black text-white transition hover:bg-white/20">
                    Konsultasi Admin PMB
                </a>
            </div>
        </div>
    </div>
</section>

<script>
    // Slider hero: berganti otomatis tiap 6 detik, berhenti saat disentuh/di-hover,
    // dan tidak berjalan otomatis jika pengguna memilih mengurangi animasi.
    window.heroSlider = (count) => ({
        active: 0,
        timer: null,

        init() {
            this.start();
        },

        start() {
            this.stop();

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            this.timer = setInterval(() => this.next(), 6000);
        },

        stop() {
            clearInterval(this.timer);
            this.timer = null;
        },

        next() {
            this.active = (this.active + 1) % count;
        },

        prev() {
            this.active = (this.active - 1 + count) % count;
        },

        go(index) {
            this.active = index;
        },
    });
</script>

@endsection