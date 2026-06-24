<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\IntegrationLog;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $readySyncApplicants = Applicant::where('sync_status', 'siap_sinkron')->count();
        $processingApplicants = Applicant::where('sync_status', 'proses_sinkron')->count();
        $syncedApplicants = Applicant::where('sync_status', 'sudah_sinkron')->count();
        $failedApplicants = Applicant::where('sync_status', 'gagal_sinkron')->count();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $applicants = Applicant::query()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'classType',
                'address',
                'education',
                'parentData',
                'reRegistration',
                'latestFollowUp',
                'integrationLogs',
            ])
            ->whereIn('sync_status', [
                'siap_sinkron',
                'proses_sinkron',
                'sudah_sinkron',
                'gagal_sinkron',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhere('nisn', 'like', "%{$keyword}%")
                        ->orWhere('nim', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('sync_status'), function ($query) use ($request) {
                $query->where('sync_status', $request->sync_status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $latestLogs = IntegrationLog::query()
            ->with('applicant')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.integrations.index', compact(
            'readySyncApplicants',
            'processingApplicants',
            'syncedApplicants',
            'failedApplicants',
            'studyPrograms',
            'applicants',
            'latestLogs'
        ));
    }

    public function markProcessing(Applicant $applicant)
    {
        DB::transaction(function () use ($applicant) {
            $payload = $this->buildSiakadPayload($applicant);

            $applicant->update([
                'sync_status' => 'proses_sinkron',
            ]);

            IntegrationLog::create([
                'applicant_id' => $applicant->id,
                'system_name' => 'SIAKAD',
                'direction' => 'outbound',
                'endpoint' => '/api/pmb/applicants/' . $applicant->registration_number,
                'method' => 'GET',
                'status' => 'pending',
                'request_payload' => $payload,
                'response_payload' => null,
                'error_message' => null,
                'processed_at' => now(),
            ]);
        });

        return back()->with('success', 'Camaba berhasil ditandai sedang proses sinkron.');
    }

    public function markSynced(Request $request, Applicant $applicant)
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $payload = $this->buildSiakadPayload($applicant);

            $applicant->update([
                'sync_status' => 'sudah_sinkron',
                'nim' => $validated['nim'],
                'synced_at' => now(),
            ]);

            IntegrationLog::create([
                'applicant_id' => $applicant->id,
                'system_name' => 'SIAKAD',
                'direction' => 'inbound',
                'endpoint' => '/api/pmb/applicants/' . $applicant->registration_number . '/receive-nim',
                'method' => 'POST',
                'status' => 'success',
                'request_payload' => $payload,
                'response_payload' => [
                    'nim' => $validated['nim'],
                    'message' => 'Data berhasil disinkronkan ke SIAKAD.',
                ],
                'error_message' => null,
                'processed_at' => now(),
            ]);
        });

        return back()->with('success', 'Camaba berhasil ditandai sudah sinkron dan NIM tersimpan.');
    }

    public function markFailed(Request $request, Applicant $applicant)
    {
        $validated = $request->validate([
            'error_message' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $payload = $this->buildSiakadPayload($applicant);

            $applicant->update([
                'sync_status' => 'gagal_sinkron',
            ]);

            IntegrationLog::create([
                'applicant_id' => $applicant->id,
                'system_name' => 'SIAKAD',
                'direction' => 'outbound',
                'endpoint' => '/api/pmb/applicants/' . $applicant->registration_number,
                'method' => 'POST',
                'status' => 'failed',
                'request_payload' => $payload,
                'response_payload' => null,
                'error_message' => $validated['error_message'],
                'processed_at' => now(),
            ]);
        });

        return back()->with('success', 'Status sinkron camaba berhasil ditandai gagal.');
    }

    private function buildSiakadPayload(Applicant $applicant): array
    {
        $applicant->loadMissing([
            'pmbYear',
            'admissionWave',
            'studyProgram',
            'classType',
            'address',
            'education',
            'parentData',
        ]);

        return [
            'registration_number' => $applicant->registration_number,
            'full_name' => $applicant->full_name,
            'nik' => $applicant->nik,
            'nisn' => $applicant->nisn,
            'gender' => $applicant->gender,
            'birth_place' => $applicant->birth_place,
            'birth_date' => $applicant->birth_date?->format('Y-m-d'),
            'religion' => $applicant->religion,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'pmb_year' => [
                'code' => $applicant->pmbYear?->code,
                'year' => $applicant->pmbYear?->year,
                'name' => $applicant->pmbYear?->name,
            ],
            'admission_wave' => [
                'code' => $applicant->admissionWave?->code,
                'name' => $applicant->admissionWave?->name,
            ],
            'study_program' => [
                'code' => $applicant->studyProgram?->code,
                'name' => $applicant->studyProgram?->name,
                'degree' => $applicant->studyProgram?->degree,
            ],
            'class_type' => [
                'code' => $applicant->classType?->code,
                'name' => $applicant->classType?->name,
            ],
            'address' => [
                'address' => $applicant->address?->address,
                'province_code' => $applicant->address?->province_code,
                'province_name' => $applicant->address?->province_name,
                'regency_code' => $applicant->address?->regency_code,
                'regency_name' => $applicant->address?->regency_name,
                'district_code' => $applicant->address?->district_code,
                'district_name' => $applicant->address?->district_name,
                'village_code' => $applicant->address?->village_code,
                'village_name' => $applicant->address?->village_name,
                'postal_code' => $applicant->address?->postal_code,
                'rt' => $applicant->address?->rt,
                'rw' => $applicant->address?->rw,
            ],
            'education' => [
                'school_npsn' => $applicant->education?->school_npsn,
                'school_name' => $applicant->education?->school_name,
                'school_type' => $applicant->education?->school_type,
                'school_status' => $applicant->education?->school_status,
                'major' => $applicant->education?->major,
                'graduation_year' => $applicant->education?->graduation_year,
            ],
            'parent' => [
                'father_name' => $applicant->parentData?->father_name,
                'father_job' => $applicant->parentData?->father_job,
                'mother_name' => $applicant->parentData?->mother_name,
                'mother_job' => $applicant->parentData?->mother_job,
                'guardian_name' => $applicant->parentData?->guardian_name,
                'guardian_relation' => $applicant->parentData?->guardian_relation,
                'parent_income_range' => $applicant->parentData?->parent_income_range,
            ],
        ];
    }
}