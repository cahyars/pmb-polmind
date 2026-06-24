@extends('layouts.camaba')

@section('title', 'Tagihan & Pembayaran')
@section('page_title', 'Tagihan & Pembayaran')
@section('page_subtitle', 'Lihat rincian tagihan dan unggah bukti pembayaran pendaftaran.')

@section('content')
<div class="space-y-8">

    {{-- Payment Summary --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-xl shadow-blue-900/20 lg:col-span-2">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-blue-100">
                        Status Pembayaran
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Menunggu Pembayaran
                    </h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-blue-100">
                        Silakan lakukan pembayaran sesuai nominal tagihan, kemudian unggah bukti transfer
                        agar dapat diverifikasi oleh admin/keuangan PMB.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 p-5 text-center">
                    <p class="text-sm font-bold text-blue-100">Total Tagihan</p>
                    <p class="mt-2 text-3xl font-black">Rp350.000</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-2xl">
                ⏳
            </div>
            <h3 class="mt-5 text-lg font-black text-yellow-800">
                Verifikasi Manual
            </h3>
            <p class="mt-2 text-sm leading-6 text-yellow-800">
                Pembayaran akan diverifikasi oleh admin setelah bukti transfer diunggah.
                Pastikan nominal dan rekening tujuan sudah benar.
            </p>
        </div>
    </div>

    {{-- Invoice and Bank Info --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Invoice --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-start">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                        Invoice Pendaftaran
                    </p>
                    <h2 class="mt-2 text-2xl font-black text-polmind-blue">
                        INV/PMB/2024/000982
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        No. Pendaftaran: <span class="font-bold text-slate-900">PMB20240982</span>
                    </p>
                </div>

                <span class="inline-flex rounded-full bg-yellow-100 px-4 py-2 text-xs font-black text-yellow-700">
                    Belum Bayar
                </span>
            </div>

            <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <p class="font-bold text-slate-900">Biaya Pendaftaran</p>
                        <p class="mt-1 text-sm text-slate-500">Tagihan awal proses PMB</p>
                    </div>
                    <p class="text-lg font-black text-polmind-blue">Rp350.000</p>
                </div>

                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <p class="font-bold text-slate-900">Biaya Admin</p>
                        <p class="mt-1 text-sm text-slate-500">Saat ini tidak dikenakan biaya admin</p>
                    </div>
                    <p class="text-lg font-black text-polmind-blue">Rp0</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-polmind-blue p-5 text-white">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-bold text-blue-100">Total yang harus dibayar</p>
                        <p class="mt-1 text-3xl font-black">Rp350.000</p>
                    </div>

                    <button type="button"
                            onclick="Swal.fire({
                                title: 'Invoice Disalin',
                                text: 'Nomor invoice berhasil disalin.',
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
                Rekening Pembayaran
            </h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Transfer pembayaran ke rekening resmi berikut.
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

            <div class="mt-5 rounded-2xl border border-yellow-200 bg-yellow-50 p-4">
                <p class="text-xs font-black uppercase tracking-wide text-yellow-800">
                    Catatan
                </p>
                <p class="mt-2 text-sm leading-6 text-yellow-800">
                    Mohon transfer sesuai nominal tagihan agar proses verifikasi lebih cepat.
                </p>
            </div>
        </div>
    </div>

    {{-- Upload Payment Proof --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Upload Bukti Pembayaran
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Setelah transfer, unggah bukti pembayaran untuk diverifikasi oleh admin/keuangan.
                </p>
            </div>

            <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600">
                Format: JPG, PNG, PDF
            </span>
        </div>

        <form action="#" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-slate-700">Tanggal Transfer</label>
                    <input type="date" name="transfer_date"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nama Pengirim</label>
                    <input type="text" name="sender_name" placeholder="Nama pemilik rekening pengirim"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Bank Pengirim</label>
                    <input type="text" name="sender_bank" placeholder="Contoh: BCA, BRI, Mandiri"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Nominal Transfer</label>
                    <input type="text" name="amount" placeholder="Contoh: 350000"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-slate-700">Bukti Transfer</label>
                    <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-polmind-blue hover:bg-blue-50">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">
                            ⬆️
                        </div>
                        <p class="mt-4 text-sm font-black text-polmind-blue">
                            Klik untuk upload bukti pembayaran
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            PDF, JPG, atau PNG. Maksimal 2MB.
                        </p>
                        <input type="file" name="payment_proof" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
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
                            title: 'Kirim Bukti Pembayaran?',
                            text: 'Pastikan data transfer dan file bukti pembayaran sudah benar.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Kirim',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#003B82'
                        })"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Kirim Bukti Pembayaran
                </button>
            </div>
        </form>
    </div>

    {{-- Payment History --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">
                    Riwayat Pembayaran
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Daftar pembayaran yang sudah pernah diajukan.
                </p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Jenis Tagihan</th>
                        <th class="px-4 py-3">Nominal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <tr>
                        <td class="px-4 py-4">-</td>
                        <td class="px-4 py-4">Biaya Pendaftaran</td>
                        <td class="px-4 py-4 font-bold text-polmind-blue">Rp350.000</td>
                        <td class="px-4 py-4">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                Belum Upload
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-500">Belum ada pembayaran</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Next Step --}}
    <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h3 class="text-lg font-black text-polmind-blue">
                    Langkah Berikutnya
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Setelah pembayaran terverifikasi, Anda dapat memantau hasil seleksi melalui halaman status seleksi.
                </p>
            </div>

            <a href="{{ url('/camaba/status-seleksi') }}"
               class="inline-flex items-center justify-center rounded-xl border border-polmind-blue bg-white px-5 py-3 text-sm font-bold text-polmind-blue transition hover:bg-blue-50">
                Cek Status Seleksi →
            </a>
        </div>
    </div>

</div>
@endsection