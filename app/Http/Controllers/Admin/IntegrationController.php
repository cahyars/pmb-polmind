<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Services\SiakadPayload;
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
                'acceptedStudyProgram',
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
                $query->whereEnrolledProgram($request->study_program);
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
            $payload = SiakadPayload::build($applicant);

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
            $payload = SiakadPayload::build($applicant);

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
            $payload = SiakadPayload::build($applicant);

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

        $missingFields = SiakadPayload::missingFields($applicant);

        if (! empty($missingFields)) {
            return 'Data belum lengkap untuk sinkron SIAKAD: ' . implode(', ', $missingFields) . '.';
        }

        return null;
    }
}
