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

        $payment->invoice->update([
            'status' => 'paid',
        ]);

        if ($payment->invoice->type === 'registration') {
            $payment->applicant->update([
                'payment_status' => 'valid',
            ]);
        }

        if ($payment->invoice->type === 're_registration') {
            ReRegistration::updateOrCreate(
                ['applicant_id' => $payment->applicant_id],
                [
                    'invoice_id' => $payment->invoice_id,
                    'status' => 'valid',
                    'validated_at' => now(),
                    'validated_by_name' => 'Admin PMB',
                    'ready_sync_at' => now(),
                    'admin_note' => null,
                ]
            );

            $payment->applicant->update([
                're_registration_status' => 'daftar_ulang_valid',
                'sync_status' => 'siap_sinkron',
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil divalidasi.');
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

        $payment->invoice->update([
            'status' => 'rejected',
        ]);

        if ($payment->invoice->type === 'registration') {
            $payment->applicant->update([
                'payment_status' => 'ditolak',
            ]);
        }

        if ($payment->invoice->type === 're_registration') {
            ReRegistration::updateOrCreate(
                ['applicant_id' => $payment->applicant_id],
                [
                    'invoice_id' => $payment->invoice_id,
                    'status' => 'ditolak',
                    'validated_at' => now(),
                    'validated_by_name' => 'Admin PMB',
                    'admin_note' => $validated['admin_note'],
                ]
            );

            $payment->applicant->update([
                're_registration_status' => 'ditolak',
                'sync_status' => 'belum_siap',
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil ditolak dengan catatan.');
    }
}