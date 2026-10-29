<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SelectAdminPmbYear;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use App\Services\NimGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Kelulusan > Generate NIM: camaba diterima yang daftar ulangnya valid diberi NIM
 * (otomatis sesuai format atau manual), lalu datanya siap ditarik SIAKAD.
 */
class NimController extends Controller
{
    public const STATUSES = [
        'belum_daftar_ulang' => 'Belum daftar ulang',
        'siap_nim' => 'Siap diberi NIM',
        'sudah_nim' => 'Sudah ada NIM',
        'sudah_sinkron' => 'Sudah ditarik SIAKAD',
    ];

    /**
     * NIM tidak boleh diubah lagi setelah datanya diproses atau ditarik SIAKAD.
     */
    private const LOCKED_SYNC_STATUSES = ['proses_sinkron', 'sudah_sinkron'];

    public function index(Request $request)
    {
        $pmbYear = $this->selectedPmbYear($request);

        $accepted = Applicant::where('selection_status', 'diterima');

        $applicants = (clone $accepted)
            ->with(['studyProgram', 'acceptedStudyProgram', 'classType', 'admissionWave'])
            ->when($request->filled('wave'), fn ($query) => $query->where('admission_wave_id', $request->wave))
            ->when($request->filled('registration_path'), fn ($query) => $query->where('registration_path', $request->registration_path))
            ->when($request->filled('class_type'), fn ($query) => $query->where('class_type_id', $request->class_type))
            ->when($request->filled('study_program'), fn ($query) => $query->whereEnrolledProgram($request->study_program))
            ->when($request->filled('status'), fn ($query) => match ($request->status) {
                'belum_daftar_ulang' => $query->where('re_registration_status', '!=', 'daftar_ulang_valid'),
                'siap_nim' => $query->where('re_registration_status', 'daftar_ulang_valid')->whereNull('nim'),
                'sudah_nim' => $query->whereNotNull('nim'),
                'sudah_sinkron' => $query->where('sync_status', 'sudah_sinkron'),
                default => $query,
            })
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(fn ($q) => $q->where('registration_number', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%")
                    ->orWhere('nim', 'like', "%{$keyword}%"));
            })
            ->orderBy('registration_number')
            ->paginate(25)
            ->withQueryString();

        $eligible = $pmbYear ? NimGenerator::eligible($pmbYear) : collect();

