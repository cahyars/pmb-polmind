<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudyProgram;
use App\Services\InvoiceStatus;
use App\Services\ReceiptNumber;
use App\Services\SequenceRewinder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'paymentMethod',
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

        DB::transaction(function () use ($payment) {
            $payment->load(['invoice', 'applicant']);

            $payment->update([
                'status' => 'valid',
                'admin_note' => null,
                'verified_at' => now(),
                'verified_by_name' => $this->actorName(),
            ]);

            ReceiptNumber::assign($payment);

            InvoiceStatus::refresh($payment->invoice, $this->actorName());
        });

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

        DB::transaction(function () use ($payment, $validated) {
            $payment->load(['invoice', 'applicant']);

            $payment->update([
                'status' => 'rejected',
                'admin_note' => $validated['admin_note'],
                'verified_at' => now(),
                'verified_by_name' => $this->actorName(),
            ]);

            InvoiceStatus::refresh($payment->invoice, $this->actorName(), $validated['admin_note']);
        });

        return back()->with('success', 'Pembayaran berhasil ditolak dengan catatan.');
    }

    /**
     * Hapus riwayat pembayaran yang ditolak beserta bukti transfernya. Nomor
     * pembayaran dimundurkan agar bisa dipakai lagi.
     */
    public function destroy(Payment $payment)
    {
        if ($payment->status !== 'rejected') {
            return back()->withErrors('Hanya pembayaran yang ditolak yang dapat dihapus.');
        }

        DB::transaction(function () use ($payment) {
            $payment->delete();

            InvoiceStatus::refresh($payment->invoice, $this->actorName());
        });

        if ($payment->proof_file_path) {
            Storage::disk('local')->delete($payment->proof_file_path);
        }

        SequenceRewinder::rewind($payment->applicant->pmbYear);

        return back()->with('success', 'Pembayaran ' . $payment->payment_number . ' yang ditolak berhasil dihapus.');
    }
}
