<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantEducation;
use App\Models\ClassType;
use App\Models\RefRegion;
use App\Models\RegistrationPath;
use App\Models\StudyProgram;
use App\Services\ApplicantBiodata;
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

        $references = ApplicantBiodata::references();

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
            'addressRegion' => RefRegion::chainFor(old('region_id', $applicant->address?->region_id)),
            'schoolRegion' => RefRegion::chainFor(old('school_region_id', $applicant->education?->school_region_id)),
            'programLocked' => $this->programLocked($applicant),
            'classLocked' => RegistrationNumber::isFinal($applicant->registration_number),
            'schoolTypes' => ApplicantEducation::SCHOOL_TYPES,
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
        ApplicantBiodata::prepare($request);

        $validated = $request->validate(
            ApplicantBiodata::personalRules($applicant->admission_wave_id, $applicant->id),
            ApplicantBiodata::personalMessages($applicant->admissionWave)
        );

        $applicant->update(ApplicantBiodata::personalAttributes($validated));

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
            // Jalur yang sudah dinonaktifkan tetap sah untuk camaba yang terlanjur memilihnya.
            'registration_path' => ['required', Rule::in(RegistrationPath::active()->keys()->push($applicant->registration_path)->filter()->all())],
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
        $validated = $request->validate(ApplicantBiodata::addressRules(), ApplicantBiodata::addressMessages());

        ApplicantBiodata::saveAddress($applicant, $validated);

        return null;
    }

    private function saveSchool(Request $request, Applicant $applicant): ?string
    {
        $manual = $request->boolean('is_manual_school');

        $validated = $request->validate(ApplicantEducation::schoolRules($manual) + [
            'major' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
        ], ApplicantEducation::schoolMessages());

        $applicant->education()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            ApplicantEducation::schoolAttributes($validated) + [
                'major' => $validated['major'],
                'graduation_year' => $validated['graduation_year'],
            ]
        );

        return null;
    }

    private function saveParents(Request $request, Applicant $applicant): ?string
    {
        $validated = $request->validate(ApplicantBiodata::parentRules(), ApplicantBiodata::parentMessages());

        $applicant->parentData()->updateOrCreate(['applicant_id' => $applicant->id], ApplicantBiodata::parentAttributes($validated));

        return null;
    }

    private function blankToNull($value)
    {
        return ApplicantBiodata::blankToNull($value);
    }

    /**
     * Pilihan prodi dikunci setelah hasil seleksi diputuskan.
     */
    private function programLocked(Applicant $applicant): bool
    {
        return ! in_array($applicant->selection_status, [null, '', 'belum_diseleksi'], true);
    }
}
