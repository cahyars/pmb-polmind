<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\Payment;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $baseApplicants = $this->filteredApplicants($request);

        $totalApplicants = (clone $baseApplicants)->count();
        $biodataComplete = (clone $baseApplicants)->where('registration_status', 'biodata_lengkap')->count();
        $documentValid = (clone $baseApplicants)->where('document_status', 'valid')->count();
        $paymentValid = (clone $baseApplicants)->where('payment_status', 'valid')->count();
        $acceptedApplicants = (clone $baseApplicants)->where('selection_status', 'diterima')->count();
        $reRegistered = (clone $baseApplicants)->where('re_registration_status', 'daftar_ulang_valid')->count();
        $readySync = (clone $baseApplicants)->where('sync_status', 'siap_sinkron')->count();
        $synced = (clone $baseApplicants)->where('sync_status', 'sudah_sinkron')->count();

        $targetApplicants = 180;
        $targetProgress = $targetApplicants > 0
            ? round(($totalApplicants / $targetApplicants) * 100)
            : 0;

        $funnel = collect([
            ['label' => 'Registrasi Awal', 'value' => $totalApplicants],
            ['label' => 'Biodata Lengkap', 'value' => $biodataComplete],
            ['label' => 'Berkas Valid', 'value' => $documentValid],
            ['label' => 'Pembayaran Valid', 'value' => $paymentValid],
            ['label' => 'Diterima', 'value' => $acceptedApplicants],
            ['label' => 'Daftar Ulang Valid', 'value' => $reRegistered],
            ['label' => 'Siap Sinkron SIAKAD', 'value' => $readySync],
        ])->map(function ($item) use ($totalApplicants) {
            $item['percent'] = $totalApplicants > 0
                ? round(($item['value'] / $totalApplicants) * 100)
                : 0;

            return $item;
        });

        $programReports = $studyPrograms->map(function ($program) use ($request) {
            $query = $this->filteredApplicants($request)
                ->where('study_program_id', $program->id);

            $registrants = (clone $query)->count();
            $accepted = (clone $query)->where('selection_status', 'diterima')->count();
            $reRegistered = (clone $query)->where('re_registration_status', 'daftar_ulang_valid')->count();
            $readySync = (clone $query)->where('sync_status', 'siap_sinkron')->count();

            $quota = max($program->quota, 1);

            return [
                'code' => $program->code,
                'name' => $program->name,
                'quota' => $program->quota,
                'registrants' => $registrants,
                'accepted' => $accepted,
                're_registered' => $reRegistered,
                'ready_sync' => $readySync,
                'quota_percent' => round(($reRegistered / $quota) * 100),
            ];
        });

        $classReports = ClassType::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($classType) use ($request) {
                $query = $this->filteredApplicants($request)
                    ->where('class_type_id', $classType->id);

                return [
                    'code' => $classType->code,
                    'name' => $classType->name,
                    'registrants' => (clone $query)->count(),
                    'accepted' => (clone $query)->where('selection_status', 'diterima')->count(),
                    're_registered' => (clone $query)->where('re_registration_status', 'daftar_ulang_valid')->count(),
                ];
            });

        $sourceReports = $this->filteredApplicants($request)
            ->select('source_information', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_information')
            ->groupBy('source_information')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $paymentQuery = Payment::query()
            ->whereHas('applicant', function ($query) use ($request) {
                $this->applyApplicantFilters($query, $request);
            });

        $waitingPayments = (clone $paymentQuery)->where('status', 'waiting_verification')->count();
        $validPayments = (clone $paymentQuery)->where('status', 'valid')->count();
        $rejectedPayments = (clone $paymentQuery)->where('status', 'rejected')->count();

        $totalPaid = (clone $paymentQuery)->where('status', 'valid')->sum('amount');

        $registrationPaid = (clone $paymentQuery)
            ->where('status', 'valid')
            ->whereHas('invoice', function ($query) {
                $query->where('type', 'registration');
            })
            ->sum('amount');

        $reRegistrationPaid = (clone $paymentQuery)
            ->where('status', 'valid')
            ->whereHas('invoice', function ($query) {
                $query->where('type', 're_registration');
            })
            ->sum('amount');

        $latestApplicants = $this->filteredApplicants($request)
            ->with(['studyProgram', 'classType'])
            ->latest()
            ->limit(5)
            ->get();

        $latestPayments = Payment::query()
            ->with(['applicant.studyProgram', 'invoice'])
            ->whereHas('applicant', function ($query) use ($request) {
                $this->applyApplicantFilters($query, $request);
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.reports.index', compact(
            'studyPrograms',
            'totalApplicants',
            'biodataComplete',
            'documentValid',
            'paymentValid',
            'acceptedApplicants',
            'reRegistered',
            'readySync',
            'synced',
            'targetApplicants',
            'targetProgress',
            'funnel',
            'programReports',
            'classReports',
            'sourceReports',
            'waitingPayments',
            'validPayments',
            'rejectedPayments',
            'totalPaid',
            'registrationPaid',
            'reRegistrationPaid',
            'latestApplicants',
            'latestPayments'
        ));
    }

    private function filteredApplicants(Request $request)
    {
        $query = Applicant::query();

        $this->applyApplicantFilters($query, $request);

        return $query;
    }

    private function applyApplicantFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->end_date);
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            });
    }
}