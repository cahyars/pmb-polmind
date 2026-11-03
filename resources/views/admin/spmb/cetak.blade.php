@extends('layouts.admin')

@section('title', 'Cetak Dokumen Tes')
@section('page_title', 'Cetak Dokumen Tes')
@section('page_subtitle', 'Seleksi · Daftar hadir, daftar kelompok peserta, dan kartu peserta untuk hari H tes.')

@section('content')
@php
    $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-polmind-blue';
    $printButton = 'inline-flex w-full items-center justify-center gap-2 rounded-lg bg-polmind-yellow px-4 py-2.5 text-sm font-bold text-polmind-blue-dark hover:brightness-95';
@endphp

<div class="space-y-6">
    @include('admin.spmb.partials.header')

    @if($selectedWave)
        @if($unnumbered)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                <span class="font-semibold">{{ $unnumbered }} camaba sudah bayar formulir tetapi belum bernomor</span> dan belum masuk dokumen cetak.
                <a href="{{ route('admin.spmb.numbers.index', ['gelombang' => $selectedWave->id]) }}" class="font-semibold text-polmind-blue underline">Beri nomor peserta</a>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Daftar hadir --}}
            <form method="GET" action="{{ route('admin.spmb.prints.attendance') }}" target="_blank" class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <input type="hidden" name="gelombang" value="{{ $selectedWave->id }}">
                <h2 class="text-base font-semibold text-slate-900">Daftar Hadir Peserta</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Kolom No, Nama Lengkap, Asal Sekolah, dan TTD. Pendaftar SMK Mitra Industri dicetak terpisah dengan nomor range mitra.
                </p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2" x-data="{ list: 'umum' }">
                    <div>
                        <label for="attendance-list" class="text-xs font-semibold text-slate-600">Daftar</label>
                        <select id="attendance-list" x-model="list" class="{{ $input }}">
                            <option value="umum">Pendaftar umum ({{ $generalCount }})</option>
                            <option value="mitra">Pendaftar mitra ({{ $partnerCount }})</option>
                            @foreach($groups as $group)
                                <option value="group-{{ $group->id }}">{{ $group->name }} ({{ $group->applicants_count }})</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="jenis" :value="list.startsWith('group-') ? '' : list" :disabled="list.startsWith('group-')">
                        <input type="hidden" name="kelompok" :value="list.replace('group-', '')" :disabled="! list.startsWith('group-')" disabled>
                    </div>
                    <div>
                        <label for="attendance-blank" class="text-xs font-semibold text-slate-600">Baris kosong cadangan</label>
                        <input id="attendance-blank" type="number" name="kosong" min="0" max="60" value="5" class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-auto pt-5">
                    <button type="submit" class="{{ $printButton }}"><x-icon name="printer" class="h-4 w-4" stroke-width="2" /> Cetak Daftar Hadir</button>
                </div>
            </form>

            {{-- Daftar kelompok --}}
            <form method="GET" action="{{ route('admin.spmb.prints.groups') }}" target="_blank" class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <input type="hidden" name="gelombang" value="{{ $selectedWave->id }}">
                <h2 class="text-base font-semibold text-slate-900">Daftar Kelompok Peserta</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Pegangan PIC tes: anggota tiap kelompok dengan kolom per tes. Kelompok mitra dicetak di lembar kedua; tes yang tidak diikuti diarsir abu-abu.
                </p>
                @if($groups->isEmpty())
                    <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Belum ada kelompok. <a href="{{ route('admin.spmb.groups.index', ['gelombang' => $selectedWave->id]) }}" class="font-semibold underline">Buat kelompok</a>
                    </p>
                @endif
                <div class="mt-4">
                    <label for="groups-blank" class="text-xs font-semibold text-slate-600">Baris kosong cadangan (nomor lanjutan)</label>
                    <input id="groups-blank" type="number" name="kosong" min="0" max="60" value="10" class="{{ $input }}">
                </div>
                <div class="mt-auto pt-5">
                    <button type="submit" @disabled($groups->isEmpty()) class="{{ $printButton }} disabled:cursor-not-allowed disabled:opacity-50"><x-icon name="printer" class="h-4 w-4" stroke-width="2" /> Cetak Daftar Kelompok</button>
                </div>
            </form>

            {{-- Kartu peserta --}}
            <form method="GET" action="{{ route('admin.spmb.prints.cards') }}" target="_blank" class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <input type="hidden" name="gelombang" value="{{ $selectedWave->id }}">
                <h2 class="text-base font-semibold text-slate-900">Kartu Peserta Seleksi</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">10 kartu per lembar A4, urut nomor peserta. Bisa ditambah kartu kosong di akhir.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div>
                        <label for="cards-group" class="text-xs font-semibold text-slate-600">Kelompok</label>
                        <select id="cards-group" name="kelompok" class="{{ $input }}">
                            <option value="">Semua</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cards-program" class="text-xs font-semibold text-slate-600">Program studi</label>
                        <select id="cards-program" name="study_program" class="{{ $input }}">
                            <option value="">Semua</option>
                            @foreach($studyPrograms as $program)
                                <option value="{{ $program->id }}">{{ $program->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cards-blank" class="text-xs font-semibold text-slate-600">+ Kartu kosong</label>
                        <input id="cards-blank" type="number" name="kosong" min="0" max="60" value="0" class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-auto pt-5">
                    <button type="submit" class="{{ $printButton }}"><x-icon name="printer" class="h-4 w-4" stroke-width="2" /> Cetak Kartu Peserta</button>
                </div>
            </form>

            {{-- Kartu kosong --}}
            <form method="GET" action="{{ route('admin.spmb.prints.cards') }}" target="_blank" class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <input type="hidden" name="gelombang" value="{{ $selectedWave->id }}">
                <input type="hidden" name="hanya_kosong" value="1">
                <h2 class="text-base font-semibold text-slate-900">Kartu Peserta Kosong</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Untuk peserta yang belum mengunggah bukti bayar tetapi datang ke lokasi tes. Nama dan nomor diisi tangan.
                </p>
                <div class="mt-4">
                    <label for="blank-count" class="text-xs font-semibold text-slate-600">Jumlah kartu</label>
                    <input id="blank-count" type="number" name="kosong" min="1" max="60" value="10" required class="{{ $input }}">
                </div>
                <div class="mt-auto pt-5">
                    <button type="submit" class="{{ $printButton }}"><x-icon name="printer" class="h-4 w-4" stroke-width="2" /> Cetak Kartu Kosong</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
