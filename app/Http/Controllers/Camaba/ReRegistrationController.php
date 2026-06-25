<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReRegistrationController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->load([
            'pmbYear',
            'admissionWave',
            'studyProgram',
            'classType',
            'selection',
            'reRegistration',
            'reRegistrationInvoice.items',
            'reRegistrationInvoice.latestPayment',
            'reRegistrationInvoice.payments',
        ]);

        $reRegistration = $applicant->reRegistration;
        $invoice = $applicant->reRegistrationInvoice;
        $latestPayment = $invoice?->latestPayment;

        return view('camaba.daftar-ulang', compact(
            'applicant',
            'reRegistration',
            'invoice',
            'latestPayment'
        ));
    }
}