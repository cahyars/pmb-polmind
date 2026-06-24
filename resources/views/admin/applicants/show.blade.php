@extends('layouts.admin')

@section('title', 'Detail Camaba')
@section('page_title', 'Detail Camaba')
@section('page_subtitle', 'Lihat detail data calon mahasiswa dan lakukan proses verifikasi.')

@section('content')
<div class="space-y-8">

    {{-- Back Button --}}
    <div>
        <a href="{{ url('/admin/applicants') }}"
           class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
            ← Kembali ke Data Camaba
        </a>
    </div>

    {{-- Applicant Header --}}
    <div class="overflow-hidden rounded-3xl bg-polmind-blue shadow-xl shadow-blue-900/20">
        <div class="grid gap-6 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 text-2xl font-black">
                        AF
                    </div>

                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                            No. Pendaftaran
                        </p>
                        <h2 class="mt-1 text-3xl font-black">
                            {{ $registration_number ?? 'PMB20260001' }}
                        </h2>
                        <p class="mt-2 text-lg font-bold text-white">
                            Ahmad Fauzi
                        </p>
                    </div>
                </div>

                <p class="mt-6 max-w-2xl text-sm leading-6 text-blue-100">
                    Calon mahasiswa Program Studi D4 Teknologi Rekayasa Perangkat Lunak.
                    Data berikut masih bersifat tampilan statis dan nanti akan dihubungkan ke database PMB.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        Gelombang 2
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Biodata Lengkap
                    </span>
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100">
                        TRPL
                    </span>
                </div>
            </div>

            <div class="rounded-3xl bg-white/10 p-6">
                <p class="text-sm font-bold text-blue-100">Progress Pendaftaran</p>
                <p class="mt-3 text-4xl font-black">65%</p>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full w-[65%] rounded-full bg-polmind-yellow"></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-blue-100">
                    Status terakhir: menunggu verifikasi berkas dan pembayaran.
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Status --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Biodata', 'value' => 'Lengkap', 'class' => 'bg-green-100 text-green-700'],
            ['label' => 'Berkas', 'value' => 'Menunggu', 'class' => 'bg-yellow-100 text-yellow-700'],
            ['label' => 'Pembayaran', 'value' => 'Belum Valid', 'class' => 'bg-red-100 text-red-700'],
            ['label' => 'Seleksi', 'value' => 'Belum Diproses', 'class' => 'bg-slate-100 text-slate-600'],
        ] as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $item['label'] }}</p>
                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $item['class'] }}">
                    {{ $item['value'] }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        {{-- Main Detail --}}
        <div class="space-y-8 xl:col-span-2">

            {{-- Biodata --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Biodata Pribadi
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Data identitas utama calon mahasiswa.
                        </p>
                    </div>

                    <span class="rounded-full bg-green-100 px-4 py-2 text-xs font-black text-green-700">
                        Lengkap
                    </span>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach([
                        ['label' => 'Nama Lengkap', 'value' => 'Ahmad Fauzi'],
                        ['label' => 'NIK', 'value' => '3216xxxxxxxx0001'],
                        ['label' => 'NISN', 'value' => '006xxxxxxx'],
                        ['label' => 'Tempat, Tanggal Lahir', 'value' => 'Subang, 12 Mei 2007'],
                        ['label' => 'Jenis Kelamin', 'value' => 'Laki-laki'],
                        ['label' => 'Agama', 'value' => 'Islam'],
                        ['label' => 'Email', 'value' => 'ahmad.fauzi@email.com'],
                        ['label' => 'Nomor WhatsApp', 'value' => '081234567890'],
                    ] as $data)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                {{ $data['label'] }}
                            </p>
                            <p class="mt-2 text-sm font-bold text-slate-900">
                                {{ $data['value'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Address --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Alamat Domisili
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data wilayah disimpan dengan kode dan nama wilayah.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach([
                        ['label' => 'Provinsi', 'value' => 'Jawa Barat'],
                        ['label' => 'Kabupaten/Kota', 'value' => 'Kabupaten Bekasi'],
                        ['label' => 'Kecamatan', 'value' => 'Cikarang Barat'],
                        ['label' => 'Desa/Kelurahan', 'value' => 'Gandasari'],
                        ['label' => 'RT/RW', 'value' => '001 / 002'],
                        ['label' => 'Kode Pos', 'value' => '17530'],
                    ] as $data)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                {{ $data['label'] }}
                            </p>
                            <p class="mt-2 text-sm font-bold text-slate-900">
                                {{ $data['value'] }}
                            </p>
                        </div>
                    @endforeach

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 md:col-span-2">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                            Alamat Lengkap
                        </p>
                        <p class="mt-2 text-sm font-bold leading-6 text-slate-900">
                            Jl. Industri Raya Blok A No. 12, dekat kawasan MM2100, Cikarang Barat, Kabupaten Bekasi.
                        </p>
                    </div>
                </div>
            </div>

            {{-- School --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Data Sekolah Asal
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data sekolah dipilih dari master sekolah berbasis NPSN.
                    </p>
                </div>

                <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                                Sekolah Terpilih
                            </p>
                            <h3 class="mt-2 text-xl font-black text-slate-900">
                                SMKN 1 Subang
                            </h3>
                            <p class="mt-1 text-sm text-slate-600">
                                NPSN: 20233677
                            </p>
                        </div>

                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-polmind-blue">
                            SMK Negeri
                        </span>
                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Jenjang</p>
                            <p class="mt-2 text-sm font-bold text-slate-900">SMK</p>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Jurusan Asal</p>
                            <p class="mt-2 text-sm font-bold text-slate-900">Rekayasa Perangkat Lunak</p>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Tahun Lulus</p>
                            <p class="mt-2 text-sm font-bold text-slate-900">2026</p>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Wilayah</p>
                            <p class="mt-2 text-sm font-bold text-slate-900">Subang, Jawa Barat</p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Alamat Sekolah</p>
                            <p class="mt-2 text-sm font-bold text-slate-900">
                                Jl. Arief Rahman Hakim No.35, Subang, Jawa Barat
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Parent --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-xl font-black text-polmind-blue">
                        Data Orang Tua / Wali
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data kontak keluarga untuk kebutuhan administrasi.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach([
                        ['label' => 'Nama Ayah', 'value' => 'Dadang Hidayat'],
                        ['label' => 'Pekerjaan Ayah', 'value' => 'Karyawan Swasta'],
                        ['label' => 'Nama Ibu', 'value' => 'Siti Nurjanah'],
                        ['label' => 'Pekerjaan Ibu', 'value' => 'Ibu Rumah Tangga'],
                        ['label' => 'Penghasilan Orang Tua', 'value' => 'Rp 2.000.000 - Rp 5.000.000'],
                        ['label' => 'Nomor HP Orang Tua/Wali', 'value' => '081298765432'],
                    ] as $data)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                {{ $data['label'] }}
                            </p>
                            <p class="mt-2 text-sm font-bold text-slate-900">
                                {{ $data['value'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Documents --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Berkas Pendaftaran
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Daftar dokumen yang telah diunggah oleh camaba.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Verifikasi Semua Berkas?',
                                text: 'Pastikan seluruh dokumen sudah diperiksa.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Verifikasi',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Verifikasi Berkas
                    </button>
                </div>

                @php
                    $documents = [
                        ['name' => 'Pas Foto', 'file' => 'pas-foto-ahmad.jpg', 'status' => 'Diterima', 'class' => 'bg-green-100 text-green-700'],
                        ['name' => 'KTP / Kartu Pelajar', 'file' => 'ktp-ahmad.pdf', 'status' => 'Menunggu', 'class' => 'bg-yellow-100 text-yellow-700'],
                        ['name' => 'Kartu Keluarga', 'file' => 'kk-ahmad.jpg', 'status' => 'Ditolak', 'class' => 'bg-red-100 text-red-700'],
                        ['name' => 'Ijazah / SKL', 'file' => '-', 'status' => 'Belum Upload', 'class' => 'bg-slate-100 text-slate-600'],
                    ];
                @endphp

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Dokumen</th>
                                <th class="px-4 py-3">File</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($documents as $document)
                                <tr>
                                    <td class="px-4 py-4 font-bold text-slate-900">{{ $document['name'] }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $document['file'] }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $document['class'] }}">
                                            {{ $document['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            Lihat
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payment --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-polmind-blue">
                            Pembayaran
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Informasi tagihan dan bukti pembayaran pendaftaran.
                        </p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Validasi Pembayaran?',
                                text: 'Pembayaran akan ditandai valid.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Validasi',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-700">
                        Validasi Pembayaran
                    </button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Invoice</p>
                        <p class="mt-2 text-sm font-bold text-polmind-blue">INV/PMB/2026/0001</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Nominal</p>
                        <p class="mt-2 text-sm font-bold text-slate-900">Rp350.000</p>
                    </div>

                    <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-yellow-700">Status</p>
                        <p class="mt-2 text-sm font-bold text-yellow-800">Menunggu Verifikasi</p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Bukti Pembayaran</p>
                    <div class="mt-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <p class="text-sm font-bold text-slate-900">bukti-transfer-ahmad.jpg</p>
                        <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Lihat Bukti
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Actions --}}
        <aside class="space-y-6">

            {{-- Action Panel --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Aksi Admin
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Gunakan panel ini untuk mengubah status camaba.
                </p>

                <div class="mt-6 space-y-3">
                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Set Camaba Diterima?',
                                text: 'Status seleksi akan berubah menjadi diterima.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Diterima',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#16A34A'
                            })"
                            class="w-full rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-700">
                        Set Diterima
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Set Camaba Cadangan?',
                                text: 'Status seleksi akan berubah menjadi cadangan.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Cadangan',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#F59E0B'
                            })"
                            class="w-full rounded-xl bg-yellow-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-yellow-600">
                        Set Cadangan
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Set Camaba Ditolak?',
                                text: 'Status seleksi akan berubah menjadi tidak diterima.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Tolak',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#DC2626'
                            })"
                            class="w-full rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-700">
                        Set Ditolak
                    </button>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Tandai Daftar Ulang Valid?',
                                text: 'Camaba akan ditandai siap sinkron ke SIAKAD.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Valid',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#003B82'
                            })"
                            class="w-full rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                        Daftar Ulang Valid
                    </button>
                </div>
            </div>

            {{-- Follow Up --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Catatan Follow Up
                </h2>

                <form action="#" method="POST" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label class="text-sm font-bold text-slate-700">Status Follow Up</label>
                        <select name="follow_up_status"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                            <option>Belum Dihubungi</option>
                            <option>Sudah Dihubungi</option>
                            <option>Tertarik</option>
                            <option>Menunggu Orang Tua</option>
                            <option>Akan Daftar Ulang</option>
                            <option>Tidak Jadi</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">Catatan</label>
                        <textarea name="note"
                                  rows="4"
                                  placeholder="Tulis hasil follow up..."
                                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"></textarea>
                    </div>

                    <button type="button"
                            class="w-full rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                        Simpan Catatan
                    </button>
                </form>
            </div>

            {{-- Status Timeline --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-polmind-blue">
                    Timeline Status
                </h2>

                <div class="mt-6 space-y-5">
                    @foreach([
                        ['title' => 'Registrasi Akun', 'date' => '20 Juni 2026', 'status' => 'Selesai'],
                        ['title' => 'Biodata Lengkap', 'date' => '21 Juni 2026', 'status' => 'Selesai'],
                        ['title' => 'Upload Berkas', 'date' => '22 Juni 2026', 'status' => 'Menunggu'],
                        ['title' => 'Pembayaran', 'date' => '23 Juni 2026', 'status' => 'Menunggu'],
                    ] as $timeline)
                        <div class="flex gap-3">
                            <div class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">
                                ✓
                            </div>

                            <div>
                                <p class="text-sm font-black text-slate-900">
                                    {{ $timeline['title'] }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $timeline['date'] }} · {{ $timeline['status'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </aside>
    </div>

</div>
@endsection