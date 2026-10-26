<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\RefOption;
use App\Models\RefRegion;
use App\Models\StudyProgram;
use App\Services\ApplicantJourney;
use App\Services\RegistrationNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Wizard biodata camaba. Setiap langkah disimpan terpisah agar camaba
 * bisa berhenti dan melanjutkan kapan saja.
 */
class BiodataController extends Controller
{
    public const STEPS = ['pribadi', 'prodi', 'alamat', 'sekolah', 'orangtua', 'ringkasan'];

    public const SCHOOL_TYPES = ['SMA', 'SMK', 'MA', 'MAK', 'Paket C', 'Lainnya'];

    public function edit(Request $request)
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->load([
            'studyProgram',
            'secondStudyProgram',
            'classType',
            'address',
            'education',
            'parentData',
        ]);

        $journey = ApplicantJourney::for($applicant);

        $step = in_array($request->query('step'), self::STEPS, true)
            ? $request->query('step')
            : $journey->firstIncompleteSection();

        $references = collect([
            RefOption::AGAMA,
            RefOption::PENDIDIKAN,
            RefOption::PEKERJAAN,
            RefOption::PENGHASILAN,
            RefOption::JENIS_TINGGAL,
            RefOption::ALAT_TRANSPORTASI,
            RefOption::KEBUTUHAN_KHUSUS,
            RefOption::KEWARGANEGARAAN,
        ])->mapWithKeys(fn (string $group) => [$group => RefOption::optionsFor($group)]);

