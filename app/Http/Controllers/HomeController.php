<?php

namespace App\Http\Controllers;

use App\Models\AdmissionWave;
use App\Models\Brochure;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\PmbYear;
use App\Models\StudyProgram;

class HomeController extends Controller
{
    /**
     * Landing page PMB untuk calon mahasiswa.
     */
    public function index()
    {
        $pmbYear = PmbYear::where('is_active', true)->first();

        $currentWave = AdmissionWave::current($pmbYear);

        // Jika belum ada gelombang yang dibuka, tampilkan gelombang terdekat yang akan datang.
        $nextWave = $currentWave || ! $pmbYear
            ? null
            : AdmissionWave::where('pmb_year_id', $pmbYear->id)
                ->where('status', '!=', 'closed')
                ->whereDate('start_date', '>', now()->toDateString())
                ->orderBy('start_date')
                ->first();

        // Semua informasi di landing page diambil dari Master Data agar selalu sinkron.
        return view('public.home', [
            'pmbYear' => $pmbYear,
            'currentWave' => $currentWave,
            'nextWave' => $nextWave,
            'waves' => $pmbYear
                ? AdmissionWave::with('selectionSchedules')
                    ->where('pmb_year_id', $pmbYear->id)
                    ->orderBy('start_date')
                    ->orderBy('id')
                    ->get()
                : collect(),
            'registrationFee' => FeeComponent::registrationFee()?->amount,
            'reRegistrationFees' => FeeComponent::where('type', 're_registration')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'studyPrograms' => StudyProgram::where('is_active', true)->orderBy('code')->get(),
            'documentTypes' => DocumentType::where('is_active', true)->orderBy('sort_order')->get(),
            'classTypes' => ClassType::where('is_active', true)->orderBy('code')->get(),
            'brochures' => Brochure::published()->get(),
        ]);
    }
}