        return view('admin.nim.index', [
            'applicants' => $applicants,
            'eligible' => $eligible,
            'missingNimCode' => $eligible->filter(fn ($row) => blank($row['applicant']->enrolled_study_program?->nim_code))
                ->map(fn ($row) => $row['applicant']->enrolled_study_program?->code ?? '-')
                ->unique()
                ->values(),
            'stats' => [
                'accepted' => (clone $accepted)->count(),
                'reRegistered' => (clone $accepted)->where('re_registration_status', 'daftar_ulang_valid')->count(),
                'withNim' => (clone $accepted)->whereNotNull('nim')->count(),
                'synced' => (clone $accepted)->where('sync_status', 'sudah_sinkron')->count(),
            ],
            'nimExample' => NimGenerator::example(year: $pmbYear?->year),
            'waves' => AdmissionWave::orderBy('start_date')->orderBy('id')->get(),
            'classTypes' => ClassType::orderBy('code')->get(),
            'studyPrograms' => StudyProgram::where('is_active', true)->orderBy('code')->get(),
            'statuses' => self::STATUSES,
            'lockedSyncStatuses' => self::LOCKED_SYNC_STATUSES,
        ]);
    }

    /**
     * Bubuhkan NIM ke semua camaba yang siap, berurutan dari tanggal daftar ulang paling awal.
     */
    public function generate(Request $request)
    {
        $pmbYear = $this->selectedPmbYear($request);

        if (! $pmbYear) {
            return back()->withErrors('Pilih Tahun PMB terlebih dahulu.');
        }

        ['generated' => $generated, 'skipped' => $skipped] = NimGenerator::generate($pmbYear);

        if ($generated->isEmpty() && $skipped->isEmpty()) {
            return back()->withErrors('Belum ada camaba yang daftar ulangnya valid dan belum memiliki NIM.');
        }

        if ($skipped->isNotEmpty()) {
            $programs = $skipped->map(fn ($applicant) => $applicant->enrolled_study_program?->code ?? '-')->unique()->implode(', ');

            $message = $generated->count() . ' NIM berhasil dibuat. ' . $skipped->count()
                . ' camaba dilewati karena kode NIM prodi (' . $programs . ') belum diisi di Master Data Program Studi.';

            return $generated->isEmpty() ? back()->withErrors($message) : back()->with('success', $message);
        }

        return back()->with('success', $generated->count() . ' NIM berhasil dibuat sesuai urutan tanggal daftar ulang. Data siap ditarik SIAKAD.');
    }

    public function generateOne(Request $request, Applicant $applicant)
    {
        if ($error = $this->guardAssignable($applicant)) {
            return back()->withErrors($error);
        }

        ['generated' => $generated] = NimGenerator::generate($applicant->pmbYear, $applicant->id);

        if ($generated->isEmpty()) {
            return back()->withErrors('NIM ' . $applicant->full_name . ' gagal dibuat. Pastikan kode NIM prodi sudah diisi di Master Data Program Studi.');
        }

        return back()->with('success', 'NIM ' . $generated->first()->nim . ' dibubuhkan untuk ' . $applicant->full_name . '.');
    }

    /**
     * Pembubuhan NIM manual, contoh untuk mahasiswa pindahan atau NIM dari SK tertentu.
     */
    public function update(Request $request, Applicant $applicant)
    {
        if ($error = $this->guardAssignable($applicant, allowExisting: true)) {
            return back()->withErrors($error);
        }

        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\-\/]+$/', Rule::unique('applicants', 'nim')->ignore($applicant->id)],
        ], [
            'nim.regex' => 'NIM hanya boleh berisi huruf, angka, titik, strip, dan garis miring.',
            'nim.unique' => 'NIM ini sudah dipakai mahasiswa lain.',
        ], [
            'nim' => 'NIM',
        ]);

        DB::transaction(fn () => NimGenerator::assign($applicant, strtoupper($validated['nim'])));

        return back()->with('success', 'NIM ' . $applicant->nim . ' disimpan untuk ' . $applicant->full_name . '.');
    }

    public function destroy(Applicant $applicant)
    {
        if (blank($applicant->nim)) {
            return back()->withErrors($applicant->full_name . ' belum memiliki NIM.');
        }

        if (in_array($applicant->sync_status, self::LOCKED_SYNC_STATUSES, true)) {
            return back()->withErrors('NIM ' . $applicant->nim . ' tidak dapat dihapus karena data sudah diproses atau ditarik SIAKAD.');
        }

        $nim = $applicant->nim;

        DB::transaction(fn () => NimGenerator::release($applicant));

        return back()->with('success', 'NIM ' . $nim . ' milik ' . $applicant->full_name . ' dihapus.');
    }

    private function guardAssignable(Applicant $applicant, bool $allowExisting = false): ?string
    {
        if ($applicant->selection_status !== 'diterima' || $applicant->re_registration_status !== 'daftar_ulang_valid') {
            return 'NIM hanya bisa dibubuhkan setelah daftar ulang ' . $applicant->full_name . ' valid.';
        }

        if (! $allowExisting && filled($applicant->nim)) {
            return $applicant->full_name . ' sudah memiliki NIM ' . $applicant->nim . '.';
        }

        if (in_array($applicant->sync_status, self::LOCKED_SYNC_STATUSES, true)) {
            return 'NIM tidak dapat diubah karena data sudah diproses atau ditarik SIAKAD.';
        }

        return null;
    }

    private function selectedPmbYear(Request $request): ?PmbYear
    {
        return PmbYear::find($request->session()->get(SelectAdminPmbYear::SESSION_KEY));
    }
}
