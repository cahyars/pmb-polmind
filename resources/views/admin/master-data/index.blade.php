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
                    onclick="Swal.fire({
                        title: 'Tambah Tahun PMB',
                        text: 'Form tambah/edit master data akan kita sambungkan di tahap berikutnya.',
                        icon: 'info',
                        confirmButtonColor: '#003B82'
                    })"
                    class="rounded-xl bg-polmind-yellow px-5 py-3 text-sm font-black text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                Tambah Tahun
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Tahun</th>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Aktif</th>
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
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                        Tidak
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
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
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-xl font-black text-polmind-blue">Gelombang Pendaftaran</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Gelombang aktif menentukan periode pendaftaran yang sedang dibuka.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Tahun PMB</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Biaya Daftar</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Aktif</th>
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
                                {{ $wave->is_active ? 'Ya' : 'Tidak' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
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
            <div class="border-b border-slate-200 p-6">
                <h2 class="text-xl font-black text-polmind-blue">Program Studi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Data prodi untuk pilihan pendaftaran camaba.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Kode</th>
                            <th class="px-6 py-4">Nama Prodi</th>
                            <th class="px-6 py-4">Jenjang</th>
                            <th class="px-6 py-4">Kuota</th>
                            <th class="px-6 py-4">Aktif</th>
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
                                    {{ $program->is_active ? 'Ya' : 'Tidak' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
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
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-xl font-black text-polmind-blue">Komponen Biaya</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Komponen biaya untuk invoice pendaftaran dan daftar ulang.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Urutan</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Biaya</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Aktif</th>
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
                                {{ $fee->is_active ? 'Ya' : 'Tidak' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm font-semibold text-slate-500">
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