        return view('camaba.biodata', [
            'applicant' => $applicant,
            'step' => $step,
            'steps' => self::STEPS,
            'sections' => $journey->biodataSections(),
            'biodataComplete' => $journey->biodataComplete(),
            'studyPrograms' => StudyProgram::where('is_active', true)->orderBy('code')->get(),
            'classTypes' => ClassType::where('is_active', true)->orderBy('code')->get(),
            'references' => $references,
            'provinces' => RefRegion::where('level', RefRegion::LEVEL_PROVINCE)->orderBy('name')->get(['id', 'name']),
            'addressRegion' => $this->regionChain(old('region_id', $applicant->address?->region_id)),
            'schoolRegion' => $this->regionChain(old('school_region_id', $applicant->education?->school_region_id)),
            'programLocked' => $this->programLocked($applicant),
            'classLocked' => RegistrationNumber::isFinal($applicant->registration_number),
            'schoolTypes' => self::SCHOOL_TYPES,
        ]);
    }

    public function update(Request $request)
    {
        $applicant = Auth::guard('applicant')->user();

        $step = $request->validate([
            'step' => ['required', Rule::in(array_keys(ApplicantJourney::BIODATA_SECTIONS))],
        ])['step'];

        $message = match ($step) {
            'pribadi' => $this->savePersonal($request, $applicant),
            'prodi' => $this->saveProgram($request, $applicant),
            'alamat' => $this->saveAddress($request, $applicant),
            'sekolah' => $this->saveSchool($request, $applicant),
            'orangtua' => $this->saveParents($request, $applicant),
        };

        $applicant->refresh();

        $journey = ApplicantJourney::for($applicant);

        if ($journey->biodataComplete() && $applicant->registration_status !== 'biodata_lengkap') {
            $applicant->update(['registration_status' => 'biodata_lengkap']);
        }

        $nextStep = self::STEPS[array_search($step, self::STEPS, true) + 1];

        if ($nextStep === 'ringkasan' && ! $journey->biodataComplete()) {
            $nextStep = $journey->firstIncompleteSection();
        }

        return redirect()
            ->route('camaba.biodata.edit', ['step' => $nextStep])
            ->with('success', $message ?? ApplicantJourney::BIODATA_SECTIONS[$step]['label'] . ' berhasil disimpan.');
    }

    private function savePersonal(Request $request, Applicant $applicant): ?string
    {
        $request->merge([
            'full_name' => mb_strtoupper(trim((string) $request->input('full_name'))),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', Rule::unique('applicants', 'nik')->ignore($applicant->id)],
            'nisn' => ['nullable', 'digits:10'],
            'npwp' => ['nullable', 'regex:/^[0-9]{15,16}$/'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'religion_id' => ['required', $this->refRule(RefOption::AGAMA)],
            'citizenship' => ['required', $this->refRule(RefOption::KEWARGANEGARAAN)],
            'special_need_id' => ['required', $this->refRule(RefOption::KEBUTUHAN_KHUSUS)],
            'kps_recipient' => ['required', 'boolean'],
            'kps_number' => ['nullable', 'required_if:kps_recipient,1', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('applicants', 'email')->ignore($applicant->id)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'home_phone' => ['nullable', 'string', 'regex:/^[0-9]{6,15}$/'],
            'transportation_id' => ['nullable', $this->refRule(RefOption::ALAT_TRANSPORTASI)],
        ], [
            'phone.regex' => 'Nomor HP hanya boleh berisi angka (9-15 digit).',
            'home_phone.regex' => 'Telepon rumah hanya boleh berisi angka.',
            'npwp.regex' => 'NPWP harus 15 atau 16 digit angka.',
            'kps_number.required_if' => 'Nomor KPS wajib diisi untuk penerima KPS.',
        ]);

        $applicant->update([
            'full_name' => $validated['full_name'],
            'nik' => $validated['nik'],
            'nisn' => $this->blankToNull($validated['nisn'] ?? null),
            'npwp' => $this->blankToNull($validated['npwp'] ?? null),
            'gender' => $validated['gender'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'religion_id' => $validated['religion_id'],
            'citizenship' => $validated['citizenship'],
            'special_need_id' => $validated['special_need_id'],
            'kps_recipient' => (bool) $validated['kps_recipient'],
            'kps_number' => $validated['kps_recipient'] ? $this->blankToNull($validated['kps_number'] ?? null) : null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'home_phone' => $this->blankToNull($validated['home_phone'] ?? null),
            'transportation_id' => $this->blankToNull($validated['transportation_id'] ?? null),
        ]);

        return null;
    }

    private function saveProgram(Request $request, Applicant $applicant): ?string
    {
        if ($this->programLocked($applicant)) {
            return 'Pilihan program studi sudah dikunci karena hasil seleksi telah diputuskan.';
        }

        $classLocked = RegistrationNumber::isFinal($applicant->registration_number);

        $validated = $request->validate([
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')->where('is_active', true)],
            'second_study_program_id' => ['nullable', 'different:study_program_id', Rule::exists('study_programs', 'id')->where('is_active', true)],
            'class_type_id' => [Rule::excludeIf($classLocked), 'required', Rule::exists('class_types', 'id')->where('is_active', true)],
            'registration_path' => ['required', 'in:umum,prestasi,undangan'],
        ], [
            'second_study_program_id.different' => 'Pilihan prodi kedua harus berbeda dengan pilihan pertama.',
        ]);

        return DB::transaction(function () use ($applicant, $validated, $classLocked) {
            $data = [
                'study_program_id' => $validated['study_program_id'],
                'second_study_program_id' => $this->blankToNull($validated['second_study_program_id'] ?? null),
                'registration_path' => $validated['registration_path'],
            ];

            $message = null;

            if (! $classLocked) {
                $classType = ClassType::findOrFail($validated['class_type_id']);

                $data['class_type_id'] = $classType->id;
                $data['registration_number'] = RegistrationNumber::final($applicant, $classType);

                $message = 'Program studi tersimpan. Nomor pendaftaran Anda sekarang: ' . $data['registration_number'] . '.';
            }

            $applicant->update($data);

            return $message;
        });
    }

    private function saveAddress(Request $request, Applicant $applicant): ?string
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:500'],
            'hamlet' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'digits_between:1,3'],
            'rw' => ['nullable', 'digits_between:1,3'],
            'village_name' => ['required', 'string', 'max:100'],
            'region_id' => ['required', Rule::exists('ref_regions', 'id')->where('level', RefRegion::LEVEL_DISTRICT)],
            'postal_code' => ['nullable', 'digits:5'],
            'residence_type_id' => ['nullable', $this->refRule(RefOption::JENIS_TINGGAL)],
        ], [
            'region_id.required' => 'Pilih provinsi, kabupaten/kota, dan kecamatan.',
        ]);

        $district = RefRegion::with('parent.parent')->findOrFail($validated['region_id']);

        $applicant->address()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'address' => $validated['address'],
                'hamlet' => $this->blankToNull($validated['hamlet'] ?? null),
                'rt' => $this->padNumber($validated['rt'] ?? null),
                'rw' => $this->padNumber($validated['rw'] ?? null),
                'village_code' => null,
                'village_name' => $validated['village_name'],
                'region_id' => $district->id,
                'district_code' => $district->id,
                'district_name' => $district->name,
                'regency_code' => $district->parent?->id,
                'regency_name' => $district->parent?->name,
                'province_code' => $district->parent?->parent?->id,
                'province_name' => $district->parent?->parent?->name,
                'postal_code' => $this->blankToNull($validated['postal_code'] ?? null),
                'residence_type_id' => $this->blankToNull($validated['residence_type_id'] ?? null),
            ]
        );

        return null;
    }

    private function saveSchool(Request $request, Applicant $applicant): ?string
    {
        $manual = $request->boolean('is_manual_school');

        $validated = $request->validate([
            'school_region_id' => ['required', Rule::exists('ref_regions', 'id')->where('level', RefRegion::LEVEL_DISTRICT)],
            'is_manual_school' => ['required', 'boolean'],
            'school_npsn' => [$manual ? 'nullable' : 'required', 'digits:8'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', Rule::in(self::SCHOOL_TYPES)],
            'school_status' => ['nullable', 'in:Negeri,Swasta'],
            'major' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
        ], [
            'school_region_id.required' => 'Pilih provinsi, kabupaten/kota, dan kecamatan lokasi sekolah.',
            'school_npsn.required' => 'Pilih sekolah dari daftar, atau centang "Sekolah saya tidak ada di daftar".',
        ]);

        $applicant->education()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'school_region_id' => $validated['school_region_id'],
                'is_manual_school' => $manual,
                'school_npsn' => $this->blankToNull($validated['school_npsn'] ?? null),
                'school_name' => mb_strtoupper($validated['school_name']),
                'school_type' => $validated['school_type'],
                'school_status' => $this->blankToNull($validated['school_status'] ?? null),
                'major' => $validated['major'],
                'graduation_year' => $validated['graduation_year'],
            ]
        );

        return null;
    }

    private function saveParents(Request $request, Applicant $applicant): ?string
    {
        $person = fn (string $prefix, bool $nameRequired = false) => [
            "{$prefix}_name" => [$nameRequired ? 'required' : 'nullable', 'string', 'max:255'],
            "{$prefix}_nik" => ['nullable', 'digits:16'],
            "{$prefix}_birth_date" => ['nullable', 'date', 'before:today'],
            "{$prefix}_education_id" => ['nullable', $this->refRule(RefOption::PENDIDIKAN)],
            "{$prefix}_job_id" => ['nullable', $this->refRule(RefOption::PEKERJAAN)],
            "{$prefix}_income_id" => ['nullable', $this->refRule(RefOption::PENGHASILAN)],
            "{$prefix}_phone" => ['nullable', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
        ];

        $validated = $request->validate(
            $person('father')
            + $person('mother', true)
            + $person('guardian')
            + [
                'father_special_need_id' => ['required', $this->refRule(RefOption::KEBUTUHAN_KHUSUS)],
                'mother_special_need_id' => ['required', $this->refRule(RefOption::KEBUTUHAN_KHUSUS)],
                'guardian_relation' => ['nullable', 'string', 'max:100'],
            ],
            ['*_phone.regex' => 'Nomor HP hanya boleh berisi angka (9-15 digit).']
        );

        $data = collect($validated)->map(fn ($value) => $this->blankToNull($value))->all();

        $data['mother_name'] = mb_strtoupper($validated['mother_name']);
        $data['father_special_need_id'] = $validated['father_special_need_id'];
        $data['mother_special_need_id'] = $validated['mother_special_need_id'];

        $applicant->parentData()->updateOrCreate(['applicant_id' => $applicant->id], $data);

        return null;
    }

    /**
     * Provinsi, kab/kota, dan kecamatan terpilih untuk dropdown berjenjang.
     */
    private function regionChain(?string $districtId): array
    {
        $district = $districtId ? RefRegion::with('parent')->find($districtId) : null;

        return [
            'province' => $district?->parent?->parent_id,
            'regency' => $district?->parent_id,
            'district' => $district?->id,
        ];
    }

    private function refRule(string $group)
    {
        return Rule::in(RefOption::codesFor($group));
    }

    private function blankToNull($value)
    {
        return filled($value) ? $value : null;
    }

    private function padNumber(?string $value): ?string
    {
        return filled($value) ? str_pad(ltrim($value, '0') ?: '0', 3, '0', STR_PAD_LEFT) : null;
    }

    /**
     * Pilihan prodi dikunci setelah hasil seleksi diputuskan.
     */
    private function programLocked(Applicant $applicant): bool
    {
        return ! in_array($applicant->selection_status, [null, '', 'belum_diseleksi'], true);
    }
}
