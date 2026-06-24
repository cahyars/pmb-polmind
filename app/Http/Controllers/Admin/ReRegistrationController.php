<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
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

                $query->whereHas('applicant', function ($applicantQuery) use ($keyword) {
                    $applicantQuery->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%");
                        });
                })->orWhereHas('invoice', function ($invoiceQuery) use ($keyword) {
                    $invoiceQuery->where('invoice_number', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->whereHas('applicant', function ($applicantQuery) use ($request) {
                    $applicantQuery->where('study_program_id', $request->study_program);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.re-registrations.index', compact(
            'acceptedApplicants',
            'waitingReRegistrations',
            'validReRegistrations',
            'readySyncApplicants',
            'studyPrograms',
            'reRegistrations'
        ));
    }

    public function validateReRegistration(ReRegistration $reRegistration)
    {
        DB::transaction(function () use ($reRegistration) {
            $reRegistration->load(['applicant', 'invoice.latestPayment']);

            $reRegistration->update([
                'status' => 'valid',
                'validated_at' => now(),
                'validated_by_name' => 'Admin PMB',
                'ready_sync_at' => now(),
                'admin_note' => null,
            ]);

            if ($reRegistration->invoice) {
                $reRegistration->invoice->update([
                    'status' => 'paid',
                ]);
            }

            if ($reRegistration->invoice?->latestPayment) {
                $reRegistration->invoice->latestPayment->update([
                    'status' => 'valid',
                    'verified_at' => now(),
                    'verified_by_name' => 'Admin PMB',
                    'admin_note' => null,
                ]);
            }

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

        DB::transaction(function () use ($reRegistration, $validated) {
            $reRegistration->load(['applicant', 'invoice.latestPayment']);

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

            if ($reRegistration->invoice?->latestPayment) {
                $reRegistration->invoice->latestPayment->update([
                    'status' => 'rejected',
                    'verified_at' => now(),
                    'verified_by_name' => 'Admin PMB',
                    'admin_note' => $validated['admin_note'],
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
        DB::transaction(function () use ($reRegistration) {
            $reRegistration->load('applicant');

            $reRegistration->update([
                'status' => 'valid',
                'ready_sync_at' => now(),
            ]);

            $reRegistration->applicant->update([
                're_registration_status' => 'daftar_ulang_valid',
                'sync_status' => 'siap_sinkron',
            ]);
        });

        return back()->with('success', 'Camaba berhasil ditandai siap sinkron ke SIAKAD.');
    }
}