<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\ReceiptNumber;

/**
 * Cetak invoice dan kwitansi camaba dari panel admin, memakai dokumen yang sama
 * dengan yang dicetak camaba (A4, bisa disimpan sebagai PDF dari dialog cetak).
 */
class PrintController extends Controller
{
    public function invoice(Invoice $invoice)
    {
        $invoice->syncRegistrationFee();
        $invoice->load(['items', 'payments', 'installments', 'applicant.pmbYear', 'applicant.studyProgram', 'applicant.classType']);

        return view('print.invoice', [
            'invoice' => $invoice,
            'paymentMethods' => $invoice->isPaid() ? collect() : PaymentMethod::active()->get(),
            'backUrl' => route('admin.payments.index'),
        ]);
    }

    public function receipt(Payment $payment)
    {
        abort_unless($payment->status === 'valid', 404, 'Kwitansi hanya tersedia untuk pembayaran yang sudah divalidasi.');

        ReceiptNumber::assign($payment);

        $payment->load(['invoice.items', 'invoice.payments', 'paymentMethod', 'applicant.pmbYear', 'applicant.studyProgram']);

        return view('print.kwitansi', [
            'payment' => $payment,
            'signer' => ReceiptNumber::signer(),
            'backUrl' => route('admin.payments.index'),
        ]);
    }
}
