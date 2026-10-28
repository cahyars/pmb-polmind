<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\DocumentType;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentVerificationController extends Controller
{
    public function index(Request $request)
    {
        $waitingDocuments = ApplicantDocument::where('status', 'menunggu_verifikasi')->count();
        $acceptedDocuments = ApplicantDocument::where('status', 'diterima')->count();
        $rejectedDocuments = ApplicantDocument::where('status', 'ditolak')->count();
        $notUploadedDocuments = ApplicantDocument::where('status', 'belum_upload')->count();

        $requiredDocumentTypeIds = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->pluck('id');

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        // Satu baris per camaba yang sudah mengunggah berkas; yang punya berkas menunggu verifikasi tampil paling atas.
        $applicants = Applicant::query()
            ->whereHas('documents', fn ($query) => $query->whereNotNull('file_path'))
            ->with([
                'studyProgram',
                'classType',
                'education',
                'documents' => fn ($query) => $query->whereNotNull('file_path')->with('documentType'),
            ])
            ->withMax(['documents as last_uploaded_at' => fn ($query) => $query->whereNotNull('file_path')], 'uploaded_at')
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
                        })
                        ->orWhereHas('documents', function ($documentQuery) use ($keyword) {
                            $documentQuery->where('file_name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                match ($request->status) {
                    'menunggu_verifikasi' => $query->whereHas('documents', fn ($q) => $q->where('status', 'menunggu_verifikasi')),
                    'ditolak' => $query->whereHas('documents', fn ($q) => $q->where('status', 'ditolak')),
                    'selesai' => $query->whereDoesntHave('documents', fn ($q) => $q->where('status', 'menunggu_verifikasi')),
                    default => null,
                };
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->where('registration_path', $request->registration_path);
            })
            ->orderByDesc(function ($query) {
                $query->from('applicant_documents')
                    ->selectRaw('count(*) > 0')
                    ->whereColumn('applicant_documents.applicant_id', 'applicants.id')
                    ->where('applicant_documents.status', 'menunggu_verifikasi');
            })
            ->orderByDesc('last_uploaded_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.documents.index', compact(
            'waitingDocuments',
            'acceptedDocuments',
            'rejectedDocuments',
            'notUploadedDocuments',
            'requiredDocumentTypeIds',
            'studyPrograms',
            'applicants'
        ));
    }

    public function show(string $registrationNumber)
    {
        $applicant = Applicant::where('registration_number', $registrationNumber)
            ->with([
                'studyProgram',
                'classType',
                'admissionWave',
                'education',
                'photoDocument',
                'documents.documentType',
            ])
            ->firstOrFail();

        $documents = $applicant->documents->keyBy('document_type_id');

        // Jenis berkas aktif, ditambah jenis nonaktif yang terlanjur diunggah agar tetap bisa diverifikasi.
        $documentTypes = DocumentType::where('is_active', true)
            ->orWhereIn('id', $applicant->documents->whereNotNull('file_path')->pluck('document_type_id'))
            ->orderBy('sort_order')
            ->get();

        $requiredCount = $documentTypes->where('is_active', true)->where('is_required', true)->count();

        $acceptedRequiredCount = $applicant->documents
            ->filter(fn ($document) => $document->documentType?->is_active
                && $document->documentType?->is_required
                && $document->status === 'diterima')
            ->count();

        $waitingCount = $applicant->documents->where('status', 'menunggu_verifikasi')->count();

        return view('admin.documents.show', compact(
            'applicant',
            'documents',
            'documentTypes',
            'requiredCount',
            'acceptedRequiredCount',
            'waitingCount'
        ));
    }

    public function acceptAll(string $registrationNumber)
    {
        $applicant = Applicant::where('registration_number', $registrationNumber)->firstOrFail();

        $waitingDocuments = $applicant->documents()
            ->where('status', 'menunggu_verifikasi')
            ->get();

        if ($waitingDocuments->isEmpty()) {
            return back()->withErrors('Tidak ada berkas yang menunggu verifikasi.');
        }

        $acceptable = $waitingDocuments->filter(
            fn ($document) => $document->file_path && Storage::disk('local')->exists($document->file_path)
        );

        if ($acceptable->isEmpty()) {
            return back()->withErrors('File berkas tidak ditemukan di storage. Tidak ada berkas yang diterima.');
        }

        DB::transaction(function () use ($applicant, $acceptable) {
            foreach ($acceptable as $document) {
                $document->update([
                    'status' => 'diterima',
                    'admin_note' => null,
                    'verified_at' => now(),
                    'verified_by_name' => $this->actorName(),
                ]);
            }

            $this->refreshApplicantDocumentStatus($applicant);
        });

        $skipped = $waitingDocuments->count() - $acceptable->count();

        return back()->with('success', $acceptable->count() . ' berkas berhasil diterima.'
            . ($skipped > 0 ? " {$skipped} berkas dilewati karena file tidak ditemukan di storage." : ''));
    }

    public function accept(ApplicantDocument $document)
    {
        if ($document->status !== 'menunggu_verifikasi') {
            return back()->withErrors('Hanya berkas yang menunggu verifikasi yang dapat diterima.');
        }

        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            return back()->withErrors('File berkas tidak ditemukan di storage. Berkas tidak dapat diterima.');
        }

        DB::transaction(function () use ($document) {
            $document->update([
                'status' => 'diterima',
                'admin_note' => null,
                'verified_at' => now(),
                'verified_by_name' => $this->actorName(),
            ]);

            $this->refreshApplicantDocumentStatus($document->applicant);
        });

        return back()->with('success', 'Berkas berhasil diterima.');
    }

    public function reject(Request $request, ApplicantDocument $document)
    {
        if ($document->status !== 'menunggu_verifikasi') {
            return back()->withErrors('Hanya berkas yang menunggu verifikasi yang dapat ditolak.');
        }

        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            return back()->withErrors('File berkas tidak ditemukan di storage. Berkas tidak dapat ditolak.');
        }

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($document, $validated) {
            $document->update([
                'status' => 'ditolak',
                'admin_note' => $validated['admin_note'],
                'verified_at' => now(),
                'verified_by_name' => $this->actorName(),
            ]);

            $this->refreshApplicantDocumentStatus($document->applicant);
        });

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