@extends('layouts.admin')

@section('title', 'Follow Up Camaba')
@section('page_title', 'Follow Up Camaba')
@section('page_subtitle', 'Kelola komunikasi dan tindak lanjut calon mahasiswa baru.')

@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Belum Dihubungi', 'value' => '24', 'desc' => 'Perlu follow up awal', 'class' => 'bg-red-100 text-red-700'],
            ['label' => 'Sudah Dihubungi', 'value' => '58', 'desc' => 'Sudah ada komunikasi', 'class' => 'bg-blue-100 text-polmind-blue'],
            ['label' => 'Tertarik', 'value' => '36', 'desc' => 'Potensi daftar ulang', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Tidak Aktif', 'value' => '10', 'desc' => 'Perlu evaluasi ulang', 'class' => 'bg-slate-100 text-slate-700'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-4xl font-black text-polmind-blue">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $card['class'] }}">
                        CRM
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Filter Follow Up
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cari camaba berdasarkan nama, nomor pendaftaran, status follow up, prodi, atau gelombang.
                </p>
            </div>

            <button type="button"
                    onclick="Swal.fire({
                        title: 'Template WhatsApp',
                        text: 'Template pesan WhatsApp akan dibuat sebagai fitur CRM manual.',
                        icon: 'info',
                        confirmButtonColor: '#003B82'
                    })"
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Template WA
            </button>
        </div>

        <form action="#" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       placeholder="Nama / no. pendaftaran / nomor WA"
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status Follow Up</label>
                <select name="follow_up_status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    <option>Belum Dihubungi</option>
                    <option>Sudah Dihubungi</option>
                    <option>Tertarik</option>
                    <option>Ragu-ragu</option>
                    <option>Menunggu Orang Tua</option>
                    <option>Akan Daftar Ulang</option>
                    <option>Tidak Jadi</option>
                    <option>Tidak Aktif</option>
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
                <label class="text-sm font-bold text-slate-700">Prioritas</label>
                <select name="priority"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prioritas</option>
                    <option>Tinggi</option>
                    <option>Sedang</option>
                    <option>Rendah</option>
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ url('/admin/follow-ups') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Follow Up Table --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">
                        Daftar Follow Up
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Pantau komunikasi dengan camaba dan catat hasil tindak lanjut PMB.
                    </p>
                </div>

                <span class="rounded-full bg-red-100 px-4 py-2 text-xs font-black text-red-700">
                    24 Belum Dihubungi
                </span>
            </div>

            @php
                $followUps = [
                    [
                        'registration' => 'PMB20260001',
                        'name' => 'Ahmad Fauzi',
                        'phone' => '081234567890',
                        'program' => 'TRPL',
                        'wave' => 'Gel. 2',
                        'status' => 'Belum Dihubungi',
                        'status_key' => 'not_contacted',
                        'priority' => 'Tinggi',
                        'last_follow_up' => '-',
                        'next_follow_up' => 'Hari ini',
                        'note' => 'Belum ada catatan follow up.',
                    ],
                    [
                        'registration' => 'PMB20260002',
                        'name' => 'Siti Aminah',
                        'phone' => '081298765431',
                        'program' => 'Bisnis Digital',
                        'wave' => 'Gel. 2',
                        'status' => 'Tertarik',
                        'status_key' => 'interested',
                        'priority' => 'Tinggi',
                        'last_follow_up' => '22 Juni 2026',
                        'next_follow_up' => '24 Juni 2026',
                        'note' => 'Menunggu diskusi dengan orang tua terkait biaya daftar ulang.',
                    ],
                    [
                        'registration' => 'PMB20260003',
                        'name' => 'Budi Santoso',
                        'phone' => '082112345678',
                        'program' => 'TRM',
                        'wave' => 'Gel. 2',
                        'status' => 'Menunggu Orang Tua',
                        'status_key' => 'parent',
                        'priority' => 'Sedang',
                        'last_follow_up' => '21 Juni 2026',
                        'next_follow_up' => '25 Juni 2026',
                        'note' => 'Camaba tertarik, tetapi keputusan ada di orang tua.',
                    ],
                    [
                        'registration' => 'PMB20260004',
                        'name' => 'Dewi Lestari',
                        'phone' => '085712345678',
                        'program' => 'TRPL',
                        'wave' => 'Gel. 1',
                        'status' => 'Akan Daftar Ulang',
                        'status_key' => 'reregister',
                        'priority' => 'Tinggi',
                        'last_follow_up' => '23 Juni 2026',
                        'next_follow_up' => '26 Juni 2026',
                        'note' => 'Sudah konfirmasi akan melakukan pembayaran daftar ulang minggu ini.',
                    ],
                    [
                        'registration' => 'PMB20260005',
                        'name' => 'Rizky Pratama',
                        'phone' => '081377712345',
                        'program' => 'Bisnis Digital',
                        'wave' => 'Gel. 1',
                        'status' => 'Tidak Aktif',
                        'status_key' => 'inactive',
                        'priority' => 'Rendah',
                        'last_follow_up' => '20 Juni 2026',
                        'next_follow_up' => '-',
                        'note' => 'Nomor WA belum merespons setelah 3 kali follow up.',
                    ],
                ];

                $statusClasses = [
                    'not_contacted' => 'bg-red-100 text-red-700',
                    'interested' => 'bg-green-100 text-green-700',
                    'parent' => 'bg-yellow-100 text-yellow-700',
                    'reregister' => 'bg-blue-100 text-polmind-blue',
                    'inactive' => 'bg-slate-100 text-slate-600',
                ];

                $priorityClasses = [
                    'Tinggi' => 'bg-red-50 text-red-700',
                    'Sedang' => 'bg-yellow-50 text-yellow-700',
                    'Rendah' => 'bg-slate-100 text-slate-600',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Camaba</th>
                            <th class="px-6 py-4">Kontak</th>
                            <th class="px-6 py-4">Prodi</th>
                            <th class="px-6 py-4">Status Follow Up</th>
                            <th class="px-6 py-4">Prioritas</th>
                            <th class="px-6 py-4">Follow Up Berikutnya</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($followUps as $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                            {{ collect(explode(' ', $item['name']))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $item['name'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $item['registration'] }} · {{ $item['wave'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-bold text-slate-900">{{ $item['phone'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">WA aktif</p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                        {{ $item['program'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$item['status_key']] }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $priorityClasses[$item['priority']] }}">
                                        {{ $item['priority'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-bold text-slate-900">{{ $item['next_follow_up'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Terakhir: {{ $item['last_follow_up'] }}
                                    </p>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="https://wa.me/62{{ ltrim($item['phone'], '0') }}?text={{ urlencode('Halo ' . $item['name'] . ', kami dari PMB Politeknik Mitra Industri ingin menindaklanjuti proses pendaftaran Anda.') }}"
                                           target="_blank"
                                           class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                            WhatsApp
                                        </a>

                                        <button type="button"
                                                onclick="Swal.fire({
                                                    title: 'Tambah Catatan Follow Up',
                                                    html:
                                                        '<select class=\'swal2-input\'>' +
                                                        '<option>Belum Dihubungi</option>' +
                                                        '<option>Sudah Dihubungi</option>' +
                                                        '<option>Tertarik</option>' +
                                                        '<option>Ragu-ragu</option>' +
                                                        '<option>Menunggu Orang Tua</option>' +
                                                        '<option>Akan Daftar Ulang</option>' +
                                                        '<option>Tidak Jadi</option>' +
                                                        '</select>' +
                                                        '<textarea class=\'swal2-textarea\' placeholder=\'Tulis catatan follow up...\'></textarea>',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Simpan',
                                                    cancelButtonText: 'Batal',
                                                    confirmButtonColor: '#003B82'
                                                })"
                                                class="rounded-xl bg-polmind-blue px-3 py-2 text-xs font-bold text-white transition hover:bg-polmind-blue-dark">
                                            Catat
                                        </button>

                                        <a href="{{ url('/admin/applicants/' . $item['registration']) }}"
                                           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr class="bg-slate-50">
                                <td colspan="7" class="px-6 py-4">
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                        Catatan Terakhir
                                    </p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        {{ $item['note'] }}
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col justify-between gap-4 border-t border-slate-200 p-6 md:flex-row md:items-center">
                <p class="text-sm text-slate-500">
                    Menampilkan <span class="font-bold text-slate-700">1-5</span> dari <span class="font-bold text-slate-700">128</span> data follow up
                </p>

                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-500">
                        Sebelumnya
                    </button>
                    <button class="rounded-xl bg-polmind-blue px-4 py-2 text-sm font-bold text-white">
                        1
                    </button>
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        2
                    </button>
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        {{-- Side Panel --}}
        <aside class="space-y-6">

            {{-- Today Priority --}}
            <div class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-2xl">
                    🔔
                </div>

                <h3 class="mt-5 text-lg font-black text-red-800">
                    Prioritas Hari Ini
                </h3>

                <p class="mt-3 text-sm leading-6 text-red-800">
                    Ada <span class="font-black">24 camaba</span> yang belum dihubungi dan perlu follow up segera,
                    terutama yang sudah diterima tetapi belum daftar ulang.
                </p>

                <button type="button"
                        onclick="Swal.fire({
                            title: 'Reminder Prioritas',
                            text: 'Fitur reminder massal akan disiapkan setelah backend CRM aktif.',
                            icon: 'info',
                            confirmButtonColor: '#003B82'
                        })"
                        class="mt-5 w-full rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-700">
                    Lihat Prioritas
                </button>
            </div>

            {{-- WhatsApp Templates --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Template WhatsApp
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Template manual yang bisa digunakan admin PMB untuk follow up camaba.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach([
                        'Reminder Lengkapi Biodata',
                        'Reminder Upload Berkas',
                        'Reminder Pembayaran',
                        'Pengumuman Diterima',
                        'Reminder Daftar Ulang',
                    ] as $template)
                        <button type="button"
                                onclick="Swal.fire({
                                    title: '{{ $template }}',
                                    text: 'Template pesan akan ditampilkan dan bisa disalin setelah modul template dibuat.',
                                    icon: 'info',
                                    confirmButtonColor: '#003B82'
                                })"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-polmind-blue">
                            {{ $template }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Follow Up Guide --}}
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                    📌
                </div>

                <h3 class="mt-5 text-lg font-black text-yellow-800">
                    Panduan Follow Up
                </h3>

                <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-800">
                    <li>• Prioritaskan camaba diterima yang belum daftar ulang.</li>
                    <li>• Catat hasil komunikasi setiap kali follow up.</li>
                    <li>• Gunakan bahasa yang ramah dan jelas.</li>
                    <li>• Jadwalkan ulang follow up jika camaba belum bisa memastikan.</li>
                    <li>• Tandai tidak aktif jika tidak merespons beberapa kali.</li>
                </ul>
            </div>

        </aside>
    </div>

</div>
@endsection