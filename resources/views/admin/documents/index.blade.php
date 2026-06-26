@extends('layouts.admin')

@section('title', 'Verifikasi Berkas')
@section('page_title', 'Verifikasi Berkas')
@section('page_subtitle', 'Validasi dokumen pendaftaran calon mahasiswa baru.')

@section('content')
@php
    $statusLabels = [
        'belum_upload' => 'Belum Upload',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
    ];

    $statusClasses = [
        'belum_upload' => 'bg-slate-100 text-slate-600',
        'menunggu_verifikasi' => 'bg-yellow-100 text-yellow-700',
        'diterima' => 'bg-green-100 text-green-700',
        'ditolak' => 'bg-red-100 text-red-700',
    ];

    $registrationPathClasses = [
        'umum' => 'bg-blue-50 text-polmind-blue',
        'prestasi' => 'bg-green-100 text-green-700',
        'undangan' => 'bg-purple-100 text-purple-700',
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

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Menunggu Verifikasi',
                'value' => $waitingDocuments,
                'desc' => 'Perlu dicek admin',
                'class' => 'text-yellow-700',
            ],
            [
                'label' => 'Diterima',
                'value' => $acceptedDocuments,
                'desc' => 'Berkas valid',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Ditolak',
                'value' => $rejectedDocuments,
                'desc' => 'Perlu upload ulang',
                'class' => 'text-red-700',
            ],
            [
                'label' => 'Belum Upload',
                'value' => $notUploadedDocuments,
                'desc' => 'Belum ada file',
                'class' => 'text-slate-700',
            ],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Berkas</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama camaba, nomor pendaftaran, NIK, sekolah asal, atau nama file.
            </p>
        </div>

        <form action="{{ route('admin.documents.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, no pendaftaran, NIK, sekolah, file..."
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jenis Berkas</label>
                <select name="document_type"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Berkas</option>
                    @foreach($documentTypes as $type)
                        <option value="{{ $type->id }}" @selected(request('document_type') == $type->id)>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Jalur</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    <option value="umum" @selected(request('registration_path') === 'umum')>
                        Umum
                    </option>
                    <option value="prestasi" @selected(request('registration_path') === 'prestasi')>
                        Prestasi
                    </option>
                    <option value="undangan" @selected(request('registration_path') === 'undangan')>
                        Undangan
                    </option>
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-6">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.documents.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">Antrian Verifikasi Berkas</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Admin hanya dapat memvalidasi berkas yang berstatus menunggu verifikasi.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $documents->total() }} Data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1300px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Jalur</th>
                        <th class="px-6 py-4">Jenis Berkas</th>
                        <th class="px-6 py-4">File</th>
                        <th class="px-6 py-4">Prodi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Catatan</th>
                        <th class="px-6 py-4">Upload</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($documents as $document)
                        @php
                            $fileUrl = $document->file_path
                                ? asset('storage/' . $document->file_path)
                                : null;

                            $registrationPath = $document->applicant?->registration_path ?? 'umum';
                            $isWaiting = $document->status === 'menunggu_verifikasi';
                        @endphp

                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $document->applicant?->registration_number }}
                                </p>

                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $document->applicant?->full_name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $document->applicant?->education?->school_name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $document->applicant?->phone ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $registrationPathClasses[$registrationPath] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $document->applicant?->registration_path_label ?? 'Umum' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">
                                    {{ $document->documentType?->name ?? '-' }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if($document->documentType?->is_required)
                                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">
                                            Wajib
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                            Opsional
                                        </span>
                                    @endif

                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                        {{ $document->documentType?->code ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <p class="max-w-56 truncate font-semibold text-slate-700">
                                    {{ $document->file_name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ strtoupper($document->file_extension ?? '-') }} · {{ $document->file_size_kb ?? 0 }} KB
                                </p>

                                @if($fileUrl)
                                    <a href="{{ $fileUrl }}"
                                       target="_blank"
                                       class="mt-3 inline-flex rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-polmind-blue transition hover:bg-blue-100">
                                        Lihat File
                                    </a>
                                @else
                                    <span class="mt-3 inline-flex rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">
                                        File kosong
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $document->applicant?->studyProgram?->code ?? '-' }}
                                </span>

                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $document->applicant?->classType?->name ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$document->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$document->status] ?? $document->status }}
                                </span>

                                @if($document->verified_at)
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $document->verified_at->format('d M Y H:i') }}
                                    </p>
                                @endif

                                @if($document->verified_by_name)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $document->verified_by_name }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if($document->admin_note)
                                    <div class="max-w-64 rounded-xl border border-red-200 bg-red-50 p-3 text-xs leading-5 text-red-700">
                                        <p class="font-black">Catatan Admin</p>
                                        <p class="mt-1">{{ $document->admin_note }}</p>
                                    </div>
                                @else
                                    <span class="text-slate-500">-</span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-slate-600">
                                {{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $document->applicant?->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if($isWaiting)
                                        <form action="{{ route('admin.documents.accept', $document) }}"
                                              method="POST"
                                              onsubmit="return confirm('Terima berkas ini?')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                                Terima
                                            </button>
                                        </form>

                                        <button type="button"
                                                onclick="document.getElementById('reject-form-{{ $document->id }}').classList.toggle('hidden')"
                                                class="rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                                            Tolak
                                        </button>
                                    @else
                                        <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">
                                            Selesai
                                        </span>
                                    @endif
                                </div>

                                @if($isWaiting)
                                    <form id="reject-form-{{ $document->id }}"
                                          action="{{ route('admin.documents.reject', $document) }}"
                                          method="POST"
                                          class="mt-3 hidden rounded-2xl border border-red-200 bg-red-50 p-3">
                                        @csrf
                                        @method('PATCH')

                                        <textarea name="admin_note"
                                                  rows="3"
                                                  required
                                                  placeholder="Tulis alasan penolakan..."
                                                  class="w-full rounded-xl border border-red-200 px-3 py-2 text-xs outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100">{{ old('admin_note') }}</textarea>

                                        <div class="mt-2 flex justify-end gap-2">
                                            <button type="button"
                                                    onclick="document.getElementById('reject-form-{{ $document->id }}').classList.add('hidden')"
                                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                                                Batal
                                            </button>

                                            <button type="submit"
                                                    class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">
                                                Simpan Tolak
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
                                    Tidak ada data berkas ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba reset filter atau tunggu camaba mengupload berkas.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-6">
            {{ $documents->links() }}
        </div>
    </div>

</div>
@endsection