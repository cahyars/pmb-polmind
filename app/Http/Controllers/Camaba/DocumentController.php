<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $applicant->load([
            'documents.documentType',
        ]);

        $documents = $applicant->documents->keyBy('document_type_id');

        $requiredCount = $documentTypes->where('is_required', true)->count();

        $acceptedRequiredCount = $applicant->documents
            ->filter(function ($document) {
                return $document->documentType?->is_required && $document->status === 'diterima';
            })
            ->count();

        $documentProgress = $requiredCount > 0
            ? round(($acceptedRequiredCount / $requiredCount) * 100)
            : 0;

        return view('camaba.upload-berkas', compact(
            'applicant',
            'documentTypes',
            'documents',
            'requiredCount',
            'acceptedRequiredCount',
            'documentProgress'
        ));
    }

    public function store(Request $request)
    {
        $applicant = Auth::guard('applicant')->user();

        $validated = $request->validate([
            'document_type_id' => [
                'required',
                Rule::exists('document_types', 'id')->where('is_active', true),
            ],
            'file' => ['required', 'file'],
        ]);

        $documentType = DocumentType::findOrFail($validated['document_type_id']);

        // Batasi ekstensi berkas secara ketat hanya format dokumen/gambar yang aman.
        $safeExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowedExtensions = ! empty($documentType->allowed_extensions)
            ? array_values(array_intersect(array_map('strtolower', (array) $documentType->allowed_extensions), $safeExtensions))
            : $safeExtensions;

        $maxSizeKb = $documentType->max_size_kb ?? 2048;

        $rules = [
            'file' => [
                'required',
                'file',
                'max:' . $maxSizeKb,
                'mimes:' . implode(',', $allowedExtensions),
            ],
        ];

        $request->validate($rules, [
            'file.max' => 'Ukuran file ' . $documentType->name . ' maksimal ' . number_format($maxSizeKb, 0, ',', '.') . ' KB.',
            'file.mimes' => 'Format file ' . $documentType->name . ' harus ' . strtoupper(implode(', ', $allowedExtensions)) . '.',
        ]);

        $file = $request->file('file');

        $existingDocument = ApplicantDocument::where('applicant_id', $applicant->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        if ($existingDocument?->file_path && Storage::disk('local')->exists($existingDocument->file_path)) {
            Storage::disk('local')->delete($existingDocument->file_path);
        }

        $folder = 'applicant-documents/' . $applicant->registration_number;

        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $safeCode = Str::slug($documentType->code, '_');
        $filename = $safeCode . '_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $extension;

        $filePath = $file->storeAs($folder, $filename, 'local');

        DB::transaction(function () use ($applicant, $documentType, $file, $filePath, $extension) {
            ApplicantDocument::updateOrCreate(
                [
                    'applicant_id' => $applicant->id,
                    'document_type_id' => $documentType->id,
                ],
                [
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_extension' => $extension,
                    'file_size_kb' => ceil($file->getSize() / 1024),
                    'status' => 'menunggu_verifikasi',
                    'admin_note' => null,
                    'uploaded_at' => now(),
                    'verified_at' => null,
                    'verified_by_name' => null,
                ]
            );

            $this->refreshApplicantDocumentStatus($applicant);
        });

        return back()->with('success', 'Unggah berkas ' . $documentType->name . ' berhasil disimpan.');
    }

    public function destroy(ApplicantDocument $document)
    {
        $applicant = Auth::guard('applicant')->user();

        abort_if($document->applicant_id !== $applicant->id, 403);

        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        DB::transaction(function () use ($document, $applicant) {
            $document->update([
                'file_name' => null,
                'file_path' => null,
                'file_extension' => null,
                'file_size_kb' => null,
                'status' => 'belum_upload',
                'admin_note' => null,
                'uploaded_at' => null,
                'verified_at' => null,
                'verified_by_name' => null,
            ]);

            $this->refreshApplicantDocumentStatus($applicant);
        });

        return back()->with('success', 'Berkas berhasil dihapus.');
    }

    private function refreshApplicantDocumentStatus(Applicant $applicant): void
    {
        $requiredDocumentTypes = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->get();

        $requiredCount = $requiredDocumentTypes->count();

        $documents = ApplicantDocument::with('documentType')
            ->where('applicant_id', $applicant->id)
            ->get();

        $acceptedRequiredCount = $documents
            ->filter(function ($document) {
                return $document->documentType?->is_required && $document->status === 'diterima';
            })
            ->count();

        $waitingCount = $documents->where('status', 'menunggu_verifikasi')->count();
        $rejectedCount = $documents->where('status', 'ditolak')->count();

        $uploadedCount = $documents
            ->filter(fn ($document) => ! empty($document->file_path))
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