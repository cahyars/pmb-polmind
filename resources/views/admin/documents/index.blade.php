@extends('layouts.admin')

@section('title', 'Verifikasi Berkas')
@section('page_title', 'Verifikasi Berkas')
@section('page_subtitle', 'Validasi dokumen pendaftaran calon mahasiswa baru.')

@section('content')
@php
    $statusFilters = [
        'menunggu_verifikasi' => 'Perlu Verifikasi',
        'ditolak' => 'Ada Berkas Ditolak',
        'selesai' => 'Selesai Diverifikasi',
    ];

    $requiredCount = $requiredDocumentTypeIds->count();
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
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
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-base font-semibold text-slate-900">Filter Berkas</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama camaba, nomor pendaftaran, NIK, sekolah asal, atau nama file.
            </p>
        </div>

        <form action="{{ route('admin.documents.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="text-sm font-semibold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, no pendaftaran, NIK, sekolah, file..."
                       class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Jalur</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    @foreach(\App\Models\RegistrationPath::ordered() as $pathOption)
                        <option value="{{ $pathOption->code }}" @selected(request('registration_path') === $pathOption->code)>{{ $pathOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach($statusFilters as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.documents.index') }}"
                   class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Antrian Verifikasi Berkas</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Satu baris per camaba. Klik Detail untuk memeriksa berkas yang menunggu verifikasi.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                {{ $applicants->total() }} Camaba
            </span>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Camaba</th>
                    <th class="hidden px-5 py-3.5 lg:table-cell">Prodi &amp; Jalur</th>
                    <th class="px-5 py-3.5">Berkas</th>
                    <th class="hidden px-5 py-3.5 md:table-cell">Status</th>
                    <th class="hidden px-5 py-3.5 xl:table-cell">Upload Terakhir</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($applicants as $applicant)
                    @php
                        $waiting = $applicant->documents->where('status', 'menunggu_verifikasi');
                        $rejectedCount = $applicant->documents->where('status', 'ditolak')->count();
                        $acceptedRequiredCount = $applicant->documents
                            ->where('status', 'diterima')
                            ->whereIn('document_type_id', $requiredDocumentTypeIds)
                            ->count();

                        [$statusLabel, $statusClass] = match (true) {
                            $waiting->isNotEmpty() => ['Perlu Verifikasi', 'bg-yellow-100 text-yellow-700'],
                            $rejectedCount > 0 => ['Menunggu Upload Ulang', 'bg-red-100 text-red-700'],
                            $requiredCount > 0 && $acceptedRequiredCount >= $requiredCount => ['Lengkap', 'bg-green-100 text-green-700'],
                            default => ['Belum Lengkap', 'bg-slate-100 text-slate-600'],
                        };

                        $registrationPath = $applicant->registration_path ?? 'umum';
                        $lastUploadedAt = $applicant->last_uploaded_at ? \Illuminate\Support\Carbon::parse($applicant->last_uploaded_at) : null;
                    @endphp

                    <tr class="align-top transition hover:bg-slate-50 {{ $waiting->isNotEmpty() ? 'bg-yellow-50/40' : '' }}">
                        <td class="px-5 py-4 text-slate-500">{{ $applicants->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-polmind-blue">{{ $applicant->registration_number }}</p>
                            <p class="mt-1 font-semibold text-slate-800">{{ $applicant->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $applicant->education?->school_name ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500 lg:hidden">
                                {{ $applicant->studyProgram?->code ?? '-' }} · {{ $applicant->registration_path_label }}
                            </p>
                        </td>

                        <td class="hidden px-5 py-4 lg:table-cell">
                            <p class="font-semibold text-slate-800">{{ $applicant->studyProgram?->code ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $applicant->classType?->name ?? '-' }}</p>
                            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ \App\Models\RegistrationPath::badgeClass($registrationPath) }}">
                                {{ $applicant->registration_path_label }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            @if($waiting->isNotEmpty())
                                <p class="font-semibold text-yellow-800">
                                    {{ $waiting->count() }} berkas perlu diverifikasi
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($waiting as $document)
                                        <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">
                                            {{ $document->documentType?->name ?? '-' }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="font-semibold text-slate-600">Tidak ada berkas menunggu</p>
                            @endif

                            <p class="mt-2 text-xs text-slate-500">
                                Wajib diterima {{ $acceptedRequiredCount }}/{{ $requiredCount }}
                                @if($rejectedCount > 0)
                                    · <span class="font-semibold text-red-600">{{ $rejectedCount }} ditolak</span>
                                @endif
                            </p>

                            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold md:hidden {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        <td class="hidden px-5 py-4 md:table-cell">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        <td class="hidden px-5 py-4 text-slate-600 xl:table-cell">
                            {{ $lastUploadedAt?->format('d M Y H:i') ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.documents.show', $applicant->registration_number) }}"
                               class="inline-flex rounded-lg px-3 py-2 text-xs font-semibold transition {{ $waiting->isNotEmpty() ? 'bg-polmind-blue text-white hover:bg-polmind-blue-dark' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-slate-600">
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

        <div class="border-t border-slate-200 p-6">
            {{ $applicants->links() }}
        </div>
    </div>

</div>
@endsection