<?php

namespace App\Services\Reports;

use App\Models\ApplicantEducation;
use App\Models\StudyProgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan Pendaftar: asal sekolah, prodi pilihan 1 & 2, gender, bulan daftar,
 * sumber informasi, tahun lulus, jenis sekolah, dan jurusan asal.
 * Agregasi dilakukan di PHP agar hasilnya sama di MariaDB maupun SQLite.
 */
class ApplicantReport
{
    public const NOT_FILLED = 'Belum diisi';

    public static function build(ReportFilter $filter): array
    {
        $rows = $filter->applicants()
            ->leftJoin('applicant_educations as edu', 'edu.applicant_id', '=', 'applicants.id')
            ->get([
                'applicants.id',
                'applicants.study_program_id',
                'applicants.second_study_program_id',
                'applicants.gender',
                'applicants.source_information',
                'applicants.registration_status',
                'applicants.document_status',
                'applicants.payment_status',
                'applicants.selection_status',
                'applicants.re_registration_status',
                'applicants.sync_status',
                'applicants.created_at',
                'edu.school_npsn',
                'edu.school_name',
                'edu.school_type',
                'edu.school_status',
                'edu.major',
                'edu.graduation_year',
            ]);

        $total = $rows->count();

        return [
            'total' => $total,
            'funnel' => self::funnel($rows),
            'schools' => self::schools($rows),
            'programs' => self::programs($rows),
            'gender' => self::gender($rows),
            'monthly' => MonthlySeries::fill($rows->countBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))),
            'sources' => self::countBy($rows, fn ($row) => $row->source_information ?: 'Tidak diisi'),
            'graduationYears' => self::countBy($rows, fn ($row) => $row->graduation_year ? (string) $row->graduation_year : self::NOT_FILLED)
                ->sortBy(fn ($count, $year) => $year === self::NOT_FILLED ? '9999' : $year),
            'schoolTypes' => self::schoolTypes($rows),
            'majors' => self::majors($rows),
        ];
    }

    private static function funnel(Collection $rows): Collection
    {
        $total = $rows->count();

        return collect([
            'Registrasi Awal' => $total,
            'Formulir Lunas' => $rows->where('payment_status', 'valid')->count(),
            'Biodata Lengkap' => $rows->where('registration_status', 'biodata_lengkap')->count(),
            'Berkas Valid' => $rows->where('document_status', 'valid')->count(),
            'Diterima' => $rows->where('selection_status', 'diterima')->count(),
            'Daftar Ulang Valid' => $rows->where('re_registration_status', 'daftar_ulang_valid')->count(),
        ])->map(fn ($value, $label) => [
            'label' => $label,
            'value' => $value,
            'percent' => $total > 0 ? (int) round($value / $total * 100) : 0,
        ])->values();
    }

    private static function schools(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($row) => $row->school_npsn ?: mb_strtoupper(trim((string) $row->school_name)) ?: self::NOT_FILLED)
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'name' => $first->school_name ?: self::NOT_FILLED,
                    'npsn' => $first->school_npsn,
                    'type' => trim(($first->school_type ?? '') . ' ' . ($first->school_status ?? '')) ?: '-',
                    'total' => $group->count(),
                    'paid' => $group->where('payment_status', 'valid')->count(),
                    'accepted' => $group->where('selection_status', 'diterima')->count(),
                ];
            })
            ->sortBy([['total', 'desc'], ['name', 'asc']])
            ->values();
    }

    private static function programs(Collection $rows): Collection
    {
        $firstChoice = $rows->countBy('study_program_id');
        $secondChoice = $rows->whereNotNull('second_study_program_id')->countBy('second_study_program_id');

        return StudyProgram::orderBy('code')->get()
            ->filter(fn ($program) => $program->is_active || $firstChoice->has($program->id) || $secondChoice->has($program->id))
            ->map(fn ($program) => [
                'code' => $program->code,
                'name' => $program->degree . ' ' . $program->name,
                'first' => $firstChoice->get($program->id, 0),
                'second' => $secondChoice->get($program->id, 0),
            ])
            ->values();
    }

    private static function gender(Collection $rows): array
    {
        return [
            'Laki-laki' => $rows->where('gender', 'L')->count(),
            'Perempuan' => $rows->where('gender', 'P')->count(),
            self::NOT_FILLED => $rows->whereNotIn('gender', ['L', 'P'])->count(),
        ];
    }


    private static function schoolTypes(Collection $rows): Collection
    {
        $types = collect(ApplicantEducation::SCHOOL_TYPES)->push(self::NOT_FILLED);

        return $types
            ->map(function ($type) use ($rows) {
                $group = $rows->filter(fn ($row) => ($row->school_type ?: self::NOT_FILLED) === $type);

                return [
                    'type' => $type,
                    'negeri' => $group->where('school_status', 'Negeri')->count(),
                    'swasta' => $group->where('school_status', 'Swasta')->count(),
                    'other' => $group->whereNotIn('school_status', ['Negeri', 'Swasta'])->count(),
                    'total' => $group->count(),
                ];
            })
            ->filter(fn ($row) => $row['total'] > 0 || in_array($row['type'], ['SMA', 'SMK', 'MA'], true))
            ->values();
    }

    private static function majors(Collection $rows): Collection
    {
        $counts = self::countBy($rows, fn ($row) => filled($row->major) ? mb_strtoupper(trim($row->major)) : self::NOT_FILLED);

        if ($counts->count() <= 12) {
            return $counts;
        }

        return $counts->take(11)->put('Lainnya', $counts->slice(11)->sum());
    }

    private static function countBy(Collection $rows, callable $key): Collection
    {
        return $rows->countBy($key)->sortDesc();
    }
}
