<?php

namespace App\Services\Reports;

use App\Models\Applicant;
use App\Models\StudyProgram;
use Illuminate\Support\Collection;

/**
 * Laporan Seleksi: kesiapan seleksi, hasil keputusan per prodi, jalur,
 * dan gelombang, serta sebaran nilai akhir.
 */
class SelectionReport
{
    public const STATUSES = [
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'ditolak' => 'Ditolak',
        'belum_diseleksi' => 'Belum Diseleksi',
    ];

    public const SCORE_BUCKETS = [
        '< 50' => [0, 50],
        '50-59' => [50, 60],
        '60-69' => [60, 70],
        '70-79' => [70, 80],
        '80-89' => [80, 90],
        '90-100' => [90, 101],
    ];

    public static function build(ReportFilter $filter): array
    {
        $applicants = $filter->applicants()
            ->with(['selection', 'studyProgram', 'admissionWave'])
            ->get();

        $ready = $applicants->filter(fn (Applicant $applicant) => self::isReady($applicant));

        return [
            'summary' => [
                'total' => $applicants->count(),
                'ready' => $ready->count(),
                'waiting' => $ready->where('selection_status', 'belum_diseleksi')->count(),
                'accepted' => $applicants->where('selection_status', 'diterima')->count(),
                'reserve' => $applicants->where('selection_status', 'cadangan')->count(),
                'rejected' => $applicants->where('selection_status', 'ditolak')->count(),
            ],
            'programs' => self::programs($applicants),
            'paths' => self::groupRows($applicants, 'registration_path', collect(Applicant::REGISTRATION_PATHS)->map(fn ($path) => $path['label'])),
            'waves' => self::groupRows(
                $applicants,
                'admission_wave_id',
                $applicants->pluck('admissionWave')->filter()->unique('id')->sortBy('start_date')->mapWithKeys(fn ($wave) => [$wave->id => $wave->name])
            ),
            'scores' => self::scoreBuckets($applicants),
            'results' => $applicants
                ->where('selection_status', '!=', 'belum_diseleksi')
                ->sortBy([
                    fn ($a, $b) => strcmp((string) $a->studyProgram?->code, (string) $b->studyProgram?->code),
                    fn ($a, $b) => ($b->selection?->final_score ?? -1) <=> ($a->selection?->final_score ?? -1),
                ])
                ->values(),
        ];
    }

    public static function isReady(Applicant $applicant): bool
    {
        return $applicant->document_status === 'valid' && $applicant->payment_status === 'valid';
    }

    private static function programs(Collection $applicants): Collection
    {
        return StudyProgram::orderBy('code')->get()
            ->map(function ($program) use ($applicants) {
                $group = $applicants->where('study_program_id', $program->id);

                return $program->is_active || $group->isNotEmpty()
                    ? ['code' => $program->code, 'name' => $program->degree . ' ' . $program->name] + self::stats($group)
                    : null;
            })
            ->filter()
            ->values();
    }

    private static function groupRows(Collection $applicants, string $column, Collection $labels): Collection
    {
        return $labels
            ->map(fn ($label, $key) => ['label' => $label] + self::stats($applicants->where($column, $key)))
            ->values();
    }

    private static function stats(Collection $group): array
    {
        $ready = $group->filter(fn (Applicant $applicant) => self::isReady($applicant))->count();
        $accepted = $group->where('selection_status', 'diterima')->count();
        $scores = $group->map(fn ($applicant) => $applicant->selection?->final_score)->filter(fn ($score) => $score !== null);

        return [
            'registrants' => $group->count(),
            'ready' => $ready,
            'accepted' => $accepted,
            'reserve' => $group->where('selection_status', 'cadangan')->count(),
            'rejected' => $group->where('selection_status', 'ditolak')->count(),
            'waiting' => $group->filter(fn ($applicant) => self::isReady($applicant) && $applicant->selection_status === 'belum_diseleksi')->count(),
            'acceptance_rate' => $ready > 0 ? (int) round($accepted / $ready * 100) : null,
            'average_score' => $scores->isNotEmpty() ? round($scores->avg(), 1) : null,
        ];
    }

    private static function scoreBuckets(Collection $applicants): Collection
    {
        $scores = $applicants->map(fn ($applicant) => $applicant->selection?->final_score)->filter(fn ($score) => $score !== null);

        return collect(self::SCORE_BUCKETS)->map(
            fn ($range) => $scores->filter(fn ($score) => $score >= $range[0] && $score < $range[1])->count()
        );
    }
}
