<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Services\ApplicantJourney;
use App\Services\LoginInfoMessage;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->registrationInvoice?->syncRegistrationFee();

        $applicant->load([
            'pmbYear',
            'admissionWave',
            'studyProgram',
            'secondStudyProgram',
            'classType',
            'address',
            'education',
            'parentData',
            'documents.documentType',
            'photoDocument',
            'registrationInvoice.items',
            'registrationInvoice.payments',
            'registrationInvoice.latestPayment',
            'reRegistrationInvoice.items',
            'reRegistrationInvoice.payments',
            'reRegistrationInvoice.latestPayment',
            'selection',
            'reRegistration',
            'latestFollowUp',
        ]);

        $journey = ApplicantJourney::for($applicant);

        return view('camaba.dashboard', [
            'applicant' => $applicant,
            'journey' => $journey,
            'steps' => $journey->steps(),
            'nextStep' => $journey->nextStep(),
            'biodataSections' => $journey->biodataSections(),
            'registrationInvoice' => $applicant->registrationInvoice,
            'reRegistrationInvoice' => $applicant->reRegistrationInvoice,
            'usingDefaultPin' => LoginInfoMessage::activePin($applicant) !== null,
        ]);
    }
}