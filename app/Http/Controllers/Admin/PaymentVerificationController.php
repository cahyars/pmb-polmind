<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReRegistration;
use App\Models\StudyProgram;
use Illuminate\Http\Request;

class PaymentVerificationController extends Controller
{
    public function index(Request $request)
    {
        $waitingPayments = Payment::where('status', 'waiting_verification')->count();
        $validPayments = Payment::where('status', 'valid')->count();
        $rejectedPayments = Payment::where('status', 'rejected')->count();

        $totalValidAmount = Payment::where('status', 'valid')->sum('amount');
        $totalWaitingAmount = Payment::where('status', 'waiting_verification')->sum('amount');

        $partialInvoices = Invoice::where('status', 'partial')->count();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $payments = Payment::query()
            ->with([
                'applicant.studyProgram',
                'applicant.classType',
                'applicant.education',
                'invoice.items',
                'invoice.payments',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('payment_number', 'like', "%{$keyword}%")
                        ->orWhere('sender_name', 'like', "%{$keyword}%")
                        ->orWhere('sender_bank', 'like', "%{$keyword}%")
                        ->orWhereHas('invoice', function ($invoiceQuery) use ($keyword) {
                            $invoiceQuery->where('invoice_number', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('applicant', function ($applicantQuery) use ($keyword) {
                            $applicantQuery->where('registration_number', 'like', "%{$keyword}%")
                                ->orWhere('full_name', 'like', "%{$keyword}%")
                                ->orWhere('nik', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('invoice_type'), function ($query) use ($request) {
                $query->whereHas('invoice', function ($invoiceQuery) use ($request) {
                    $invoiceQuery->where('type', $request->invoice_type);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->whereHas('applicant', function ($applicantQuery) use ($request) {
                    $applicantQuery->where('study_program_id', $request->study_program);
                });
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->whereHas('applicant', function ($applicantQuery) use ($request) {
                    $applicantQuery->where('registration_path', $request->registration_path);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', compact(
            'waitingPayments',
            'validPayments',
            'rejectedPayments',
            'totalValidAmount',
            'totalWaitingAmount',
            'partialInvoices',
            'studyPrograms',
            'payments'
        ));
    }

    public function accept(Payment $payment)
    {
        if ($payment->status !== 'waiting_verification') {
            return back()->withErrors('Hanya pembayaran dengan status menunggu verifikasi yang dapat divalidasi.');
        }

        $payment->load(['invoice', 'applicant']);

        $payment->update([
            'status' => 'valid',
            'admin_note' => null,
            'verified_at' => now(),
            'verified_by_name' => 'Admin PMB',
        ]);

        $this->refreshPaymentStatus($payment);

        $payment->refresh();
        $payment->load('invoice');

        $totalValidPaid = Payment::where('invoice_id', $payment->invoice_id)
            ->where('status', 'valid')
            ->sum('amount');

        $isFullyPaid = (float) $totalValidPaid >= (float) $payment->invoice->total_amount;

        return back()->with(
            'success',
            $isFullyPaid
                ? 'Pembayaran berhasil divalidasi dan tagihan sudah lunas.'
                : 'Pembayaran berhasil divalidasi sebagai cicilan. Tagihan belum lunas.'
        );
    }

    public function reject(Request $request, Payment $payment)
    {
        if ($payment->status !== 'waiting_verification') {
            return back()->withErrors('Hanya pembayaran dengan status menunggu verifikasi yang dapat ditolak.');
        }

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $payment->load(['invoice', 'applicant']);

        $payment->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
            'verified_at' => now(),
            'verified_by_name' => 'Admin PMB',
        ]);

        $this->refreshPaymentStatus($payment, $validated['admin_note']);

        return back()->with('success', 'Pembayaran berhasil ditolak dengan catatan.');
    }

    private function refreshPaymentStatus(Payment $payment, ?string $adminNote = null): void
    {
        $payment->load(['invoice', 'applicant']);

        $totalValidPaid = Payment::where('invoice_id', $payment->invoice_id)
            ->where('status', 'valid')
            ->sum('amount');

        $hasWaitingPayment = Payment::where('invoice_id', $payment->invoice_id)
            ->where('status', 'waiting_verification')
            ->exists();

        $isFullyPaid = (float) $totalValidPaid >= (float) $payment->invoice->total_amount;
        $hasPartialPaid = (float) $totalValidPaid > 0;

        $invoiceStatus = 'unpaid';

        if ($isFullyPaid) {
            $invoiceStatus = 'paid';
        } elseif ($hasPartialPaid) {
            $invoiceStatus = 'partial';
        } elseif ($hasWaitingPayment) {
            $invoiceStatus = 'waiting_verification';
        } elseif ($payment->status === 'rejected') {
            $invoiceStatus = 'rejected';
        }

        $payment->invoice->update([
            'status' => $invoiceStatus,
        ]);

        if ($payment->invoice->type === 'registration') {
            $paymentStatus = 'belum_bayar';

            if ($isFullyPaid) {
                $paymentStatus = 'valid';
            } elseif ($hasWaitingPayment) {
                $paymentStatus = 'menunggu_verifikasi';
            } elseif (! $hasPartialPaid && $payment->status === 'rejected') {
                $paymentStatus = 'ditolak';
            }

            $payment->applicant->update([
                'payment_status' => $paymentStatus,
            ]);
        }

        if ($payment->invoice->type === 're_registration') {
            $reRegistrationStatus = 'belum_daftar_ulang';
            $syncStatus = 'belum_siap';

            if ($isFullyPaid) {
                $reRegistrationStatus = 'daftar_ulang_valid';
                $syncStatus = 'siap_sinkron';
            } elseif ($hasWaitingPayment) {
                $reRegistrationStatus = 'menunggu_verifikasi';
            }

            ReRegistration::updateOrCreate(
                ['applicant_id' => $payment->applicant_id],
                [
                    'invoice_id' => $payment->invoice_id,
                    'status' => $isFullyPaid ? 'valid' : $reRegistrationStatus,
                    'validated_at' => $isFullyPaid ? now() : null,
                    'validated_by_name' => $isFullyPaid ? 'Admin PMB' : null,
                    'ready_sync_at' => $isFullyPaid ? now() : null,
                    'admin_note' => $isFullyPaid
                        ? null
                        : ($adminNote ?? ($hasPartialPaid ? 'Pembayaran sudah masuk sebagian. Menunggu pelunasan sisa tagihan.' : null)),
                ]
            );

            $payment->applicant->update([
                're_registration_status' => $reRegistrationStatus,
                'sync_status' => $syncStatus,
            ]);
        }
    }
}