<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\ReceiptNumber;
use Illuminate\Support\Facades\Auth;

/**
 * Dokumen yang dapat dicetak sendiri oleh camaba: kartu ujian, invoice, dan kwitansi.
 * Halaman dirancang untuk A4 dan dapat disimpan sebagai PDF lewat dialog cetak browser.
 */
class PrintController extends Controller
{
    public function examCard()
    {
        $applicant = Auth::guard('applicant')->user();

        abort_unless(
            $applicant->canPrintExamCard(),
            403,
            'Kartu ujian dapat dicetak setelah pembayaran formulir valid dan biodata lengkap.'
        );

        $applicant->load([
            'pmbYear',
            'admissionWave.selectionSchedules.component',
            'studyProgram',
            'secondStudyProgram',
            'classType',
            'education',
            'photoDocument',
        ]);

        return view('print.kartu-ujian', compact('applicant'));
    }

    public function invoice(Invoice $invoice)
    {
        $applicant = Auth::guard('applicant')->user();

        abort_unless($invoice->applicant_id === $applicant->id, 404);

        $invoice->syncRegistrationFee();
        $invoice->load(['items', 'payments', 'applicant.pmbYear', 'applicant.studyProgram', 'applicant.classType']);

        $paymentMethods = $invoice->isPaid() ? collect() : PaymentMethod::active()->get();

        return view('print.invoice', compact('invoice', 'paymentMethods'));
    }

    public function receipt(Payment $payment)
    {
        $applicant = Auth::guard('applicant')->user();

        abort_unless($payment->applicant_id === $applicant->id && $payment->status === 'valid', 404);

        ReceiptNumber::assign($payment);

        $payment->load(['invoice.items', 'invoice.payments', 'paymentMethod', 'applicant.pmbYear', 'applicant.studyProgram']);

        return view('print.kwitansi', compact('payment'));
    }
}
