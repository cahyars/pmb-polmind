<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SelectionStatusController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->load([
            'pmbYear',
            'admissionWave',
            'studyProgram',
            'secondStudyProgram',
            'classType',
            'selection',
            'registrationInvoice',
            'reRegistrationInvoice.items',
            'reRegistrationInvoice.latestPayment',
            'reRegistration',
        ]);

        return view('camaba.status-seleksi', compact('applicant'));
    }

    public function announcement()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->load([
            'pmbYear',
            'admissionWave',
            'studyProgram',
            'classType',
            'selection',
            'reRegistrationInvoice.items',
            'reRegistration',
        ]);

        return view('camaba.pengumuman-seleksi', compact('applicant'));
    }
}