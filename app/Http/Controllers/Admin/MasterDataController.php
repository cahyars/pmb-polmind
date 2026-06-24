<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\PmbYear;
use App\Models\StudyProgram;

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
}