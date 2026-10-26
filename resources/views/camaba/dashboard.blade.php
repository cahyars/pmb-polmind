@extends('layouts.camaba')

@section('title', 'Dashboard Camaba')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Pantau progres pendaftaran mahasiswa baru Anda.')

@section('content')
@php
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');

    $registrationPaid = $journey->registrationPaid();
    $isTemporaryNumber = \App\Services\RegistrationNumber::isTemporary($applicant->registration_number);

    $stateStyles = [
        'done' => [
            'card' => 'border-green-400 bg-green-50/60 ring-1 ring-green-200',
            'dot' => 'bg-green-600 text-white',
            'badge' => 'bg-green-600 text-white',
            'badgeText' => '✓ Lengkap',
        ],
        'current' => [
            'card' => 'border-polmind-blue bg-white ring-2 ring-blue-100',
            'dot' => 'bg-polmind-blue text-white',
            'badge' => 'bg-blue-100 text-polmind-blue',
            'badgeText' => 'Perlu Dikerjakan',
        ],
        'waiting' => [
            'card' => 'border-yellow-300 bg-yellow-50/60',
            'dot' => 'bg-polmind-yellow text-polmind-blue-dark',
            'badge' => 'bg-yellow-100 text-yellow-800',
            'badgeText' => 'Menunggu',
        ],
        'locked' => [
            'card' => 'border-slate-200 bg-slate-50 opacity-75',
            'dot' => 'bg-slate-200 text-slate-500',
            'badge' => 'bg-slate-200 text-slate-600',
            'badgeText' => '🔒 Terkunci',
        ],
    ];

    $guides = [
        [
            'title' => '1. Bayar biaya formulir',
            'body' => 'Buka menu Pembayaran, transfer biaya formulir sesuai tagihan, lalu unggah foto/scan bukti transfer. Admin akan memverifikasi maksimal 1x24 jam kerja. Menu biodata dan berkas terbuka otomatis setelah pembayaran diverifikasi.',
        ],
        [
            'title' => '2. Lengkapi biodata (5 langkah)',
            'body' => 'Isi Data Diri, Program Studi & Kelas, Alamat, Sekolah Asal, serta Orang Tua/Wali. Setiap langkah tersimpan saat menekan "Simpan & Lanjutkan", jadi Anda bisa melanjutkan kapan saja. Siapkan KTP, Kartu Keluarga, dan ijazah/SKL agar data sesuai dokumen. Nomor pendaftaran final terbentuk setelah memilih kelas.',
        ],
        [
            'title' => '3. Unggah berkas persyaratan',
            'body' => 'Unggah pas foto (otomatis menjadi foto profil), KTP, Kartu Keluarga, dan ijazah/SKL dalam format JPG/PNG/PDF. Pastikan tulisan terbaca jelas. Jika berkas ditolak, baca catatan admin lalu unggah ulang.',
        ],
        [
            'title' => '4. Ikuti seleksi dan pantau pengumuman',
            'body' => 'Ikuti ujian seleksi sesuai jadwal gelombang Anda. Hasil seleksi diumumkan di menu Pengumuman.',
        ],
        [
            'title' => '5. Daftar ulang',
            'body' => 'Jika diterima, selesaikan pembayaran daftar ulang (SPI/UKT). Setelah valid, data Anda dikirim ke SIAKAD dan NIM diterbitkan.',
        ],
    ];
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Pengingat tagihan formulir --}}
    @if(! $registrationPaid && $registrationInvoice)
        <div class="overflow-hidden rounded-3xl border-2 {{ $journey->paymentWaiting() ? 'border-yellow-300 bg-yellow-50' : 'border-red-200 bg-red-50' }} p-6 shadow-sm">
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div class="flex gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $journey->paymentWaiting() ? 'bg-yellow-100' : 'bg-red-100' }} text-2xl">
                        {{ $journey->paymentWaiting() ? '⏳' : '💳' }}
                    </div>
                    <div>
                        @if($journey->paymentWaiting())
                            <h2 class="text-lg font-black text-yellow-900">Pembayaran sedang diverifikasi</h2>
                            <p class="mt-1 text-sm leading-6 text-yellow-900/80">
                                Bukti bayar formulir sudah kami terima. Pengisian biodata dan berkas terbuka setelah admin memverifikasi pembayaran.
                            </p>
                        @else
                            <h2 class="text-lg font-black text-red-800">Segera bayar biaya formulir</h2>
                            <p class="mt-1 text-sm leading-6 text-red-800/80">
                                Tagihan <span class="font-bold">{{ $registrationInvoice->invoice_number }}</span> sebesar
                                <span class="font-black">{{ $rupiah($registrationInvoice->total_amount) }}</span>
                                @if($registrationInvoice->due_date)
                                    jatuh tempo <span class="font-bold">{{ $registrationInvoice->due_date->translatedFormat('d F Y') }}</span>.
                                @endif
                                Biodata, berkas, dan tahapan lainnya masih terkunci sampai pembayaran diverifikasi.
                            </p>
                        @endif
                    </div>
                </div>

                <a href="{{ route('camaba.payments.index') }}"
                   class="inline-flex shrink-0 items-center justify-center rounded-xl {{ $journey->paymentWaiting() ? 'bg-yellow-500 text-yellow-950 hover:bg-yellow-400' : 'bg-red-600 text-white hover:bg-red-700' }} px-6 py-3 text-sm font-black shadow-sm transition">
                    {{ $journey->paymentWaiting() ? 'Lihat Status Pembayaran' : 'Bayar Sekarang' }} →
                </a>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div class="flex items-center gap-5">
                @if($applicant->photoDocument)
                    <img src="{{ route('camaba.documents.file', $applicant->photoDocument) }}"
                         alt="Foto {{ $applicant->full_name }}"
                         class="hidden h-20 w-20 shrink-0 rounded-2xl bg-white object-cover ring-4 ring-white/20 sm:block">
                @endif

                <div>
                    <p class="text-sm font-bold text-blue-100">Selamat datang,</p>
                    <h1 class="mt-1 text-2xl font-black sm:text-3xl">{{ $applicant->full_name }}</h1>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black text-white">
                            No. Pendaftaran: {{ $applicant->registration_number }}
                        </span>
                        @if($applicant->studyProgram)
                            <span class="rounded-full bg-polmind-yellow px-3 py-1 text-xs font-black text-polmind-blue-dark">
                                {{ $applicant->studyProgram->code }}
                            </span>
                        @endif
                        @if($applicant->classType)
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black text-white">
                                {{ $applicant->classType->name }}
                            </span>
                        @endif
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black text-white">
                            {{ $applicant->admissionWave?->name ?? '-' }} · {{ $applicant->pmbYear?->name }}
                        </span>
                    </div>

                    @if($isTemporaryNumber)
                        <p class="mt-3 text-xs leading-5 text-blue-100">
                            Nomor di atas adalah nomor sementara. Nomor pendaftaran final terbentuk setelah Anda memilih kelas di biodata.
                        </p>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-80">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-blue-100">Progress Pendaftaran</p>
                    <p class="text-2xl font-black text-polmind-yellow">{{ $journey->progressPercent() }}%</p>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ $journey->progressPercent() }}%"></div>
                </div>

                <p class="mt-3 text-xs text-blue-100">
                    {{ $steps->where('state', 'done')->count() }} dari {{ $steps->count() }} tahapan selesai
                </p>
            </div>
        </div>
    </div>

    {{-- Langkah berikutnya --}}
    @if($nextStep)
        <div class="rounded-3xl border-2 border-polmind-blue bg-white p-6 shadow-lg shadow-blue-900/10">
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-polmind-blue">Langkah Berikutnya</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-900">{{ $nextStep['label'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $nextStep['description'] }}</p>
                </div>

                @if($nextStep['url'])
                    <a href="{{ $nextStep['url'] }}"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-polmind-blue px-7 py-4 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                        Lanjutkan: {{ $nextStep['action'] }} <span aria-hidden="true">→</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

        <div class="space-y-8">

            {{-- Timeline --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">Tahapan Pendaftaran</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Kerjakan tahapan secara berurutan. Tahapan yang selesai ditandai hijau.
                    </p>
                </div>

                <ol class="mt-6 space-y-4">
                    @foreach($steps as $index => $step)
                        @php
                            $style = $stateStyles[$step['state']];
                        @endphp

                        <li class="rounded-2xl border p-5 transition {{ $style['card'] }}">
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-black {{ $style['dot'] }}">
                                    {{ $step['state'] === 'done' ? '✓' : $index + 1 }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-black text-slate-900">{{ $step['label'] }}</p>
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $style['badge'] }}">
                                            {{ $style['badgeText'] }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $step['description'] }}</p>

                                    @if($step['key'] === 'biodata' && $registrationPaid)
                                        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach($biodataSections as $sectionKey => $section)
                                                <a href="{{ route('camaba.biodata.edit', ['step' => $sectionKey]) }}"
                                                   class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition
                                                   {{ $section['complete'] ? 'border-green-400 bg-white text-green-800' : 'border-slate-200 bg-white text-slate-600 hover:border-polmind-blue' }}">
                                                    <span class="truncate">{{ $section['label'] }}</span>
                                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] {{ $section['complete'] ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                                        {{ $section['complete'] ? '✓ Lengkap' : 'Belum' }}
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if($step['url'] && in_array($step['state'], ['done', 'current', 'waiting'], true))
                                    <a href="{{ $step['url'] }}"
                                       class="hidden shrink-0 self-center rounded-xl border px-4 py-2 text-xs font-black transition sm:inline-flex
                                       {{ $step['state'] === 'current' ? 'border-polmind-blue bg-polmind-blue text-white hover:bg-polmind-blue-dark' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                                        {{ $step['state'] === 'done' ? 'Lihat' : 'Lanjutkan' }}
                                    </a>
                                @elseif($step['state'] === 'locked' && ! $registrationPaid && $step['url'])
                                    <button type="button"
                                            @click="window.showPaymentRequired()"
                                            class="hidden shrink-0 self-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-black text-slate-500 sm:inline-flex">
                                        🔒 Terkunci
                                    </button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Tagihan daftar ulang --}}
            @if($reRegistrationInvoice)
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-polmind-blue">Tagihan Daftar Ulang</h2>

                    <div class="mt-5 grid gap-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-bold text-slate-500">Nomor Invoice</p>
                            <p class="mt-1 font-black text-polmind-blue">{{ $reRegistrationInvoice->invoice_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500">Total Tagihan</p>
                            <p class="mt-1 text-xl font-black text-green-700">{{ $rupiah($reRegistrationInvoice->total_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500">Jatuh Tempo</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $reRegistrationInvoice->due_date?->translatedFormat('d F Y') ?? '-' }}</p>
                        </div>
                    </div>

                    <a href="{{ route('camaba.re-registration.index') }}"
                       class="mt-5 inline-flex rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Lihat Tagihan Daftar Ulang
                    </a>
                </div>
            @endif
        </div>

        <aside class="space-y-6">

            {{-- Panduan --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ open: {{ $registrationPaid ? 'null' : '0' }} }">
                <h2 class="text-lg font-black text-polmind-blue">📘 Panduan Pendaftaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Klik setiap langkah untuk melihat cara mengisinya.</p>

                <div class="mt-5 space-y-2">
                    @foreach($guides as $guideIndex => $guide)
                        <div class="rounded-2xl border border-slate-200">
                            <button type="button"
                                    @click="open = open === {{ $guideIndex }} ? null : {{ $guideIndex }}"
                                    :aria-expanded="open === {{ $guideIndex }}"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm font-bold text-slate-800">
                                <span>{{ $guide['title'] }}</span>
                                <span class="text-slate-400 transition" :class="open === {{ $guideIndex }} ? 'rotate-180' : ''">⌄</span>
                            </button>
                            <div x-show="open === {{ $guideIndex }}" x-cloak class="px-4 pb-4 text-sm leading-6 text-slate-600">
                                {{ $guide['body'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tagihan formulir --}}
            @if($registrationInvoice)
                <div class="rounded-3xl border {{ $registrationPaid ? 'border-green-400 ring-1 ring-green-200' : 'border-slate-200' }} bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black text-polmind-blue">Biaya Formulir</h2>
                        @if($registrationPaid)
                            <span class="rounded-full bg-green-600 px-2.5 py-0.5 text-[11px] font-black text-white">✓ Lunas</span>
                        @endif
                    </div>
                    <p class="mt-3 text-2xl font-black text-slate-900">{{ $rupiah($registrationInvoice->total_amount) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $registrationInvoice->invoice_number }}</p>

                    <a href="{{ route('camaba.payments.index') }}"
                       class="mt-5 inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        {{ $registrationPaid ? 'Lihat Riwayat Pembayaran' : 'Bayar / Unggah Bukti' }}
                    </a>
                </div>
            @endif

            {{-- Hasil seleksi --}}
            @if($applicant->selection)
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-black text-polmind-blue">Hasil Seleksi</h2>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        @foreach(['Tes' => $applicant->selection->test_score, 'Interview' => $applicant->selection->interview_score, 'Final' => $applicant->selection->final_score] as $scoreLabel => $score)
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">{{ $scoreLabel }}</p>
                                <p class="mt-1 font-black text-slate-800">{{ $score ?? '-' }}</p>
                            </div>
                        @endforeach
                    </div>
                    @if($applicant->selection->note)
                        <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $applicant->selection->note }}</p>
                    @endif
                </div>
            @endif

            {{-- Bantuan --}}
            <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
                <h2 class="text-lg font-black text-polmind-blue">Butuh Bantuan?</h2>
                <p class="mt-3 text-sm leading-6 text-slate-700">
                    Jika mengalami kendala pendaftaran, hubungi admin PMB Politeknik Mitra Industri.
                </p>

                <a href="https://wa.me/6282113296897?text={{ rawurlencode('Halo Admin PMB, saya ' . $applicant->full_name . ' (' . $applicant->registration_number . ') ingin bertanya.') }}"
                   target="_blank"
                   rel="noopener"
                   class="mt-5 inline-flex w-full justify-center rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-700">
                    Hubungi Admin PMB
                </a>
            </div>
        </aside>
    </div>
</div>
@endsection
