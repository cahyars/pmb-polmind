<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ReRegistration;
use App\Models\StudyProgram;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $totalApplicants = Applicant::count();

        $biodataComplete = Applicant::where('registration_status', 'biodata_lengkap')->count();

        $incompleteApplicants = Applicant::where('registration_status', '!=', 'biodata_lengkap')->count();

        $validReRegistrations = ReRegistration::where('status', 'valid')->count();

        $waves = AdmissionWave::orderBy('id')->get();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $applicants = Applicant::query()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'classType',
                'education',
                'reRegistration',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhere('nisn', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%")
                                ->orWhere('school_npsn', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('wave'), function ($query) use ($request) {
                $query->where('admission_wave_id', $request->wave);
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('registration_status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.applicants.index', compact(
            'totalApplicants',
            'biodataComplete',
            'incompleteApplicants',
            'validReRegistrations',
            'waves',
            'studyPrograms',
            'applicants'
        ));
    }

    public function show(string $registration_number)
    {
        $applicant = Applicant::query()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'secondStudyProgram',
                'classType',
                'address',
                'education',
                'parentData',
                'documents.documentType',
                'registrationInvoice.items.feeComponent',
                'registrationInvoice.payments',
                'reRegistrationInvoice.items.feeComponent',
                'reRegistrationInvoice.payments',
                'selection',
                'reRegistration.invoice',
                'followUps',
                'latestFollowUp',
                'integrationLogs',
            ])
            ->where('registration_number', $registration_number)
            ->firstOrFail();

        return view('admin.applicants.show', compact('applicant'));
    }
}