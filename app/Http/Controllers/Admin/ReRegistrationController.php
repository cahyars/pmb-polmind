<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReRegistration;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $acceptedApplicants = Applicant::where('selection_status', 'diterima')->count();

        $waitingReRegistrations = ReRegistration::whereIn('status', [
            'belum_daftar_ulang',
            'menunggu_verifikasi',
        ])->count();

        $validReRegistrations = ReRegistration::where('status', 'valid')->count();

        $readySyncApplicants = Applicant::where('sync_status', 'siap_sinkron')->count();

        $partialReRegistrations = ReRegistration::whereHas('invoice', function ($query) {
            $query->where('status', 'partial');
        })->count();

        $totalPaidReRegistration = Payment::where('status', 'valid')
            ->whereHas('invoice', function ($query) {
                $query->where('type', 're_registration');
            })
            ->sum('amount');

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $reRegistrations = ReRegistration::query()
            ->with([
                'applicant.studyProgram',
                'applicant.classType',
                'applicant.education',
                'applicant.selection',
                'invoice.items',
                'invoice.payments',
                'invoice.latestPayment',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('applicant', function ($applicantQuery) use ($keyword) {
                        $applicantQuery->where('registration_number', 'like', "%{$keyword}%")
                            ->orWhere('full_name', 'like', "%{$keyword}%")
                            ->orWhere('nik', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                                $educationQuery->where('school_name', 'like', "%{$keyword}%")
                                    ->orWhere('school_npsn', 'like', "%{$keyword}%");
                            });
                    })
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($keyword) {
                        $invoiceQuery->where('invoice_number', 'like', "%{$keyword}%");
                    });
                });
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
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('invoice_status'), function ($query) use ($request) {
                $query->whereHas('invoice', function ($invoiceQuery) use ($request) {
                    $invoiceQuery->where('status', $request->invoice_status);
                });
            })
            ->when($request->filled('sync_status'), function ($query) use ($request) {
                $query->whereHas('applicant', function ($applicantQuery) use ($request) {
                    $applicantQuery->where('sync_status', $request->sync_status);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.re-registrations.index', compact(
            'acceptedApplicants',
            'waitingReRegistrations',
            'validReRegistrations',
            'readySyncApplicants',
            'partialReRegistrations',
            'totalPaidReRegistration',
            'studyPrograms',
            'reRegistrations'
        ));
    }

    public function validateReRegistration(ReRegistration $reRegistration)
    {
        $reRegistration->load(['applicant', 'invoice.payments']);

        if ($message = $this->guardCanValidate($reRegistration)) {
            return back()->withErrors($message);
        }

        DB::transaction(function () use ($reRegistration) {
            $reRegistration->load(['applicant', 'invoice.payments']);

            $reRegistration->update([
                'status' => 'valid',
                'validated_at' => now(),
                'validated_by_name' => 'Admin PMB',
                'ready_sync_at' => now(),
                'admin_note' => null,
            ]);

            $reRegistration->invoice->update([
                'status' => 'paid',
            ]);

            $reRegistration->applicant->update([
                're_registration_status' => 'daftar_ulang_valid',
                'sync_status' => 'siap_sinkron',
            ]);
        });

        return back()->with('success', 'Daftar ulang berhasil divalidasi dan camaba siap sinkron ke SIAKAD.');
    }

    public function reject(Request $request, ReRegistration $reRegistration)
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $reRegistration->load(['applicant', 'invoice.payments']);

        if ($message = $this->guardCanReject($reRegistration)) {
            return back()->withErrors($message);
        }

        DB::transaction(function () use ($reRegistration, $validated) {
            $reRegistration->load(['applicant', 'invoice.payments']);

            $reRegistration->update([
                'status' => 'ditolak',
                'validated_at' => now(),
                'validated_by_name' => 'Admin PMB',
                'ready_sync_at' => null,
                'admin_note' => $validated['admin_note'],
            ]);

            if ($reRegistration->invoice) {
                $reRegistration->invoice->update([
                    'status' => 'rejected',
                    'note' => $validated['admin_note'],
                ]);
            }

            $reRegistration->applicant->update([
                're_registration_status' => 'ditolak',
                'sync_status' => 'belum_siap',
            ]);
        });

        return back()->with('success', 'Daftar ulang berhasil ditolak dengan catatan.');
    }

    public function markReadySync(ReRegistration $reRegistration)
    {
        $reRegistration->load(['applicant', 'invoice.payments']);

        if ($reRegistration->status !== 'valid') {
            return back()->withErrors('Hanya daftar ulang yang sudah valid yang dapat ditandai siap sinkron.');
        }

        if ($message = $this->guardCanValidate($reRegistration)) {
            return back()->withErrors($message);
        }

        DB::transaction(function () use ($reRegistration) {
            $reRegistration->load('applicant');

            $reRegistration->update([
                'status' => 'valid',
                'ready_sync_at' => now(),
                'admin_note' => null,
            ]);

            $reRegistration->applicant->update([
                're_registration_status' => 'daftar_ulang_valid',
                'sync_status' => 'siap_sinkron',
            ]);
        });

        return back()->with('success', 'Camaba berhasil ditandai siap sinkron ke SIAKAD.');
    }

    private function guardCanValidate(ReRegistration $reRegistration): ?string
    {
        if (! $reRegistration->invoice) {
            return 'Invoice daftar ulang belum tersedia.';
        }

        $summary = $this->getPaymentSummary($reRegistration->invoice);

        if (! $summary['is_fully_paid']) {
            return 'Daftar ulang belum bisa divalidasi karena pembayaran valid belum lunas. Sisa tagihan: Rp' .
                number_format($summary['remaining_amount'], 0, ',', '.');
        }

        return null;
    }

    private function guardCanReject(ReRegistration $reRegistration): ?string
    {
        if (in_array($reRegistration->status, ['valid'])) {
            return 'Daftar ulang yang sudah valid tidak dapat ditolak.';
        }

        if (in_array($reRegistration->applicant?->sync_status, ['siap_sinkron', 'proses_sinkron', 'sudah_sinkron'])) {
            return 'Daftar ulang tidak dapat ditolak karena data sudah masuk proses sinkronisasi SIAKAD.';
        }

        if ($reRegistration->invoice) {
            $summary = $this->getPaymentSummary($reRegistration->invoice);

            if ($summary['valid_paid_amount'] > 0 || $summary['waiting_paid_amount'] > 0) {
                return 'Daftar ulang tidak dapat ditolak dari halaman ini karena sudah ada aktivitas pembayaran. Tolak/validasi pembayaran melalui menu Verifikasi Pembayaran.';
            }
        }

        return null;
    }

    private function getPaymentSummary(?Invoice $invoice): array
    {
        if (! $invoice) {
            return [
                'total_amount' => 0,
                'valid_paid_amount' => 0,
                'waiting_paid_amount' => 0,
                'remaining_amount' => 0,
                'is_fully_paid' => false,
            ];
        }

        $payments = $invoice->relationLoaded('payments')
            ? $invoice->payments
            : $invoice->payments()->get();

        $totalAmount = (float) $invoice->total_amount;

        $validPaidAmount = (float) $payments
            ->where('status', 'valid')
            ->sum('amount');

        $waitingPaidAmount = (float) $payments
            ->where('status', 'waiting_verification')
            ->sum('amount');

        $remainingAmount = max(0, $totalAmount - $validPaidAmount);

        return [
            'total_amount' => $totalAmount,
            'valid_paid_amount' => $validPaidAmount,
            'waiting_paid_amount' => $waitingPaidAmount,
            'remaining_amount' => $remainingAmount,
            'is_fully_paid' => $totalAmount <= 0 || $validPaidAmount >= $totalAmount,
        ];
    }
}