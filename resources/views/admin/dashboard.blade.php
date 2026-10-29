@extends('layouts.admin')

@section('title', 'Dashboard Admin PMB')
@section('page_title', 'Dashboard PMB')
@section('page_subtitle', 'Ringkasan penerimaan mahasiswa baru Politeknik Mitra Industri.')

@section('content')
<div class="space-y-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">
                    Admin PMB Polmind
                </p>

                <h2 class="mt-3 text-3xl font-bold sm:text-4xl">
                    Monitoring Penerimaan Mahasiswa Baru
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Pantau progres pendaftaran, kelengkapan biodata, verifikasi berkas,
                    pembayaran, seleksi, daftar ulang, dan kesiapan sinkronisasi ke SIAKAD.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-blue-100">
                        Tahun PMB: {{ $selectedPmbYear?->name ?? '-' }}
                    </span>
                    @php $openWave = \App\Models\AdmissionWave::current(); @endphp
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-bold text-polmind-blue-dark">
                        {{ $openWave ? $openWave->name . ' Dibuka' : 'Belum ada gelombang dibuka' }}
                    </span>
                </div>
            </div>

            <div class="rounded-xl bg-white/10 p-6">
                <p class="text-sm font-semibold text-blue-100">Mahasiswa Baru</p>
                <p class="mt-3 text-4xl font-bold">
                    {{ $validReRegistrations }}<span class="text-xl font-semibold text-blue-100"> / {{ $targetNewStudents }}</span>
                </p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-polmind-yellow" style="width: {{ min($targetProgress, 100) }}%"></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Target {{ $targetNewStudents }} mahasiswa baru (daftar ulang valid). Progress sekitar {{ $targetProgress }}% · Total pendaftar {{ $totalApplicants }}.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Stats --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Total Camaba',
                'value' => $totalApplicants,
                'desc' => 'Semua pendaftar',
                'url' => '/admin/applicants',
                'class' => 'bg-blue-100 text-polmind-blue',
                'accent' => 'border-t-polmind-blue',
            ],
            [
                'label' => 'Berkas Menunggu',
                'value' => $pendingDocuments,
                'desc' => 'Perlu diverifikasi',
                'url' => '/admin/documents?status=menunggu_verifikasi',
                'class' => 'bg-yellow-100 text-yellow-700',
                'accent' => 'border-t-amber-400',
            ],
            [
                'label' => 'Pembayaran Menunggu',
                'value' => $pendingPayments,
                'desc' => 'Perlu validasi pembayaran',
                'url' => '/admin/payments?status=waiting_verification',
                'class' => 'bg-purple-100 text-purple-700',
                'accent' => 'border-t-purple-500',
            ],
            [
                'label' => 'Daftar Ulang Valid',
                'value' => $validReRegistrations,
                'desc' => 'Siap proses akademik',
                'url' => '/admin/re-registrations?status=valid',
                'class' => 'bg-green-100 text-green-700',
                'accent' => 'border-t-green-500',
            ],
        ] as $card)
            <a href="{{ url($card['url']) }}"
            class="rounded-lg border border-t-[3px] border-slate-200 {{ $card['accent'] }} bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-[28px] font-bold leading-tight text-slate-900">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $card['class'] }}">
                        Detail
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Registration Path Stats --}}
    <div class="grid gap-5 md:grid-cols-3">
        @foreach(\App\Models\RegistrationPath::ordered()
            ->filter(fn ($path) => $path->is_active || ($registrationPathStats[$path->code] ?? 0) > 0)
            ->map(fn ($path) => [
                'label' => 'Jalur ' . $path->name,
                'value' => $registrationPathStats[$path->code] ?? 0,
                'desc' => 'Pendaftar jalur ' . mb_strtolower($path->name),
                'class' => \App\Models\RegistrationPath::textClass($path->code),
                'url' => '/admin/applicants?registration_path=' . $path->code,
            ]) as $card)
            <a href="{{ url($card['url']) }}"
            class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Module Shortcuts --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    Shortcut Modul PMB
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Akses cepat ke seluruh modul utama admin PMB.
                </p>
            </div>

            <a href="{{ url('/admin/reports') }}"
               class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Lihat Laporan
            </a>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['label' => 'Data Camaba', 'desc' => 'Kelola data pendaftar', 'url' => '/admin/applicants', 'icon' => 'users'],
                ['label' => 'Berkas', 'desc' => 'Validasi dokumen', 'url' => '/admin/documents', 'icon' => 'document'],
                ['label' => 'Pembayaran', 'desc' => 'Verifikasi bukti bayar', 'url' => '/admin/payments', 'icon' => 'card'],
                ['label' => 'Seleksi', 'desc' => 'Tetapkan hasil seleksi', 'url' => '/admin/selections', 'icon' => 'check'],
                ['label' => 'Daftar Ulang', 'desc' => 'Validasi DU camaba', 'url' => '/admin/re-registrations', 'icon' => 'cap'],
                ['label' => 'Follow Up', 'desc' => 'CRM dan reminder WA', 'url' => '/admin/follow-ups', 'icon' => 'chat'],
                ['label' => 'Laporan', 'desc' => 'Rekap pimpinan', 'url' => '/admin/reports', 'icon' => 'chart'],
                ...(auth('web')->user()?->isAdmin() ? [['label' => 'Master Data', 'desc' => 'Konfigurasi PMB', 'url' => '/admin/master-data', 'icon' => 'cog']] : []),
                ['label' => 'Integrasi', 'desc' => 'Sinkron ke SIAKAD', 'url' => '/admin/integrations', 'icon' => 'sync'],
                ['label' => 'Landing PMB', 'desc' => 'Lihat halaman publik', 'url' => '/', 'icon' => 'computer'],
            ] as $module)
                <a href="{{ url($module['url']) }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 p-5 transition hover:border-polmind-blue hover:bg-blue-50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-polmind-blue shadow-sm">
                        <x-icon :name="$module['icon']" class="h-6 w-6" />
                    </div>

                    <h3 class="mt-4 text-sm font-bold text-polmind-blue">
                        {{ $module['label'] }}
                    </h3>

                    <p class="mt-2 text-xs leading-5 text-slate-600">
                        {{ $module['desc'] }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Main Column --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Funnel --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-base font-semibold text-slate-900">
                        Funnel PMB
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Progres camaba dari registrasi awal sampai siap sinkron akademik.
                    </p>
                </div>

                <div class="mt-6 space-y-5">
                    @foreach($funnel as $item)
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">
                                        {{ $item['label'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $item['value'] }} camaba
                                    </p>
                                </div>

                                <p class="text-sm font-bold text-polmind-blue">
                                    {{ $item['percent'] }}%
                                </p>
                            </div>

                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $item['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Prodi Distribution --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Sebaran Program Studi
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Ringkasan pendaftar dan daftar ulang berdasarkan program studi.
                        </p>
                    </div>

                    <a href="{{ url('/admin/reports') }}"
                       class="rounded-lg border border-polmind-blue bg-white px-5 py-3 text-sm font-semibold text-polmind-blue transition hover:bg-blue-50">
                        Detail Laporan
                    </a>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    @foreach($programs as $program)
                        @php
                            $target = max($program->quota, 1);
                            $percent = round(($program->re_registered_count / $target) * 100);
                        @endphp

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900">
                                        {{ $program->code }}
                                    </h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        {{ $program->name }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                                    {{ $program->re_registered_count }}/{{ $program->quota }}
                                </span>
                            </div>

                            <div class="mt-5">
                                <div class="flex justify-between text-xs font-semibold text-slate-600">
                                    <span>Pendaftar: {{ $program->registrants_count }}</span>
                                    <span>{{ $percent }}%</span>
                                </div>

                                <div class="mt-2 h-3 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-polmind-blue" style="width: {{ min($percent, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Aktivitas Terbaru
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Log aktivitas PMB yang perlu dipantau admin.
                        </p>
                    </div>

                    <span class="rounded-full bg-green-100 px-4 py-2 text-xs font-bold text-green-700">
                        Real Database
                    </span>
                </div>

                <div class="mt-6 space-y-5">
                    @forelse($latestPayments as $payment)
                        <a href="{{ url('/admin/payments') }}"
                        class="flex gap-4 rounded-lg border border-slate-200 bg-slate-50 p-5 transition hover:bg-blue-50">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white text-polmind-blue shadow-sm">
                                <x-icon name="card" class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                                    <div>
                                        <p class="font-bold text-slate-900">
                                            Pembayaran {{ $payment->applicant?->full_name ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            {{ $payment->payment_number }} ·
                                            Rp{{ number_format($payment->amount, 0, ',', '.') }} ·
                                            {{ str_replace('_', ' ', $payment->status) }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 text-xs font-semibold text-slate-500">
                                        {{ $payment->created_at?->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm font-semibold text-slate-500">
                            Belum ada aktivitas pembayaran terbaru.
                        </div>
                    @endforelse

                    @forelse($latestDocuments->take(3) as $document)
                        <a href="{{ url('/admin/documents') }}"
                        class="flex gap-4 rounded-lg border border-slate-200 bg-slate-50 p-5 transition hover:bg-blue-50">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white text-polmind-blue shadow-sm">
                                <x-icon name="document" class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                                    <div>
                                        <p class="font-bold text-slate-900">
                                            Berkas {{ $document->documentType?->name ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            {{ $document->applicant?->full_name ?? '-' }} ·
                                            {{ str_replace('_', ' ', $document->status) }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 text-xs font-semibold text-slate-500">
                                        {{ $document->created_at?->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @empty
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Side Column --}}
        <aside class="space-y-6">

            {{-- Attention Needed --}}
            <div class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 text-red-700">
                    <x-icon name="bell" class="h-6 w-6" />
                </div>

                <h3 class="mt-5 text-lg font-bold text-red-800">
                    Perlu Tindak Lanjut
                </h3>

                <p class="mt-3 text-sm leading-6 text-red-800">
                    Ada beberapa data yang perlu diproses agar flow PMB tidak tertahan.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach([
                        ['label' => 'Berkas menunggu', 'value' => $pendingDocuments, 'url' => '/admin/documents'],
                        ['label' => 'Pembayaran menunggu', 'value' => $pendingPayments, 'url' => '/admin/payments'],
                        ['label' => 'Siap sinkron SIAKAD', 'value' => $readySyncApplicants, 'url' => '/admin/integrations'],
                    ] as $item)
                        <a href="{{ url($item['url']) }}"
                           class="flex items-center justify-between rounded-lg bg-white/70 p-4 transition hover:bg-white">
                            <span class="text-sm font-semibold text-red-800">{{ $item['label'] }}</span>
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">
                                {{ $item['value'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Sync Status --}}
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-polmind-blue">
                    <x-icon name="sync" class="h-6 w-6" />
                </div>

                <h3 class="mt-5 text-base font-semibold text-slate-900">
                    Status Integrasi
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Data daftar ulang valid akan disiapkan untuk sinkronisasi ke SIAKAD.
                </p>

                <div class="mt-5 grid gap-3">
                    <div class="rounded-lg bg-white p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Siap Sinkron
                        </p>
                        <p class="mt-2 text-3xl font-bold text-purple-700">
                            {{ $readySyncApplicants }} Data
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg bg-white p-3 text-center">
                            <p class="text-xs font-semibold text-slate-500">Proses</p>
                            <p class="mt-1 text-xl font-bold text-yellow-700">
                                {{ $processingSyncApplicants }}
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-3 text-center">
                            <p class="text-xs font-semibold text-slate-500">Sukses</p>
                            <p class="mt-1 text-xl font-bold text-green-700">
                                {{ $syncedApplicants }}
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-3 text-center">
                            <p class="text-xs font-semibold text-slate-500">Gagal</p>
                            <p class="mt-1 text-xl font-bold text-red-700">
                                {{ $failedSyncApplicants }}
                            </p>
                        </div>
                    </div>
                </div>

                <a href="{{ url('/admin/integrations') }}"
                   class="mt-5 inline-flex w-full justify-center rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white transition hover:bg-polmind-blue-dark">
                    Buka Integrasi
                </a>
            </div>

            {{-- Rekap harian untuk grup WhatsApp --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ copied: false }">
                <h2 class="text-base font-semibold text-slate-900">Laporan Singkat</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Rekap harian semua pendaftar yang sudah registrasi. Pertambahan dihitung dari
                    {{ $recapWindow[0]->translatedFormat('j M H.i') }} s.d. {{ $recapWindow[1]->translatedFormat('j M H.i') }} WIB.
                </p>

                <form action="{{ route('admin.dashboard') }}" method="GET" class="mt-4 grid grid-cols-2 gap-2">
                    <select name="rekap_gelombang" aria-label="Gelombang rekap" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue">
                        <option value="semua" @selected(! $recapWave)>Semua gelombang</option>
                        @foreach($recapWaves as $wave)
                            <option value="{{ $wave->id }}" @selected($recapWave?->id === $wave->id)>{{ $wave->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="rekap_tanggal" aria-label="Tanggal rekap" value="{{ $recapDate->toDateString() }}" max="{{ now()->toDateString() }}"
                           onchange="this.form.submit()"
                           class="rounded-lg border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue">
                </form>

                <pre class="mt-3 max-h-96 overflow-y-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 font-sans text-xs leading-5 text-slate-700">{{ $recapText }}</pre>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="button"
                            @click="navigator.clipboard.writeText(@js($recapText)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="rounded-lg border border-polmind-blue bg-white px-3 py-2.5 text-sm font-semibold text-polmind-blue transition hover:bg-blue-50">
                        <span x-text="copied ? 'Tersalin' : 'Salin Teks'">Salin Teks</span>
                    </button>
                    <a href="https://wa.me/?text={{ rawurlencode($recapText) }}" target="_blank" rel="noopener"
                       class="rounded-lg bg-green-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-green-700">
                        Kirim ke WhatsApp
                    </a>
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">"Kirim ke WhatsApp" membuka WhatsApp untuk memilih grup tujuan.</p>
            </div>

            {{-- Admin Notes --}}
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-100 text-yellow-800">
                    <x-icon name="bookmark" class="h-6 w-6" />
                </div>

                <h3 class="mt-5 text-lg font-bold text-yellow-800">
                    Catatan Pengembangan
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Dashboard sudah menggunakan data real database.</li>
                    <li>• Monitoring jalur pendaftaran sudah aktif.</li>
                    <li>• Integrasi SIAKAD sudah masuk tahap simulasi workflow.</li>
                    <li>• Tahap berikutnya: laporan detail dan export data.</li>
                </ul>
            </div>

        </aside>
    </div>

</div>
@endsection