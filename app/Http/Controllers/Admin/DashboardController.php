<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\IntegrationLog;
use App\Models\Payment;
use App\Models\ReRegistration;
use App\Models\StudyProgram;

class DashboardController extends Controller
{
    public function index()
    {
        $totalApplicants = Applicant::count();

        $biodataComplete = Applicant::where('registration_status', 'biodata_lengkap')->count();

        $pendingDocuments = ApplicantDocument::where('status', 'menunggu_verifikasi')->count();

        $pendingPayments = Payment::where('status', 'waiting_verification')->count();

        $validReRegistrations = ReRegistration::where('status', 'valid')->count();

        $acceptedApplicants = Applicant::where('selection_status', 'diterima')->count();

        $readySyncApplicants = Applicant::where('sync_status', 'siap_sinkron')->count();
        $processingSyncApplicants = Applicant::where('sync_status', 'proses_sinkron')->count();
        $syncedApplicants = Applicant::where('sync_status', 'sudah_sinkron')->count();
        $failedSyncApplicants = Applicant::where('sync_status', 'gagal_sinkron')->count();

        $totalValidPayment = Payment::where('status', 'valid')->sum('amount');

        $registrationPathStats = [
            'umum' => Applicant::where('registration_path', 'umum')->count(),
            'prestasi' => Applicant::where('registration_path', 'prestasi')->count(),
            'undangan' => Applicant::where('registration_path', 'undangan')->count(),
        ];

        $targetApplicants = 180;

        $targetProgress = $targetApplicants > 0
            ? round(($totalApplicants / $targetApplicants) * 100)
            : 0;

        $funnel = collect([
            [
                'label' => 'Registrasi Awal',
                'value' => $totalApplicants,
            ],
            [
                'label' => 'Biodata Lengkap',
                'value' => $biodataComplete,
            ],
            [
                'label' => 'Berkas Valid',
                'value' => Applicant::where('document_status', 'valid')->count(),
            ],
            [
                'label' => 'Pembayaran Valid',
                'value' => Applicant::where('payment_status', 'valid')->count(),
            ],
            [
                'label' => 'Diterima',
                'value' => $acceptedApplicants,
            ],
            [
                'label' => 'Daftar Ulang Valid',
                'value' => $validReRegistrations,
            ],
            [
                'label' => 'Sudah Sinkron',
                'value' => $syncedApplicants,
            ],
        ])->map(function ($item) use ($totalApplicants) {
            $item['percent'] = $totalApplicants > 0
                ? round(($item['value'] / $totalApplicants) * 100)
                : 0;

            return $item;
        });

        $programs = StudyProgram::query()
            ->where('is_active', true)
            ->withCount([
                'applicants as registrants_count',
                'applicants as accepted_count' => function ($query) {
                    $query->where('selection_status', 'diterima');
                },
                'applicants as re_registered_count' => function ($query) {
                    $query->where('re_registration_status', 'daftar_ulang_valid');
                },
                'applicants as synced_count' => function ($query) {
                    $query->where('sync_status', 'sudah_sinkron');
                },
            ])
            ->orderBy('code')
            ->get();

        $latestApplicants = Applicant::query()
            ->with(['studyProgram', 'classType'])
            ->latest()
            ->limit(5)
            ->get();

        $latestPayments = Payment::query()
            ->with(['applicant.studyProgram', 'invoice'])
            ->latest()
            ->limit(5)
            ->get();

        $latestIntegrationLogs = IntegrationLog::query()
            ->with('applicant')
            ->latest()
            ->limit(5)
            ->get();

        $latestDocuments = ApplicantDocument::query()
            ->with(['applicant.studyProgram', 'documentType'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalApplicants',
            'biodataComplete',
            'pendingDocuments',
            'pendingPayments',
            'validReRegistrations',
            'acceptedApplicants',
            'readySyncApplicants',
            'processingSyncApplicants',
            'syncedApplicants',
            'failedSyncApplicants',
            'totalValidPayment',
            'registrationPathStats',
            'targetApplicants',
            'targetProgress',
            'funnel',
            'programs',
            'latestApplicants',
            'latestPayments',
            'latestIntegrationLogs',
            'latestDocuments'
        ));
    }
}