<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\IntegrationLog;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                'selection',
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
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('nim', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%")
                                ->orWhere('school_npsn', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->where('registration_path', $request->registration_path);
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
        $applicant->load([
            'studyProgram',
            'classType',
            'address',
            'education',
            'parentData',
            'selection',
            'reRegistration',
        ]);

        if ($message = $this->guardCanProcess($applicant)) {
            return back()->withErrors($message);
        }

        DB::transaction(function () use ($applicant) {
            $payload = $this->buildSiakadPayload($applicant);

            $applicant->update([
                'sync_status' => 'proses_sinkron',
            ]);

            IntegrationLog::create([
                'applicant_id' => $applicant->id,
                'system_name' => 'SIAKAD',
                'direction' => 'outbound',
                'endpoint' => '/api/siakad/students',
                'method' => 'POST',
                'status' => 'pending',
                'request_payload' => $payload,
                'response_payload' => null,
                'error_message' => null,
                'processed_at' => now(),
            ]);
        });

        return back()->with('success', 'Camaba berhasil ditandai sedang proses sinkron ke SIAKAD.');
    }

    public function markSynced(Request $request, Applicant $applicant)
    {
        if ($applicant->sync_status !== 'proses_sinkron') {
            return back()->withErrors('Status camaba harus proses sinkron sebelum ditandai sukses.');
        }

        $validated = $request->validate([
            'nim' => [
                'required',
                'string',
                'max:50',
                Rule::unique('applicants', 'nim')->ignore($applicant->id),
            ],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $payload = $this->buildSiakadPayload($applicant);

            $applicant->update([
                'sync_status' => 'sudah_sinkron',
                'nim' => $validated['nim'],
                'synced_at' => now(),
            ]);

            if ($applicant->reRegistration) {
                $applicant->reRegistration->update([
                    'status' => 'valid',
                    'ready_sync_at' => $applicant->reRegistration->ready_sync_at ?? now(),
                ]);
            }

            IntegrationLog::create([
                'applicant_id' => $applicant->id,
                'system_name' => 'SIAKAD',
                'direction' => 'inbound',
                'endpoint' => '/api/siakad/students/receive-nim',
                'method' => 'POST',
                'status' => 'success',
                'request_payload' => $payload,
                'response_payload' => [
                    'registration_number' => $applicant->registration_number,
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
        if ($applicant->sync_status === 'sudah_sinkron') {
            return back()->withErrors('Camaba yang sudah sinkron tidak dapat ditandai gagal.');
        }

        if (! in_array($applicant->sync_status, ['siap_sinkron', 'proses_sinkron', 'gagal_sinkron'])) {
            return back()->withErrors('Status camaba belum memenuhi syarat untuk ditandai gagal sinkron.');
        }

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
                'endpoint' => '/api/siakad/students',
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

    private function guardCanProcess(Applicant $applicant): ?string
    {
        if (! in_array($applicant->sync_status, ['siap_sinkron', 'gagal_sinkron'])) {
            return 'Hanya camaba dengan status siap sinkron atau gagal sinkron yang dapat diproses.';
        }

        if ($applicant->selection_status !== 'diterima') {
            return 'Camaba belum berstatus diterima.';
        }

        if ($applicant->re_registration_status !== 'daftar_ulang_valid') {
            return 'Daftar ulang camaba belum valid.';
        }

        if (! $applicant->reRegistration || $applicant->reRegistration->status !== 'valid') {
            return 'Data re-registration belum valid.';
        }

        $missingFields = $this->missingRequiredSiakadData($applicant);

        if (! empty($missingFields)) {
            return 'Data belum lengkap untuk sinkron SIAKAD: ' . implode(', ', $missingFields) . '.';
        }

        return null;
    }

    private function missingRequiredSiakadData(Applicant $applicant): array
    {
        $checks = [
            'NIK' => $applicant->nik,
            'Nama Lengkap' => $applicant->full_name,
            'Jenis Kelamin' => $applicant->gender,
            'Tempat Lahir' => $applicant->birth_place,
            'Tanggal Lahir' => $applicant->birth_date,
            'Nomor HP' => $applicant->phone,
            'Program Studi' => $applicant->studyProgram?->code,
            'Jenis Kelas' => $applicant->classType?->code,
            'Asal Sekolah' => $applicant->education?->school_name,
            'Tahun Lulus' => $applicant->education?->graduation_year,
        ];

        return collect($checks)
            ->filter(fn ($value) => blank($value))
            ->keys()
            ->values()
            ->all();
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
            'selection',
            'reRegistration',
        ]);

        return [
            'registration_number' => $applicant->registration_number,
            'registration_path' => $applicant->registration_path,
            'registration_path_label' => $applicant->registration_path_label,
            'nim' => $applicant->nim,
            'full_name' => $applicant->full_name,
            'nik' => $applicant->nik,
            'nisn' => $applicant->nisn,
            'gender' => $applicant->gender,
            'birth_place' => $applicant->birth_place,
            'birth_date' => $applicant->birth_date?->format('Y-m-d'),
            'religion' => $applicant->religion,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'sync_status' => $applicant->sync_status,
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
                'father_phone' => $applicant->parentData?->father_phone,
                'mother_name' => $applicant->parentData?->mother_name,
                'mother_job' => $applicant->parentData?->mother_job,
                'mother_phone' => $applicant->parentData?->mother_phone,
                'guardian_name' => $applicant->parentData?->guardian_name,
                'guardian_relation' => $applicant->parentData?->guardian_relation,
                'guardian_phone' => $applicant->parentData?->guardian_phone,
                'parent_income_range' => $applicant->parentData?->parent_income_range,
            ],
            'selection' => [
                'status' => $applicant->selection?->status,
                'test_score' => $applicant->selection?->test_score,
                'interview_score' => $applicant->selection?->interview_score,
                'final_score' => $applicant->selection?->final_score,
                'decided_at' => $applicant->selection?->decided_at?->format('Y-m-d H:i:s'),
            ],
            're_registration' => [
                'status' => $applicant->reRegistration?->status,
                'deadline_date' => $applicant->reRegistration?->deadline_date?->format('Y-m-d'),
                'validated_at' => $applicant->reRegistration?->validated_at?->format('Y-m-d H:i:s'),
                'ready_sync_at' => $applicant->reRegistration?->ready_sync_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }
}