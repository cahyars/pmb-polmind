<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BiodataController extends Controller
{
    public function edit()
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

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('camaba.biodata', compact('applicant', 'studyPrograms'));
    }

    public function update(Request $request)
    {
        $applicant = Auth::guard('applicant')->user();

        $validated = $request->validate([
            // Data diri
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('applicants', 'email')->ignore($applicant->id),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'nik' => [
                'required',
                'string',
                'max:30',
                Rule::unique('applicants', 'nik')->ignore($applicant->id),
            ],
            'nisn' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'in:male,female'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'religion' => ['required', 'string', 'max:50'],
            'second_study_program_id' => ['nullable', 'exists:study_programs,id'],

            // Alamat
            'address' => ['required', 'string', 'max:1000'],
            'province_code' => ['nullable', 'string', 'max:50'],
            'province_name' => ['required', 'string', 'max:100'],
            'regency_code' => ['nullable', 'string', 'max:50'],
            'regency_name' => ['required', 'string', 'max:100'],
            'district_code' => ['nullable', 'string', 'max:50'],
            'district_name' => ['required', 'string', 'max:100'],
            'village_code' => ['nullable', 'string', 'max:50'],
            'village_name' => ['required', 'string', 'max:100'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            // Pendidikan
            'school_npsn' => ['nullable', 'string', 'max:50'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', 'string', 'max:50'],
            'school_status' => ['nullable', 'string', 'max:50'],
            'major' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:2000', 'max:2100'],

            // Orang tua / wali
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_job' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_job' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relation' => ['nullable', 'string', 'max:100'],
            'parent_income_range' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $applicant->update([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'nik' => $validated['nik'],
                'nisn' => $validated['nisn'] ?? null,
                'gender' => $validated['gender'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'religion' => $validated['religion'],
                'second_study_program_id' => $validated['second_study_program_id'] ?? null,
                'registration_status' => 'biodata_lengkap',
            ]);

            $applicant->address()->updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'address' => $validated['address'],
                    'province_code' => $validated['province_code'] ?? null,
                    'province_name' => $validated['province_name'],
                    'regency_code' => $validated['regency_code'] ?? null,
                    'regency_name' => $validated['regency_name'],
                    'district_code' => $validated['district_code'] ?? null,
                    'district_name' => $validated['district_name'],
                    'village_code' => $validated['village_code'] ?? null,
                    'village_name' => $validated['village_name'],
                    'rt' => $validated['rt'] ?? null,
                    'rw' => $validated['rw'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                ]
            );

            $applicant->education()->updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'school_npsn' => $validated['school_npsn'] ?? null,
                    'school_name' => $validated['school_name'],
                    'school_type' => $validated['school_type'],
                    'school_status' => $validated['school_status'] ?? null,
                    'major' => $validated['major'],
                    'graduation_year' => $validated['graduation_year'],
                ]
            );

            $applicant->parentData()->updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'father_name' => $validated['father_name'] ?? null,
                    'father_job' => $validated['father_job'] ?? null,
                    'mother_name' => $validated['mother_name'] ?? null,
                    'mother_job' => $validated['mother_job'] ?? null,
                    'guardian_name' => $validated['guardian_name'] ?? null,
                    'guardian_relation' => $validated['guardian_relation'] ?? null,
                    'parent_income_range' => $validated['parent_income_range'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('camaba.biodata.edit')
            ->with('success', 'Biodata berhasil disimpan.');
    }
}