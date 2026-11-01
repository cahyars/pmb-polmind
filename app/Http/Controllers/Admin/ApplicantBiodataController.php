<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantEducation;
use App\Models\RefRegion;
use App\Services\ApplicantBiodata;
use App\Services\ApplicantJourney;
use App\Services\LoginInfoMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Detail Camaba > Edit Biodata: admin/operator membetulkan biodata camaba, baik yang daftar
 * online maupun di kampus. Aturan isian sama dengan wizard camaba dan form pendaftar di kampus.
 * Gelombang, prodi, kelas, dan jalur tidak diubah di sini karena terkait nomor pendaftaran dan seleksi.
 */
class ApplicantBiodataController extends Controller
{
    public function edit(string $registration_number)
    {
        $applicant = $this->find($registration_number);

        return view('admin.applicants.edit', [
            'applicant' => $applicant,
            'references' => ApplicantBiodata::references(),
            'provinces' => RefRegion::where('level', RefRegion::LEVEL_PROVINCE)->orderBy('name')->get(['id', 'name']),
            'addressRegion' => RefRegion::chainFor(old('region_id', $applicant->address?->region_id)),
            'schoolRegion' => RefRegion::chainFor(old('school_region_id', $applicant->education?->school_region_id)),
            'schoolTypes' => ApplicantEducation::SCHOOL_TYPES,
            'addressRequired' => $this->hasAddress($applicant),
            'schoolRequired' => $this->hasSchool($applicant),
            'motherRequired' => filled($applicant->parentData?->mother_name),
        ]);
    }

    public function update(Request $request, string $registration_number)
    {
        $applicant = $this->find($registration_number);

        ApplicantBiodata::prepare($request);

        // Bagian yang sudah terisi tidak boleh dikosongkan; bagian yang masih kosong boleh tetap kosong.
        $addressRequired = $this->hasAddress($applicant);
        $schoolRequired = $this->hasSchool($applicant) || $request->filled('school_name');
        $motherRequired = filled($applicant->parentData?->mother_name);
        $saveParents = $applicant->parentData
            || $request->filled('mother_name') || $request->filled('father_name') || $request->filled('guardian_name');

        $validated = $request->validate(
            ApplicantBiodata::personalRules($applicant->admission_wave_id, $applicant->id)
            + ApplicantBiodata::addressRules($addressRequired)
            + ($schoolRequired ? ApplicantEducation::schoolRules($request->boolean('is_manual_school')) : [])
            + [
                'major' => ['nullable', 'string', 'max:255'],
                'graduation_year' => ['nullable', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
                'school_address' => ['nullable', 'string', 'max:500'],
            ]
            + ($saveParents ? ApplicantBiodata::parentRules($motherRequired) : []),
            ApplicantBiodata::personalMessages($applicant->admissionWave)
            + ApplicantEducation::schoolMessages()
            + ApplicantBiodata::addressMessages()
            + ApplicantBiodata::parentMessages()
        );

        $hadDefaultPin = LoginInfoMessage::activePin($applicant) !== null;

        DB::transaction(function () use ($applicant, $validated, $schoolRequired, $saveParents, $hadDefaultPin) {
            $applicant->fill(ApplicantBiodata::personalAttributes($validated));

            // PIN awal = tanggal lahir; ikut dibetulkan selama camaba belum mengganti passwordnya.
            if ($hadDefaultPin && $applicant->isDirty('birth_date')) {
                $applicant->password = LoginInfoMessage::defaultPin($applicant);
            }

            $applicant->save();

            if (filled($validated['address'] ?? null)) {
                ApplicantBiodata::saveAddress($applicant, $validated);
            }

            $education = [
                'major' => ApplicantBiodata::blankToNull($validated['major'] ?? null),
                'graduation_year' => ApplicantBiodata::blankToNull($validated['graduation_year'] ?? null),
                'school_address' => ApplicantBiodata::blankToNull($validated['school_address'] ?? null),
            ];

            if ($schoolRequired) {
                $education = ApplicantEducation::schoolAttributes($validated) + $education;
            }

            if ($schoolRequired || $applicant->education || array_filter($education)) {
                $applicant->education()->updateOrCreate(['applicant_id' => $applicant->id], $education);
            }

            if ($saveParents) {
                $applicant->parentData()->updateOrCreate(['applicant_id' => $applicant->id], ApplicantBiodata::parentAttributes($validated));
            }

            if (ApplicantJourney::for($applicant->refresh())->biodataComplete() && $applicant->registration_status !== 'biodata_lengkap') {
                $applicant->update(['registration_status' => 'biodata_lengkap']);
            }
        });

        return redirect()
            ->route('admin.applicants.show', $applicant->registration_number)
            ->with('success', 'Biodata ' . $applicant->full_name . ' berhasil diperbarui oleh ' . $this->actorName() . '.');
    }

    private function find(string $registration_number): Applicant
    {
        return Applicant::query()
            ->with(['admissionWave', 'pmbYear', 'studyProgram', 'classType', 'address', 'education', 'parentData'])
            ->where('registration_number', $registration_number)
            ->firstOrFail();
    }

    private function hasAddress(Applicant $applicant): bool
    {
        return filled($applicant->address?->address);
    }

    private function hasSchool(Applicant $applicant): bool
    {
        return filled($applicant->education?->school_name);
    }
}
