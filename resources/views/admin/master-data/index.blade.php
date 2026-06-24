@extends('layouts.admin')

@section('title', 'Master Data')
@section('page_title', 'Master Data')
@section('page_subtitle', 'Kelola referensi utama PMB seperti tahun, gelombang, prodi, kelas, berkas, dan biaya.')

@section('content')
@php
    $statusClass = function ($status) {
        return match ($status) {
            'active', 'open' => 'bg-green-100 text-green-700',
            'closed', 'archived' => 'bg-slate-100 text-slate-600',
            'draft' => 'bg-yellow-100 text-yellow-700',
            default => 'bg-slate-100 text-slate-600',
        };
    };

    $typeLabels = [
        'registration' => 'Pendaftaran',
        're_registration' => 'Daftar Ulang',
    ];
@endphp

<div class="space-y-8">
    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-3xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-bold text-blue-100">Konfigurasi Sistem PMB</p>
                <h1 class="mt-2 text-3xl font-black">Master Data PMB</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-blue-100">
                    Data referensi ini menjadi dasar untuk proses registrasi camaba, tagihan, seleksi,
                    daftar ulang, hingga integrasi ke SIAKAD.
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 p-5 lg:w-80">
                <p class="text-sm font-bold text-blue-100">Tahun PMB Aktif</p>
                <p class="mt-2 text-2xl font-black text-polmind-yellow">
                    {{ $activePmbYear?->name ?? 'Belum diset' }}
                </p>
                <p class="mt-1 text-xs text-blue-100">
                    {{ $activePmbYear?->start_date?->format('d M Y') ?? '-' }}
                    -
                    {{ $activePmbYear?->end_date?->format('d M Y') ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            ['label' => 'Tahun PMB', 'value' => $pmbYears->count()],
            ['label' => 'Gelombang', 'value' => $admissionWaves->count()],
            ['label' => 'Program Studi', 'value' => $studyPrograms->count()],
            ['label' => 'Jenis Kelas', 'value' => $classTypes->count()],
            ['label' => 'Jenis Berkas', 'value' => $documentTypes->count()],
            ['label' => 'Komponen Biaya', 'value' => $feeComponents->count()],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-black text-polmind-blue">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tahun PMB --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">Tahun PMB</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data periode penerimaan mahasiswa baru.
                </p>
            </div>

            <button type="button"
                    onclick="document.getElementById('create-pmb-year-form').classList.toggle('hidden')"
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Tambah Tahun
            </button>
        </div>

        <form id="create-pmb-year-form"
            action="{{ route('admin.master-data.pmb-years.store') }}"
            method="POST"
            class="hidden border-b border-slate-200 bg-slate-50 p-6">
            @csrf

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <input type="text" name="code" placeholder="Kode, contoh PMB2027" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="number" name="year" placeholder="2027" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="text" name="name" placeholder="Nama, contoh PMB 2027" required
                    class="xl:col-span-2 rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <select name="status" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                    <option value="archived">Archived</option>
                </select>

                <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1">
                    Aktif
                </label>

                <input type="date" name="start_date"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="date" name="end_date"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('create-pmb-year-form').classList.add('hidden')"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                    Batal
                </button>

                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                    Simpan Tahun
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Tahun</th>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Aktif</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($pmbYears as $year)
                        <tr>
                            <td class="px-6 py-4 font-black text-polmind-blue">{{ $year->code }}</td>
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $year->name }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $year->year }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $year->start_date?->format('d M Y') ?? '-' }}
                                -
                                {{ $year->end_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClass($year->status) }}">
                                    {{ ucfirst($year->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($year->is_active)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Tidak</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            onclick="document.getElementById('edit-pmb-year-{{ $year->id }}').classList.toggle('hidden')"
                                            class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.master-data.pmb-years.toggle', $year) }}"
                                        method="POST"
                                        onsubmit="return confirm('Ubah status aktif tahun PMB ini?')">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                            {{ $year->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr id="edit-pmb-year-{{ $year->id }}" class="hidden bg-slate-50">
                            <td colspan="7" class="px-6 py-5">
                                <form action="{{ route('admin.master-data.pmb-years.update', $year) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                        <input type="text" name="code" value="{{ $year->code }}" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="number" name="year" value="{{ $year->year }}" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="text" name="name" value="{{ $year->name }}" required
                                            class="xl:col-span-2 rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <select name="status" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                            @foreach(['draft', 'active', 'closed', 'archived'] as $status)
                                                <option value="{{ $status }}" @selected($year->status === $status)>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($year->is_active)>
                                            Aktif
                                        </label>

                                        <input type="date" name="start_date" value="{{ $year->start_date?->format('Y-m-d') }}"
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="date" name="end_date" value="{{ $year->end_date?->format('Y-m-d') }}"
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                    </div>

                                    <div class="mt-4 flex justify-end gap-3">
                                        <button type="button"
                                                onclick="document.getElementById('edit-pmb-year-{{ $year->id }}').classList.add('hidden')"
                                                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                                            Batal
                                        </button>

                                        <button type="submit"
                                                class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                                            Update Tahun
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
                                Belum ada data tahun PMB.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gelombang --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">Gelombang Pendaftaran</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Gelombang aktif menentukan periode pendaftaran yang sedang dibuka.
                </p>
            </div>

            <button type="button"
                    onclick="document.getElementById('create-admission-wave-form').classList.toggle('hidden')"
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Tambah Gelombang
            </button>
        </div>

        <form id="create-admission-wave-form"
            action="{{ route('admin.master-data.admission-waves.store') }}"
            method="POST"
            class="hidden border-b border-slate-200 bg-slate-50 p-6">
            @csrf

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <select name="pmb_year_id" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Pilih Tahun PMB</option>
                    @foreach($pmbYears as $year)
                        <option value="{{ $year->id }}" @selected($year->is_active)>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>

                <input type="text" name="code" placeholder="Kode, contoh GEL4" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="text" name="name" placeholder="Nama gelombang" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="number" name="registration_fee" value="350000" min="0" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <select name="status" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="draft">Draft</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>

                <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1">
                    Aktif
                </label>

                <input type="date" name="start_date"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="date" name="end_date"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('create-admission-wave-form').classList.add('hidden')"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                    Batal
                </button>

                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                    Simpan Gelombang
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Tahun PMB</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Biaya Daftar</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Aktif</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($admissionWaves as $wave)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $wave->pmbYear?->name ?? '-' }}</td>
                            <td class="px-6 py-4 font-black text-polmind-blue">{{ $wave->code }}</td>
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $wave->name }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $wave->start_date?->format('d M Y') ?? '-' }}
                                -
                                {{ $wave->end_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-black text-polmind-blue">
                                Rp{{ number_format($wave->registration_fee, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClass($wave->status) }}">
                                    {{ ucfirst($wave->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($wave->is_active)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Tidak</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            onclick="document.getElementById('edit-admission-wave-{{ $wave->id }}').classList.toggle('hidden')"
                                            class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.master-data.admission-waves.toggle', $wave) }}"
                                        method="POST"
                                        onsubmit="return confirm('Ubah status aktif gelombang ini?')">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                            {{ $wave->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr id="edit-admission-wave-{{ $wave->id }}" class="hidden bg-slate-50">
                            <td colspan="8" class="px-6 py-5">
                                <form action="{{ route('admin.master-data.admission-waves.update', $wave) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                        <select name="pmb_year_id" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                            @foreach($pmbYears as $year)
                                                <option value="{{ $year->id }}" @selected($wave->pmb_year_id === $year->id)>
                                                    {{ $year->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="text" name="code" value="{{ $wave->code }}" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="text" name="name" value="{{ $wave->name }}" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="number" name="registration_fee" value="{{ $wave->registration_fee }}" min="0" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <select name="status" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                            @foreach(['draft', 'open', 'closed'] as $status)
                                                <option value="{{ $status }}" @selected($wave->status === $status)>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($wave->is_active)>
                                            Aktif
                                        </label>

                                        <input type="date" name="start_date" value="{{ $wave->start_date?->format('Y-m-d') }}"
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="date" name="end_date" value="{{ $wave->end_date?->format('Y-m-d') }}"
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                    </div>

                                    <div class="mt-4 flex justify-end gap-3">
                                        <button type="button"
                                                onclick="document.getElementById('edit-admission-wave-{{ $wave->id }}').classList.add('hidden')"
                                                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                                            Batal
                                        </button>

                                        <button type="submit"
                                                class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                                            Update Gelombang
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
                                Belum ada data gelombang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Prodi & Kelas --}}
    <div class="grid gap-8 xl:grid-cols-2">

        {{-- Prodi --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black text-polmind-blue">Program Studi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data prodi untuk pilihan pendaftaran camaba.
                    </p>
                </div>

                <button type="button"
                        onclick="document.getElementById('create-study-program-form').classList.toggle('hidden')"
                        class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Tambah Prodi
                </button>
            </div>

            <form id="create-study-program-form"
                action="{{ route('admin.master-data.study-programs.store') }}"
                method="POST"
                class="hidden border-b border-slate-200 bg-slate-50 p-6">
                @csrf

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <input type="text" name="code" placeholder="Kode, contoh TRPL" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                    <input type="text" name="name" placeholder="Nama prodi" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                    <input type="text" name="degree" value="D4" placeholder="Jenjang" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                    <input type="number" name="quota" value="40" min="0" placeholder="Kuota" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                    <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Aktif
                    </label>

                    <textarea name="description" rows="3" placeholder="Deskripsi prodi"
                            class="md:col-span-2 xl:col-span-5 rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"></textarea>
                </div>

                <div class="mt-4 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('create-study-program-form').classList.add('hidden')"
                            class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                        Batal
                    </button>

                    <button type="submit"
                            class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                        Simpan Prodi
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Kode</th>
                            <th class="px-6 py-4">Nama Prodi</th>
                            <th class="px-6 py-4">Jenjang</th>
                            <th class="px-6 py-4">Kuota</th>
                            <th class="px-6 py-4">Aktif</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @forelse($studyPrograms as $program)
                            <tr>
                                <td class="px-6 py-4 font-black text-polmind-blue">{{ $program->code }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-800">{{ $program->name }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $program->description }}</p>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-700">{{ $program->degree }}</td>
                                <td class="px-6 py-4 font-black text-polmind-blue">{{ $program->quota }}</td>
                                <td class="px-6 py-4">
                                    @if($program->is_active)
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                                onclick="document.getElementById('edit-study-program-{{ $program->id }}').classList.toggle('hidden')"
                                                class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.master-data.study-programs.toggle', $program) }}"
                                            method="POST"
                                            onsubmit="return confirm('Ubah status aktif prodi ini?')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                                {{ $program->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr id="edit-study-program-{{ $program->id }}" class="hidden bg-slate-50">
                                <td colspan="6" class="px-6 py-5">
                                    <form action="{{ route('admin.master-data.study-programs.update', $program) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                            <input type="text" name="code" value="{{ $program->code }}" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                            <input type="text" name="name" value="{{ $program->name }}" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                            <input type="text" name="degree" value="{{ $program->degree }}" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                            <input type="number" name="quota" value="{{ $program->quota }}" min="0" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                            <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" @checked($program->is_active)>
                                                Aktif
                                            </label>

                                            <textarea name="description" rows="3"
                                                    class="md:col-span-2 xl:col-span-5 rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">{{ $program->description }}</textarea>
                                        </div>

                                        <div class="mt-4 flex justify-end gap-3">
                                            <button type="button"
                                                    onclick="document.getElementById('edit-study-program-{{ $program->id }}').classList.add('hidden')"
                                                    class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                                                Batal
                                            </button>

                                            <button type="submit"
                                                    class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                                                Update Prodi
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
                                    Belum ada data prodi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kelas --}}
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <h2 class="text-xl font-black text-polmind-blue">Jenis Kelas</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data kelas reguler pagi dan kelas karyawan/malam.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Kode</th>
                            <th class="px-6 py-4">Nama</th>
                            <th class="px-6 py-4">Deskripsi</th>
                            <th class="px-6 py-4">Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($classTypes as $classType)
                            <tr>
                                <td class="px-6 py-4 font-black text-polmind-blue">{{ $classType->code }}</td>
                                <td class="px-6 py-4 font-bold text-slate-800">{{ $classType->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $classType->description ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $classType->is_active ? 'Ya' : 'Tidak' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
                                    Belum ada data kelas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Berkas --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-xl font-black text-polmind-blue">Jenis Berkas</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Jenis dokumen yang harus atau dapat diupload oleh camaba.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Urutan</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Berkas</th>
                        <th class="px-6 py-4">Wajib</th>
                        <th class="px-6 py-4">Format</th>
                        <th class="px-6 py-4">Max Size</th>
                        <th class="px-6 py-4">Aktif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($documentTypes as $documentType)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-700">{{ $documentType->sort_order }}</td>
                            <td class="px-6 py-4 font-black text-polmind-blue">{{ $documentType->code }}</td>
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $documentType->name }}</td>
                            <td class="px-6 py-4">
                                @if($documentType->is_required)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">Wajib</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Opsional</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ implode(', ', $documentType->allowed_extensions ?? []) }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($documentType->max_size_kb) }} KB
                            </td>
                            <td class="px-6 py-4">
                                {{ $documentType->is_active ? 'Ya' : 'Tidak' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
                                Belum ada data jenis berkas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Biaya --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">Komponen Biaya</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Komponen biaya untuk invoice pendaftaran dan daftar ulang.
                </p>
            </div>

            <button type="button"
                    onclick="document.getElementById('create-fee-component-form').classList.toggle('hidden')"
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Tambah Biaya
            </button>
        </div>

        <form id="create-fee-component-form"
            action="{{ route('admin.master-data.fee-components.store') }}"
            method="POST"
            class="hidden border-b border-slate-200 bg-slate-50 p-6">
            @csrf

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <input type="text" name="code" placeholder="Kode, contoh SPP1" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="text" name="name" placeholder="Nama biaya" required
                    class="xl:col-span-2 rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <select name="type" required
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="registration">Pendaftaran</option>
                    <option value="re_registration">Daftar Ulang</option>
                </select>

                <input type="number" name="amount" value="0" min="0" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <input type="number" name="sort_order" value="1" min="0" required
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Aktif
                </label>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('create-fee-component-form').classList.add('hidden')"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                    Batal
                </button>

                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                    Simpan Biaya
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Urutan</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Biaya</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Aktif</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($feeComponents as $fee)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-700">{{ $fee->sort_order }}</td>
                            <td class="px-6 py-4 font-black text-polmind-blue">{{ $fee->code }}</td>
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $fee->name }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $typeLabels[$fee->type] ?? $fee->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-black text-green-700">
                                Rp{{ number_format($fee->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($fee->is_active)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            onclick="document.getElementById('edit-fee-component-{{ $fee->id }}').classList.toggle('hidden')"
                                            class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.master-data.fee-components.toggle', $fee) }}"
                                        method="POST"
                                        onsubmit="return confirm('Ubah status aktif komponen biaya ini?')">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                            {{ $fee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr id="edit-fee-component-{{ $fee->id }}" class="hidden bg-slate-50">
                            <td colspan="7" class="px-6 py-5">
                                <form action="{{ route('admin.master-data.fee-components.update', $fee) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                        <input type="text" name="code" value="{{ $fee->code }}" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="text" name="name" value="{{ $fee->name }}" required
                                            class="xl:col-span-2 rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <select name="type" required
                                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                            <option value="registration" @selected($fee->type === 'registration')>Pendaftaran</option>
                                            <option value="re_registration" @selected($fee->type === 're_registration')>Daftar Ulang</option>
                                        </select>

                                        <input type="number" name="amount" value="{{ $fee->amount }}" min="0" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <input type="number" name="sort_order" value="{{ $fee->sort_order }}" min="0" required
                                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                        <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($fee->is_active)>
                                            Aktif
                                        </label>
                                    </div>

                                    <div class="mt-4 flex justify-end gap-3">
                                        <button type="button"
                                                onclick="document.getElementById('edit-fee-component-{{ $fee->id }}').classList.add('hidden')"
                                                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                                            Batal
                                        </button>

                                        <button type="submit"
                                                class="rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white">
                                            Update Biaya
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
                                Belum ada data komponen biaya.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection