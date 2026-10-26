{{-- Ringkasan biodata (langkah terakhir wizard) --}}
@php
    $ref = \App\Models\RefOption::class;
    $parentData = $applicant->parentData;
    $educationData = $applicant->education;
    $addressData = $applicant->address;

    $summary = [
        'pribadi' => [
            'Nama Lengkap' => $applicant->full_name,
            'NIK' => $applicant->nik,
            'NISN' => $applicant->nisn,
            'Jenis Kelamin' => $applicant->gender ? $applicant->gender_label : null,
            'Tempat, Tanggal Lahir' => $applicant->birth_place
                ? $applicant->birth_place . ', ' . $applicant->birth_date?->translatedFormat('d F Y')
                : null,
            'Agama' => $applicant->religion_name,
            'Email' => $applicant->email,
            'Nomor HP' => $applicant->phone,
        ],
        'prodi' => [
            'Pilihan Pertama' => $applicant->studyProgram?->name,
            'Pilihan Kedua' => $applicant->secondStudyProgram?->name,
            'Jenis Kelas' => $applicant->classType?->name,
            'Jalur' => $applicant->study_program_id ? $applicant->registration_path_label : null,
            'Nomor Pendaftaran' => $applicant->registration_number,
        ],
        'alamat' => [
            'Jalan' => $addressData?->address,
            'RT / RW' => $addressData?->rt || $addressData?->rw ? ($addressData?->rt ?? '-') . ' / ' . ($addressData?->rw ?? '-') : null,
            'Desa/Kelurahan' => $addressData?->village_name,
            'Kecamatan' => $addressData?->district_name,
            'Kab/Kota' => $addressData?->regency_name,
            'Provinsi' => $addressData?->province_name,
        ],
        'sekolah' => [
            'Sekolah' => $educationData?->school_name,
            'NPSN' => $educationData?->school_npsn,
            'Jenis' => trim(($educationData?->school_type ?? '') . ' ' . ($educationData?->school_status ?? '')) ?: null,
            'Jurusan' => $educationData?->major,
            'Tahun Lulus' => $educationData?->graduation_year,
        ],
        'orangtua' => [
            'Ibu Kandung' => $parentData?->mother_name,
            'Pekerjaan Ibu' => $ref::nameOf($ref::PEKERJAAN, $parentData?->mother_job_id),
            'Ayah' => $parentData?->father_name,
            'Pekerjaan Ayah' => $ref::nameOf($ref::PEKERJAAN, $parentData?->father_job_id),
            'Wali' => $parentData?->guardian_name,
        ],
    ];
@endphp

@if($biodataComplete)
    <div class="rounded-3xl border-2 border-green-400 bg-green-50 p-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <p class="text-lg font-black text-green-800">✓ Biodata Anda sudah lengkap</p>
                <p class="mt-1 text-sm leading-6 text-green-800/80">
                    Periksa kembali ringkasan di bawah. Anda masih bisa mengubah data sebelum hasil seleksi diumumkan.
                </p>
            </div>
            <a href="{{ route('camaba.documents.index') }}"
               class="inline-flex shrink-0 justify-center rounded-xl bg-green-600 px-6 py-3 text-sm font-black text-white transition hover:bg-green-700">
                Lanjutkan: Upload Berkas →
            </a>
        </div>
    </div>
@else
    <div class="rounded-3xl border-2 border-yellow-300 bg-yellow-50 p-6 text-sm leading-6 text-yellow-900">
        <p class="font-black">Biodata belum lengkap</p>
        <p>Lengkapi bagian yang belum bertanda hijau agar dapat mengikuti seleksi.</p>
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-2">
    @foreach($summary as $sectionKey => $rows)
        @php
            $complete = $sections[$sectionKey]['complete'];
        @endphp
        <div class="rounded-3xl border bg-white p-6 shadow-sm {{ $complete ? 'border-green-400 ring-1 ring-green-200' : 'border-slate-200' }}">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-black text-polmind-blue">{{ $sections[$sectionKey]['label'] }}</h3>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $complete ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                        {{ $complete ? '✓ Lengkap' : 'Belum lengkap' }}
                    </span>
                </div>
                <a href="{{ route('camaba.biodata.edit', ['step' => $sectionKey]) }}" class="text-xs font-bold text-polmind-blue underline">
                    {{ $complete ? 'Ubah' : 'Lengkapi' }}
                </a>
            </div>

            <dl class="mt-4 space-y-2 text-sm">
                @foreach($rows as $rowLabel => $value)
                    <div class="grid grid-cols-[9rem_1fr] gap-3">
                        <dt class="text-slate-500">{{ $rowLabel }}</dt>
                        <dd class="font-semibold {{ filled($value) ? 'text-slate-800' : 'text-slate-400' }}">{{ filled($value) ? $value : '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endforeach
</div>
