<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    public function index()
    {
        $activePmbYear = PmbYear::where('is_active', true)->first();

        $pmbYears = PmbYear::query()
            ->orderByDesc('year')
            ->get();

        $admissionWaves = AdmissionWave::query()
            ->with('pmbYear')
            ->orderByDesc('id')
            ->get();

        $studyPrograms = StudyProgram::query()
            ->orderBy('code')
            ->get();

        $classTypes = ClassType::query()
            ->orderBy('code')
            ->get();

        $documentTypes = DocumentType::query()
            ->orderBy('sort_order')
            ->get();

        $feeComponents = FeeComponent::query()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return view('admin.master-data.index', compact(
            'activePmbYear',
            'pmbYears',
            'admissionWaves',
            'studyPrograms',
            'classTypes',
            'documentTypes',
            'feeComponents'
        ));
    }

    public function storeStudyProgram(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:study_programs,code'],
            'name' => ['required', 'string', 'max:255'],
            'degree' => ['required', 'string', 'max:20'],
            'quota' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        StudyProgram::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'degree' => strtoupper($validated['degree']),
            'quota' => $validated['quota'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function updateStudyProgram(Request $request, StudyProgram $studyProgram)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:study_programs,code,' . $studyProgram->id],
            'name' => ['required', 'string', 'max:255'],
            'degree' => ['required', 'string', 'max:20'],
            'quota' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $studyProgram->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'degree' => strtoupper($validated['degree']),
            'quota' => $validated['quota'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function toggleStudyProgram(StudyProgram $studyProgram)
    {
        $studyProgram->update([
            'is_active' => ! $studyProgram->is_active,
        ]);

        return back()->with('success', 'Status program studi berhasil diubah.');
    }

    public function storeFeeComponent(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:fee_components,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:registration,re_registration'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        FeeComponent::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Komponen biaya berhasil ditambahkan.');
    }

    public function updateFeeComponent(Request $request, FeeComponent $feeComponent)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:fee_components,code,' . $feeComponent->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:registration,re_registration'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $feeComponent->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Komponen biaya berhasil diperbarui.');
    }

    public function toggleFeeComponent(FeeComponent $feeComponent)
    {
        $feeComponent->update([
            'is_active' => ! $feeComponent->is_active,
        ]);

        return back()->with('success', 'Status komponen biaya berhasil diubah.');
    }

    public function storePmbYear(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:pmb_years,code'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed,archived'],
        ]);

        if ($request->boolean('is_active')) {
            PmbYear::query()->update(['is_active' => false]);
        }

        PmbYear::create([
            'code' => strtoupper($validated['code']),
            'year' => $validated['year'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tahun PMB berhasil ditambahkan.');
    }

    public function updatePmbYear(Request $request, PmbYear $pmbYear)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:pmb_years,code,' . $pmbYear->id],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed,archived'],
        ]);

        if ($request->boolean('is_active')) {
            PmbYear::where('id', '!=', $pmbYear->id)->update(['is_active' => false]);
        }

        $pmbYear->update([
            'code' => strtoupper($validated['code']),
            'year' => $validated['year'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tahun PMB berhasil diperbarui.');
    }

    public function togglePmbYear(PmbYear $pmbYear)
    {
        if (! $pmbYear->is_active) {
            PmbYear::query()->update(['is_active' => false]);

            $pmbYear->update([
                'is_active' => true,
                'status' => 'active',
            ]);
        } else {
            $pmbYear->update([
                'is_active' => false,
            ]);
        }

        return back()->with('success', 'Status aktif Tahun PMB berhasil diubah.');
    }

    public function storeAdmissionWave(Request $request)
    {
        $validated = $request->validate([
            'pmb_year_id' => ['required', 'exists:pmb_years,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admission_waves', 'code')
                    ->where(fn ($query) => $query->where('pmb_year_id', $request->pmb_year_id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_fee' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,open,closed'],
        ]);

        if ($request->boolean('is_active')) {
            AdmissionWave::where('pmb_year_id', $validated['pmb_year_id'])
                ->update(['is_active' => false]);
        }

        AdmissionWave::create([
            'pmb_year_id' => $validated['pmb_year_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'registration_fee' => $validated['registration_fee'],
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Gelombang pendaftaran berhasil ditambahkan.');
    }

    public function updateAdmissionWave(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $request->validate([
            'pmb_year_id' => ['required', 'exists:pmb_years,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admission_waves', 'code')
                    ->where(fn ($query) => $query->where('pmb_year_id', $request->pmb_year_id))
                    ->ignore($admissionWave->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_fee' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,open,closed'],
        ]);

        if ($request->boolean('is_active')) {
            AdmissionWave::where('pmb_year_id', $validated['pmb_year_id'])
                ->where('id', '!=', $admissionWave->id)
                ->update(['is_active' => false]);
        }

        $admissionWave->update([
            'pmb_year_id' => $validated['pmb_year_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'registration_fee' => $validated['registration_fee'],
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Gelombang pendaftaran berhasil diperbarui.');
    }

    public function toggleAdmissionWave(AdmissionWave $admissionWave)
    {
        if (! $admissionWave->is_active) {
            AdmissionWave::where('pmb_year_id', $admissionWave->pmb_year_id)
                ->update(['is_active' => false]);

            $admissionWave->update([
                'is_active' => true,
                'status' => 'open',
            ]);
        } else {
            $admissionWave->update([
                'is_active' => false,
            ]);
        }

        return back()->with('success', 'Status aktif gelombang berhasil diubah.');
    }

    public function storeClassType(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:class_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        ClassType::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis kelas berhasil ditambahkan.');
    }

    public function updateClassType(Request $request, ClassType $classType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:class_types,code,' . $classType->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $classType->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis kelas berhasil diperbarui.');
    }

    public function toggleClassType(ClassType $classType)
    {
        $classType->update([
            'is_active' => ! $classType->is_active,
        ]);

        return back()->with('success', 'Status jenis kelas berhasil diubah.');
    }

    public function storeDocumentType(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'allowed_extensions' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['required', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DocumentType::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'is_required' => $request->boolean('is_required'),
            'allowed_extensions' => $this->parseExtensions($validated['allowed_extensions'] ?? null),
            'max_size_kb' => $validated['max_size_kb'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis berkas berhasil ditambahkan.');
    }

    public function updateDocumentType(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code,' . $documentType->id],
            'name' => ['required', 'string', 'max:255'],
            'allowed_extensions' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['required', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $documentType->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'is_required' => $request->boolean('is_required'),
            'allowed_extensions' => $this->parseExtensions($validated['allowed_extensions'] ?? null),
            'max_size_kb' => $validated['max_size_kb'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis berkas berhasil diperbarui.');
    }

    public function toggleDocumentType(DocumentType $documentType)
    {
        $documentType->update([
            'is_active' => ! $documentType->is_active,
        ]);

        return back()->with('success', 'Status jenis berkas berhasil diubah.');
    }

    private function parseExtensions(?string $extensions): array
    {
        if (! $extensions) {
            return [];
        }

        return collect(explode(',', strtolower($extensions)))
            ->map(fn ($extension) => trim($extension))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}