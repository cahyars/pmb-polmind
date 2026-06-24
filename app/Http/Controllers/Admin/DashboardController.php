<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\IntegrationLog;
use App\Models\Payment;
use App\Models\ReRegistration;
use App\Models\Selection;
use App\Models\StudyProgram;

class DashboardController extends Controller
{
    public function index()
    {
        $totalApplicants = Applicant::count();

        $pendingDocuments = ApplicantDocument::where('status', 'menunggu_verifikasi')->count();

        $pendingPayments = Payment::where('status', 'waiting_verification')->count();

        $validReRegistrations = ReRegistration::where('status', 'valid')->count();

        $acceptedApplicants = Selection::where('status', 'diterima')->count();

        $readySyncApplicants = Applicant::where('sync_status', 'siap_sinkron')
            ->orWhereHas('reRegistration', function ($query) {
                $query->where('status', 'valid');
            })
            ->count();

        $targetApplicants = 180;

        $targetProgress = $targetApplicants > 0
            ? round(($totalApplicants / $targetApplicants) * 100)
            : 0;

        $funnel = [
            [
                'label' => 'Registrasi Awal',
                'value' => Applicant::count(),
            ],
            [
                'label' => 'Biodata Lengkap',
                'value' => Applicant::where('registration_status', 'biodata_lengkap')->count(),
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
        ];

        $funnel = collect($funnel)->map(function ($item) use ($totalApplicants) {
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
                    $query->whereHas('selection', function ($selectionQuery) {
                        $selectionQuery->where('status', 'diterima');
                    });
                },
                'applicants as re_registered_count' => function ($query) {
                    $query->whereHas('reRegistration', function ($reRegistrationQuery) {
                        $reRegistrationQuery->where('status', 'valid');
                    });
                },
            ])
            ->get();

        $latestApplicants = Applicant::query()
            ->with(['studyProgram', 'classType'])
            ->latest()
            ->limit(5)
            ->get();

        $latestPayments = Payment::query()
            ->with(['applicant', 'invoice'])
            ->latest()
            ->limit(5)
            ->get();

        $latestIntegrationLogs = IntegrationLog::query()
            ->with('applicant')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalApplicants',
            'pendingDocuments',
            'pendingPayments',
            'validReRegistrations',
            'acceptedApplicants',
            'readySyncApplicants',
            'targetApplicants',
            'targetProgress',
            'funnel',
            'programs',
            'latestApplicants',
            'latestPayments',
            'latestIntegrationLogs'
        ));
    }
}