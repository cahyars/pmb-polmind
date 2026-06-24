@extends('layouts.camaba')

@section('title', 'Pengumuman & Daftar Ulang')
@section('page_title', 'Pengumuman Seleksi')
@section('page_subtitle', 'Lihat hasil seleksi dan lakukan proses daftar ulang.')

@section('content')
<div class="space-y-8">

    {{-- Accepted Hero --}}
    <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-green-600 to-green-800 shadow-xl shadow-green-900/20">
        <div class="grid gap-8 p-6 text-white lg:grid-cols-3 lg:p-8">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-wide text-green-100">
                    Hasil Seleksi PMB
                </p>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    Selamat! Anda Dinyatakan Diterima
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-green-100">
                    Berdasarkan hasil seleksi Penerimaan Mahasiswa Baru Politeknik Mitra Industri,
                    Anda dinyatakan diterima sebagai calon mahasiswa baru. Silakan lanjutkan proses daftar ulang
                    sesuai batas waktu yang telah ditentukan.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black text-green-100">
                        No. Pendaftaran: PMB20240982
                    </span>
                    <span class="rounded-full bg-polmind-yellow px-4 py-2 text-xs font-black text-polmind-blue-dark">
                        Diterima
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-center">
                <div class="rounded-3xl bg-white/10 p-8 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 text-5xl">
                        🎉
                    </div>
                    <p class="mt-5 text-sm font-bold text-green-100">
                        Batas Daftar Ulang
                    </p>
                    <p class="mt-2 text-2xl font-black">
                        30 Juni 2026
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Applicant Result Summary --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-xl font-black text-polmind-blue">
                    Ringkasan Penerimaan
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Berikut informasi hasil seleksi dan program studi penerimaan Anda.
                </p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                        Nama Camaba
                    </p>
                    <p class="mt-2 text-sm font-bold text-slate-900">
                        Ahmad Fauzi
                    </p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                        Nomor Pendaftaran
                    </p>
                    <p class="mt-2 text-sm font-bold text-slate-900">
                        PMB20240982
                    </p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                        Program Studi Diterima
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
                        Kelas
                    </p>
                    <p class="mt-2 text-sm font-bold text-slate-900">
                        Reguler Pagi
                    </p>
                </div>

                <div class="rounded-2xl border border-green-100 bg-green-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-green-700">
                        Status
                    </p>
                    <p class="mt-2 text-sm font-bold text-green-800">
                        Diterima - Menunggu Daftar Ulang
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <button type="button"
                        onclick="Swal.fire({
                            title: 'Surat Pengumuman',
                            text: 'Fitur unduh surat pengumuman akan dihubungkan setelah backend siap.',
                            icon: 'info',
                            confirmButtonColor: '#003B82'
                        })"
                        class="rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                    Unduh Surat Pengumuman
                </button>

                <button type="button"
                        onclick="Swal.fire({
                            title: 'Surat Pernyataan',
                            text: 'Template surat pernyataan daftar ulang akan disiapkan pada tahap berikutnya.',
                            icon: 'info',
                            confirmButtonColor: '#003B82'
                        })"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Unduh Surat Pernyataan
                </button>
            </div>
        </div>

        {{-- Important Deadline --}}
        <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                📌
            </div>

            <h3 class="mt-5 text-lg font-black text-yellow-800">
                Catatan Penting
            </h3>

            <p class="mt-2 text-sm leading-6 text-yellow-800">
                Daftar ulang wajib dilakukan sebelum batas waktu. Jika tidak melakukan daftar ulang,
                status penerimaan dapat dibatalkan atau dialihkan ke calon mahasiswa lain.
            </p>

            <div class="mt-5 rounded-2xl bg-white/60 p-4">
                <p class="text-xs font-black uppercase tracking-wide text-yellow-800">
                    Batas Waktu
                </p>
                <p class="mt-1 text-xl font-black text-yellow-900">
                    30 Juni 2026
                </p>
            </div>
        </div>
    </div>

    {{-- Re-registration Steps --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">
                Langkah Daftar Ulang
            </h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Ikuti langkah berikut untuk menyelesaikan proses daftar ulang.
            </p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-4">
            @foreach([
                ['title' => 'Cek Tagihan', 'desc' => 'Lihat rincian biaya daftar ulang.', 'icon' => '💳'],
                ['title' => 'Transfer', 'desc' => 'Lakukan pembayaran ke rekening resmi.', 'icon' => '🏦'],
                ['title' => 'Upload Bukti', 'desc' => 'Unggah bukti pembayaran daftar ulang.', 'icon' => '📤'],
                ['title' => 'Verifikasi', 'desc' => 'Tunggu validasi dari admin/keuangan.', 'icon' => '✅'],
            ] as $index => $step)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                        {{ $step['icon'] }}
                    </div>
                    <p class="mt-4 text-xs font-black text-polmind-blue">
                        {{ $index + 1 }}. {{ $step['title'] }}
                    </p>
                    <p class="mt-2 text-xs leading-5 text-slate-600">
                        {{ $step['desc'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Re-registration Billing --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Bill Detail --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-start">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                        Tagihan Daftar Ulang
                    </p>
                    <h2 class="mt-2 text-2xl font-black text-polmind-blue">
                        INV/DU/2026/000982
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Status: <span class="font-bold text-yellow-700">Menunggu Pembayaran</span>
                    </p>
                </div>

                <span class="inline-flex rounded-full bg-yellow-100 px-4 py-2 text-xs font-black text-yellow-700">
                    Belum Bayar
                </span>
            </div>

            <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <p class="font-bold text-slate-900">Daftar Ulang</p>
                        <p class="mt-1 text-sm text-slate-500">Biaya awal konfirmasi mahasiswa baru</p>
                    </div>
                    <p class="text-lg font-black text-polmind-blue">Rp2.500.000</p>
                </div>

                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <p class="font-bold text-slate-900">Angsuran SPI</p>
                        <p class="mt-1 text-sm text-slate-500">Pembayaran tahap awal SPI</p>
                    </div>
                    <p class="text-lg font-black text-polmind-blue">Rp5.000.000</p>
                </div>

                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <p class="font-bold text-slate-900">SPP Semester 1</p>
                        <p class="mt-1 text-sm text-slate-500">Biaya perkuliahan semester pertama</p>
                    </div>
                    <p class="text-lg font-black text-polmind-blue">Rp4.000.000</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-polmind-blue p-5 text-white">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-bold text-blue-100">Total Daftar Ulang</p>
                        <p class="mt-1 text-3xl font-black">Rp11.500.000</p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Invoice Disalin',
                                text: 'Nomor invoice daftar ulang berhasil disalin.',
                                icon: 'success',
                                confirmButtonColor: '#003B82'
                            })"
                            class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark transition hover:brightness-95">
                        Salin Invoice
                    </button>
                </div>
            </div>
        </div>

        {{-- Bank Info --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-polmind-blue">
                Rekening Daftar Ulang
            </h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Transfer pembayaran daftar ulang ke rekening resmi berikut.
            </p>

            <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-polmind-blue">
                    Bank Tujuan
                </p>
                <h3 class="mt-2 text-xl font-black text-slate-900">
                    Bank Mandiri
                </h3>

                <div class="mt-5 space-y-4 text-sm">
                    <div>
                        <p class="font-bold text-slate-700">Nomor Rekening</p>
                        <div class="mt-1 flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3">
                            <span class="font-black text-polmind-blue">1234567890</span>
                            <button type="button"
                                    onclick="Swal.fire({
                                        title: 'Nomor Rekening Disalin',
                                        icon: 'success',
                                        confirmButtonColor: '#003B82'
                                    })"
                                    class="text-xs font-black text-polmind-blue hover:underline">
                                Salin
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="font-bold text-slate-700">Atas Nama</p>
                        <p class="mt-1 text-slate-600">Politeknik Mitra Industri</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Upload Proof --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Upload Bukti Pembayaran Daftar Ulang
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Unggah bukti transfer agar admin dapat melakukan validasi daftar ulang.
                </p>
            </div>

            <span class="rounded-full bg-yellow-100 px-4 py-2 text-xs font-black text-yellow-700">
                Menunggu Upload
            </span>
        </div>

        <form action="#" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">Tanggal Transfer</label>
                    <input type="date" name="reregistration_transfer_date"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nama Pengirim</label>
                    <input type="text" name="reregistration_sender_name" placeholder="Nama pemilik rekening pengirim"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Bank Pengirim</label>
                    <input type="text" name="reregistration_sender_bank" placeholder="Contoh: BCA, BRI, Mandiri"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nominal Transfer</label>
                    <input type="text" name="reregistration_amount" placeholder="Contoh: 11500000"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-slate-700">Bukti Transfer Daftar Ulang</label>
                    <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-polmind-blue hover:bg-blue-50">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                            ⬆️
                        </div>
                        <p class="mt-4 text-sm font-black text-polmind-blue">
                            Klik untuk upload bukti daftar ulang
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            PDF, JPG, atau PNG. Maksimal 2MB.
                        </p>
                        <input type="file" name="reregistration_payment_proof" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                    </label>
                </div>
            </div>

            <div class="flex flex-col justify-end gap-3 border-t border-slate-200 pt-5 sm:flex-row">
                <button type="button"
                        class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Simpan Draft
                </button>

                <button type="button"
                        onclick="Swal.fire({
                            title: 'Kirim Bukti Daftar Ulang?',
                            text: 'Pastikan data transfer dan bukti pembayaran sudah benar.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Kirim',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#003B82'
                        })"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Kirim Bukti Daftar Ulang
                </button>
            </div>
        </form>
    </div>

    {{-- Verification Status --}}
    <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div>
                <h3 class="text-lg font-black text-polmind-blue">
                    Status Daftar Ulang
                </h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Setelah pembayaran daftar ulang divalidasi, status Anda akan berubah menjadi
                    <span class="font-bold text-polmind-blue">Daftar Ulang Valid</span> dan data akan siap disinkronkan ke sistem akademik pada tahap integrasi.
                </p>
            </div>

            <span class="rounded-full bg-yellow-100 px-4 py-2 text-xs font-black text-yellow-700">
                Menunggu Pembayaran
            </span>
        </div>
    </div>

</div>
@endsection