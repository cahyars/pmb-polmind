<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ReRegistration;
use App\Models\Selection;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectionController extends Controller
{
    public function index(Request $request)
    {
        $readySelection = Applicant::where('document_status', 'valid')
            ->where('payment_status', 'valid')
            ->count();

        $acceptedApplicants = Applicant::where('selection_status', 'diterima')->count();
        $reserveApplicants = Applicant::where('selection_status', 'cadangan')->count();
        $rejectedApplicants = Applicant::where('selection_status', 'ditolak')->count();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $applicants = Applicant::query()
            ->with([
                'studyProgram',
                'classType',
                'education',
                'selection',
                'registrationInvoice',
                'reRegistrationInvoice',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%")
                                ->orWhere('school_npsn', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->where('registration_path', $request->registration_path);
            })
            ->when($request->filled('ready_status'), function ($query) use ($request) {
                if ($request->ready_status === 'ready') {
                    $query->where('document_status', 'valid')
                        ->where('payment_status', 'valid');
                }

                if ($request->ready_status === 'not_ready') {
                    $query->where(function ($q) {
                        $q->where('document_status', '!=', 'valid')
                            ->orWhere('payment_status', '!=', 'valid');
                    });
                }
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('selection_status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.selections.index', compact(
            'readySelection',
            'acceptedApplicants',
            'reserveApplicants',
            'rejectedApplicants',
            'studyPrograms',
            'applicants'
        ));
    }

    public function accept(Request $request, Applicant $applicant)
    {
        if ($message = $this->guardSelectionDecision($applicant, 'diterima')) {
            return back()->withErrors($message);
        }

        $validated = $request->validate([
            'test_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $finalScore = $this->calculateFinalScore(
                $validated['test_score'] ?? null,
                $validated['interview_score'] ?? null
            );

            Selection::updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'test_score' => $validated['test_score'] ?? null,
                    'interview_score' => $validated['interview_score'] ?? null,
                    'final_score' => $finalScore,
                    'status' => 'diterima',
                    'note' => $validated['note'] ?: 'Camaba dinyatakan diterima.',
                    'decided_at' => now(),
                    'decided_by_name' => 'Admin PMB',
                ]
            );

            $applicant->update([
                'selection_status' => 'diterima',
                're_registration_status' => 'belum_daftar_ulang',
                'sync_status' => 'belum_siap',
            ]);

            $this->createReRegistrationInvoice($applicant);
        });

        return back()->with('success', 'Camaba berhasil dinyatakan diterima dan invoice daftar ulang dibuat.');
    }

    public function reserve(Request $request, Applicant $applicant)
    {
        if ($message = $this->guardSelectionDecision($applicant, 'cadangan')) {
            return back()->withErrors($message);
        }

        $validated = $request->validate([
            'test_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $finalScore = $this->calculateFinalScore(
                $validated['test_score'] ?? null,
                $validated['interview_score'] ?? null
            );

            Selection::updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'test_score' => $validated['test_score'] ?? null,
                    'interview_score' => $validated['interview_score'] ?? null,
                    'final_score' => $finalScore,
                    'status' => 'cadangan',
                    'note' => $validated['note'] ?: 'Camaba masuk daftar cadangan.',
                    'decided_at' => now(),
                    'decided_by_name' => 'Admin PMB',
                ]
            );

            $applicant->update([
                'selection_status' => 'cadangan',
                're_registration_status' => 'belum_daftar_ulang',
                'sync_status' => 'belum_siap',
            ]);

            $this->cancelReRegistrationInvoice($applicant);
        });

        return back()->with('success', 'Camaba berhasil ditandai sebagai cadangan.');
    }

    public function reject(Request $request, Applicant $applicant)
    {
        if ($message = $this->guardSelectionDecision($applicant, 'ditolak')) {
            return back()->withErrors($message);
        }

        $validated = $request->validate([
            'test_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($applicant, $validated) {
            $finalScore = $this->calculateFinalScore(
                $validated['test_score'] ?? null,
                $validated['interview_score'] ?? null
            );

            Selection::updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'test_score' => $validated['test_score'] ?? null,
                    'interview_score' => $validated['interview_score'] ?? null,
                    'final_score' => $finalScore,
                    'status' => 'ditolak',
                    'note' => $validated['note'],
                    'decided_at' => now(),
                    'decided_by_name' => 'Admin PMB',
                ]
            );

            $applicant->update([
                'selection_status' => 'ditolak',
                're_registration_status' => 'belum_daftar_ulang',
                'sync_status' => 'belum_siap',
            ]);

            $this->cancelReRegistrationInvoice($applicant);
        });

        return back()->with('success', 'Camaba berhasil ditolak.');
    }

    private function guardSelectionDecision(Applicant $applicant, string $targetStatus): ?string
    {
        $applicant->loadMissing('reRegistrationInvoice');

        if ($applicant->document_status !== 'valid' || $applicant->payment_status !== 'valid') {
            return 'Camaba belum siap diseleksi. Pastikan berkas dan pembayaran pendaftaran sudah valid.';
        }

        $reRegistrationInvoice = $applicant->reRegistrationInvoice;

        if (
            $targetStatus !== 'diterima'
            && $reRegistrationInvoice
            && in_array($reRegistrationInvoice->status, ['waiting_verification', 'partial', 'paid'])
        ) {
            return 'Status seleksi tidak dapat diubah karena camaba sudah memiliki aktivitas pembayaran daftar ulang.';
        }

        return null;
    }

    private function calculateFinalScore($testScore, $interviewScore): ?float
    {
        if ($testScore === null && $interviewScore === null) {
            return null;
        }

        $testScore = $testScore ?? 0;
        $interviewScore = $interviewScore ?? 0;

        return round(($testScore * 0.6) + ($interviewScore * 0.4), 2);
    }

    private function createReRegistrationInvoice(Applicant $applicant): void
    {
        $fees = FeeComponent::where('type', 're_registration')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $totalAmount = $fees->sum('amount');

        $invoice = Invoice::where('applicant_id', $applicant->id)
            ->where('type', 're_registration')
            ->first();

        if (! $invoice) {
            $invoice = Invoice::create([
                'applicant_id' => $applicant->id,
                'invoice_number' => 'INV/DU/' . now()->format('Y') . '/' . $applicant->registration_number,
                'type' => 're_registration',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'note' => 'Invoice daftar ulang otomatis setelah camaba dinyatakan diterima.',
            ]);
        } elseif (! in_array($invoice->status, ['waiting_verification', 'partial', 'paid'])) {
            $invoice->update([
                'invoice_number' => $invoice->invoice_number ?: 'INV/DU/' . now()->format('Y') . '/' . $applicant->registration_number,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'note' => 'Invoice daftar ulang otomatis setelah camaba dinyatakan diterima.',
            ]);
        }

        foreach ($fees as $fee) {
            InvoiceItem::updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'fee_component_id' => $fee->id,
                ],
                [
                    'name' => $fee->name,
                    'amount' => $fee->amount,
                    'quantity' => 1,
                    'subtotal' => $fee->amount,
                    'sort_order' => $fee->sort_order,
                ]
            );
        }

        ReRegistration::updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'invoice_id' => $invoice->id,
                'status' => 'belum_daftar_ulang',
                'deadline_date' => now()->addDays(14)->toDateString(),
                'validated_at' => null,
                'validated_by_name' => null,
                'ready_sync_at' => null,
                'admin_note' => null,
            ]
        );
    }

    private function cancelReRegistrationInvoice(Applicant $applicant): void
    {
        $invoice = Invoice::where('applicant_id', $applicant->id)
            ->where('type', 're_registration')
            ->first();

        if ($invoice && ! in_array($invoice->status, ['waiting_verification', 'partial', 'paid'])) {
            $invoice->update([
                'status' => 'cancelled',
                'note' => 'Invoice daftar ulang dibatalkan karena status seleksi bukan diterima.',
            ]);
        }

        ReRegistration::where('applicant_id', $applicant->id)->update([
            'status' => 'belum_daftar_ulang',
            'ready_sync_at' => null,
        ]);
    }
}