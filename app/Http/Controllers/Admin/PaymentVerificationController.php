<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $payments = Payment::query()
            ->with([
                'applicant.studyProgram',
                'applicant.classType',
                'applicant.education',
                'invoice.items',
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
                                ->orWhere('nik', 'like', "%{$keyword}%");
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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', compact(
            'waitingPayments',
            'validPayments',
            'rejectedPayments',
            'totalValidAmount',
            'studyPrograms',
            'payments'
        ));
    }

    public function accept(Payment $payment)
    {
        $payment->load(['invoice', 'applicant']);

        $payment->update([
            'status' => 'valid',
            'admin_note' => null,
            'verified_at' => now(),
            'verified_by_name' => 'Admin PMB',
        ]);

        $totalValidPaid = Payment::where('invoice_id', $payment->invoice_id)
            ->where('status', 'valid')
            ->sum('amount');

        $isFullyPaid = (float) $totalValidPaid >= (float) $payment->invoice->total_amount;

        $payment->invoice->update([
            'status' => $isFullyPaid ? 'paid' : 'partial',
        ]);

        if ($payment->invoice->type === 'registration') {
            $payment->applicant->update([
                'payment_status' => $isFullyPaid ? 'valid' : 'belum_bayar',
            ]);
        }

        if ($payment->invoice->type === 're_registration') {
            ReRegistration::updateOrCreate(
                ['applicant_id' => $payment->applicant_id],
                [
                    'invoice_id' => $payment->invoice_id,
                    'status' => $isFullyPaid ? 'valid' : 'belum_daftar_ulang',
                    'validated_at' => $isFullyPaid ? now() : null,
                    'validated_by_name' => $isFullyPaid ? 'Admin PMB' : null,
                    'ready_sync_at' => $isFullyPaid ? now() : null,
                    'admin_note' => $isFullyPaid ? null : 'Pembayaran sudah masuk sebagian. Menunggu pelunasan sisa tagihan.',
                ]
            );

            $payment->applicant->update([
                're_registration_status' => $isFullyPaid ? 'daftar_ulang_valid' : 'belum_daftar_ulang',
                'sync_status' => $isFullyPaid ? 'siap_sinkron' : 'belum_siap',
            ]);
        }

        return back()->with(
            'success',
            $isFullyPaid
                ? 'Pembayaran berhasil divalidasi dan tagihan sudah lunas.'
                : 'Pembayaran berhasil divalidasi sebagai pembayaran sebagian. Tagihan belum lunas.'
        );
    }

    public function reject(Request $request, Payment $payment)
    {
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

        $totalValidPaid = Payment::where('invoice_id', $payment->invoice_id)
            ->where('status', 'valid')
            ->sum('amount');

        $isFullyPaid = (float) $totalValidPaid >= (float) $payment->invoice->total_amount;
        $hasPartialPaid = (float) $totalValidPaid > 0;

        $payment->invoice->update([
            'status' => $isFullyPaid ? 'paid' : ($hasPartialPaid ? 'partial' : 'rejected'),
        ]);

        if ($payment->invoice->type === 'registration') {
            $payment->applicant->update([
                'payment_status' => $isFullyPaid ? 'valid' : 'ditolak',
            ]);
        }

        if ($payment->invoice->type === 're_registration') {
            ReRegistration::updateOrCreate(
                ['applicant_id' => $payment->applicant_id],
                [
                    'invoice_id' => $payment->invoice_id,
                    'status' => $isFullyPaid ? 'valid' : 'belum_daftar_ulang',
                    'validated_at' => $isFullyPaid ? now() : null,
                    'validated_by_name' => $isFullyPaid ? 'Admin PMB' : null,
                    'ready_sync_at' => $isFullyPaid ? now() : null,
                    'admin_note' => $validated['admin_note'],
                ]
            );

            $payment->applicant->update([
                're_registration_status' => $isFullyPaid ? 'daftar_ulang_valid' : 'belum_daftar_ulang',
                'sync_status' => $isFullyPaid ? 'siap_sinkron' : 'belum_siap',
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil ditolak dengan catatan.');
    }
}