@extends('layouts.admin')

@section('title', 'Laporan PMB')
@section('page_title', 'Laporan PMB')
@section('page_subtitle', 'Pantau ringkasan performa penerimaan mahasiswa baru secara menyeluruh.')

@section('content')
<div class="space-y-8">

    {{-- Header Report --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                    Laporan PMB 2026
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Rekap Penerimaan Mahasiswa Baru
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                    Laporan ini menampilkan ringkasan jumlah pendaftar, status seleksi,
                    pembayaran, daftar ulang, serta sebaran program studi.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        Periode: PMB 2026
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Data Sementara
                    </span>
                </div>
            </div>

            <div class="rounded-3xl bg-white/10 p-6">
                <p class="text-sm font-bold text-blue-100">Total Pendaftar</p>
                <p class="mt-3 text-5xl font-black">128</p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full w-[72%] rounded-full bg-polmind-yellow"></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Target sementara: 180 pendaftar. Progress saat ini sekitar 72%.
                </p>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Pendaftar', 'value' => '128', 'desc' => 'Semua camaba terdaftar', 'class' => 'bg-blue-100 text-polmind-blue'],
            ['label' => 'Lolos Seleksi', 'value' => '28', 'desc' => 'Dinyatakan diterima', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Daftar Ulang', 'value' => '12', 'desc' => 'Sudah valid DU', 'class' => 'bg-purple-100 text-purple-700'],
            ['label' => 'Belum Lengkap', 'value' => '24', 'desc' => 'Perlu follow up', 'class' => 'bg-yellow-100 text-yellow-700'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        PMB
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filter Report --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Filter Laporan
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Pilih periode, gelombang, program studi, dan kelas untuk menampilkan laporan tertentu.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button"
                        onclick="Swal.fire({
                            title: 'Export Excel',
                            text: 'Export laporan Excel akan dihubungkan setelah database siap.',
                            icon: 'info',
                            confirmButtonColor: '#003B82'
                        })"
                        class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Export Excel
                </button>

                <button type="button"
                        onclick="Swal.fire({
                            title: 'Export PDF',
                            text: 'Export laporan PDF akan dibuat pada tahap backend laporan.',
                            icon: 'info',
                            confirmButtonColor: '#003B82'
                        })"
                        class="rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                    Export PDF
                </button>
            </div>
        </div>

        <form action="#" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="text-sm font-bold text-slate-700">Tahun PMB</label>
                <select name="year"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option>2026</option>
                    <option>2025</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Gelombang</label>
                <select name="wave"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Gelombang</option>
                    <option>Gelombang 1</option>
                    <option>Gelombang 2</option>
                    <option>Gelombang 3</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    <option>TRPL</option>
                    <option>Bisnis Digital</option>
                    <option>TRM</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Kelas</label>
                <select name="class_type"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Kelas</option>
                    <option>Reguler A</option>
                    <option>Reguler B</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    <option>Registrasi Awal</option>
                    <option>Biodata Lengkap</option>
                    <option>Diterima</option>
                    <option>Daftar Ulang Valid</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/reports') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Main Report --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Funnel PMB --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Funnel PMB
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Ringkasan perjalanan camaba dari registrasi awal sampai daftar ulang valid.
                    </p>
                </div>

                @php
                    $funnels = [
                        ['label' => 'Registrasi Awal', 'value' => 128, 'percent' => 100, 'class' => 'bg-polmind-blue'],
                        ['label' => 'Biodata Lengkap', 'value' => 86, 'percent' => 67, 'class' => 'bg-blue-500'],
                        ['label' => 'Berkas Valid', 'value' => 64, 'percent' => 50, 'class' => 'bg-green-500'],
                        ['label' => 'Pembayaran Valid', 'value' => 52, 'percent' => 41, 'class' => 'bg-purple-500'],
                        ['label' => 'Diterima', 'value' => 28, 'percent' => 22, 'class' => 'bg-yellow-500'],
                        ['label' => 'Daftar Ulang Valid', 'value' => 12, 'percent' => 9, 'class' => 'bg-emerald-600'],
                    ];
                @endphp

                <div class="mt-6 space-y-5">
                    @foreach($funnels as $item)
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-slate-900">
                                        {{ $item['label'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $item['value'] }} camaba
                                    </p>
                                </div>

                                <p class="text-sm font-black text-polmind-blue">
                                    {{ $item['percent'] }}%
                                </p>
                            </div>

                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $item['class'] }}" style="width: {{ $item['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Study Program Report --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Sebaran Program Studi
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Rekap jumlah pendaftar dan daftar ulang berdasarkan program studi.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-100 px-4 py-2 text-xs font-black text-polmind-blue">
                        3 Prodi
                    </span>
                </div>

                @php
                    $programs = [
                        [
                            'name' => 'D4 Teknologi Rekayasa Perangkat Lunak',
                            'short' => 'TRPL',
                            'registrants' => 46,
                            'accepted' => 12,
                            'reregistered' => 5,
                            'target' => 40,
                        ],
                        [
                            'name' => 'D4 Teknologi Rekayasa Manufaktur',
                            'short' => 'TRM',
                            'registrants' => 52,
                            'accepted' => 8,
                            'reregistered' => 4,
                            'target' => 40,
                        ],
                        [
                            'name' => 'D4 Bisnis Digital',
                            'short' => 'BD',
                            'registrants' => 30,
                            'accepted' => 8,
                            'reregistered' => 3,
                            'target' => 40,
                        ],
                    ];
                @endphp

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Program Studi</th>
                                <th class="px-5 py-4">Pendaftar</th>
                                <th class="px-5 py-4">Diterima</th>
                                <th class="px-5 py-4">Daftar Ulang</th>
                                <th class="px-5 py-4">Target</th>
                                <th class="px-5 py-4">Progress</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($programs as $program)
                                @php
                                    $progress = round(($program['reregistered'] / $program['target']) * 100);
                                @endphp

                                <tr>
                                    <td class="px-5 py-5">
                                        <p class="font-black text-slate-900">{{ $program['short'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $program['name'] }}</p>
                                    </td>

                                    <td class="px-5 py-5 font-bold text-slate-900">
                                        {{ $program['registrants'] }}
                                    </td>

                                    <td class="px-5 py-5 font-bold text-green-700">
                                        {{ $program['accepted'] }}
                                    </td>

                                    <td class="px-5 py-5 font-bold text-polmind-blue">
                                        {{ $program['reregistered'] }}
                                    </td>

                                    <td class="px-5 py-5 font-bold text-slate-700">
                                        {{ $program['target'] }}
                                    </td>

                                    <td class="px-5 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 w-28 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $progress }}%"></div>
                                            </div>
                                            <span class="text-xs font-black text-polmind-blue">
                                                {{ $progress }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Registration Source --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Sumber Informasi Pendaftar
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data sumber informasi membantu evaluasi efektivitas promosi PMB.
                    </p>
                </div>

                @php
                    $sources = [
                        ['label' => 'Instagram / TikTok', 'value' => 38, 'percent' => 30],
                        ['label' => 'Roadshow Sekolah', 'value' => 34, 'percent' => 27],
                        ['label' => 'Teman / Keluarga', 'value' => 24, 'percent' => 19],
                        ['label' => 'Website PMB', 'value' => 18, 'percent' => 14],
                        ['label' => 'Perusahaan / Industri', 'value' => 14, 'percent' => 10],
                    ];
                @endphp

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach($sources as $source)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-900">{{ $source['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $source['value'] }} pendaftar</p>
                                </div>

                                <p class="text-lg font-black text-polmind-blue">
                                    {{ $source['percent'] }}%
                                </p>
                            </div>

                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $source['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Side Report --}}
        <aside class="space-y-6">

            {{-- Class Distribution --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Sebaran Kelas
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Perbandingan peminat Reguler A dan Reguler B.
                </p>

                <div class="mt-6 space-y-5">
                    @foreach([
                        ['label' => 'Reguler A', 'value' => 79, 'percent' => 74],
                        ['label' => 'Reguler B', 'value' => 27, 'percent' => 26],
                    ] as $class)
                        <div>
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-bold text-slate-900">{{ $class['label'] }}</p>
                                <p class="text-sm font-black text-polmind-blue">{{ $class['value'] }}</p>
                            </div>

                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-polmind-blue" style="width: {{ $class['percent'] }}%"></div>
                            </div>

                            <p class="mt-1 text-xs text-slate-500">{{ $class['percent'] }}% dari total daftar ulang sementara</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Payment Report --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Status Pembayaran
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Ringkasan pembayaran pendaftaran dan daftar ulang.
                </p>

                <div class="mt-6 space-y-4">
                    @foreach([
                        ['label' => 'Pembayaran Pendaftaran Valid', 'value' => '52', 'class' => 'bg-green-100 text-green-700'],
                        ['label' => 'Menunggu Verifikasi', 'value' => '18', 'class' => 'bg-yellow-100 text-yellow-700'],
                        ['label' => 'Ditolak / Revisi', 'value' => '6', 'class' => 'bg-red-100 text-red-700'],
                        ['label' => 'Belum Bayar', 'value' => '52', 'class' => 'bg-slate-100 text-slate-600'],
                    ] as $item)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-700">{{ $item['label'] }}</p>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $item['class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Executive Notes --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Catatan Laporan
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Data masih bersifat sementara.</li>
                    <li>• Angka final mengikuti proses verifikasi admin.</li>
                    <li>• Daftar ulang valid akan menjadi dasar sinkron ke SIAKAD.</li>
                    <li>• Laporan pimpinan dapat diexport setelah modul backend aktif.</li>
                </ul>
            </div>

            {{-- Quick Actions --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Aksi Cepat
                </h2>

                <div class="mt-5 space-y-3">
                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Generate Laporan Pimpinan',
                                text: 'Fitur generate laporan pimpinan akan disiapkan pada tahap backend laporan.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Generate Laporan
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Rekap Prodi',
                                text: 'Rekap sebaran prodi akan diexport setelah database siap.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Rekap Prodi
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Export Siap Sinkron',
                                text: 'Data siap sinkron SIAKAD akan tersedia setelah modul integrasi aktif.',
                                icon: 'info',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Export Siap Sinkron
                    </button>
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection