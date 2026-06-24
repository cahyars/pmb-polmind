<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\DocumentType;
use App\Models\StudyProgram;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    public function index(Request $request)
    {
        $waitingDocuments = ApplicantDocument::where('status', 'menunggu_verifikasi')->count();
        $acceptedDocuments = ApplicantDocument::where('status', 'diterima')->count();
        $rejectedDocuments = ApplicantDocument::where('status', 'ditolak')->count();
        $notUploadedDocuments = ApplicantDocument::where('status', 'belum_upload')->count();

        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $documents = ApplicantDocument::query()
            ->with([
                'applicant.studyProgram',
                'applicant.classType',
                'applicant.education',
                'documentType',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('file_name', 'like', "%{$keyword}%")
                        ->orWhereHas('applicant', function ($applicantQuery) use ($keyword) {
                            $applicantQuery->where('registration_number', 'like', "%{$keyword}%")
                                ->orWhere('full_name', 'like', "%{$keyword}%")
                                ->orWhere('nik', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('document_type'), function ($query) use ($request) {
                $query->where('document_type_id', $request->document_type);
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->whereHas('applicant', function ($applicantQuery) use ($request) {
                    $applicantQuery->where('study_program_id', $request->study_program);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.documents.index', compact(
            'waitingDocuments',
            'acceptedDocuments',
            'rejectedDocuments',
            'notUploadedDocuments',
            'documentTypes',
            'studyPrograms',
            'documents'
        ));
    }

    public function accept(ApplicantDocument $document)
    {
        $document->update([
            'status' => 'diterima',
            'admin_note' => null,
            'verified_at' => now(),
            'verified_by_name' => 'Admin PMB',
        ]);

        $this->refreshApplicantDocumentStatus($document->applicant);

        return back()->with('success', 'Berkas berhasil diterima.');
    }

    public function reject(Request $request, ApplicantDocument $document)
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $document->update([
            'status' => 'ditolak',
            'admin_note' => $validated['admin_note'],
            'verified_at' => now(),
            'verified_by_name' => 'Admin PMB',
        ]);

        $this->refreshApplicantDocumentStatus($document->applicant);

        return back()->with('success', 'Berkas berhasil ditolak dengan catatan.');
    }

    private function refreshApplicantDocumentStatus(Applicant $applicant): void
    {
        $requiredDocumentTypeIds = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->pluck('id');

        $requiredCount = $requiredDocumentTypeIds->count();

        $acceptedRequiredCount = $applicant->documents()
            ->whereIn('document_type_id', $requiredDocumentTypeIds)
            ->where('status', 'diterima')
            ->count();

        $waitingCount = $applicant->documents()
            ->where('status', 'menunggu_verifikasi')
            ->count();

        $rejectedCount = $applicant->documents()
            ->where('status', 'ditolak')
            ->count();

        $uploadedCount = $applicant->documents()
            ->whereNotNull('file_path')
            ->count();

        $status = 'belum_upload';

        if ($requiredCount > 0 && $acceptedRequiredCount >= $requiredCount) {
            $status = 'valid';
        } elseif ($waitingCount > 0) {
            $status = 'menunggu_verifikasi';
        } elseif ($rejectedCount > 0) {
            $status = 'ditolak';
        } elseif ($uploadedCount > 0) {
            $status = 'sebagian_upload';
        }

        $applicant->update([
            'document_status' => $status,
        ]);
    }
}