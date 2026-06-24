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
}