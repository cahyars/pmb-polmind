<?php

namespace App\Services\Reports;

use App\Models\InformationSource;
use App\Models\StudyProgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan Sumber Informasi untuk tim marketing: jumlah pendaftar dan konversinya
 * (formulir lunas, diterima, daftar ulang) per sumber, tren bulanan, dan sebaran prodi.
 */
class SourceReport
{
    /**
     * Banyaknya sumber teratas yang digambar sendiri di grafik tren; sisanya digabung "Lainnya".
     */
    private const TREND_SERIES = 3;

    public static function build(ReportFilter $filter): array
    {
        $rows = $filter->applicants()->get([
            'applicants.id',
            'applicants.source_information',
            'applicants.study_program_id',
            'applicants.payment_status',
            'applicants.registration_status',
            'applicants.selection_status',
            'applicants.re_registration_status',
            'applicants.created_at',
        ]);

        $total = $rows->count();
        $grouped = $rows->groupBy(fn ($row) => filled($row->source_information) ? $row->source_information : InformationSource::NOT_FILLED_KEY);
        $sources = self::sources($grouped, $total);
        $filled = $sources->where('key', '!=', InformationSource::NOT_FILLED_KEY);

        return [
            'total' => $total,
            'filled' => $filled->sum('total'),
            'sources' => $sources,
            'top' => $filled->where('total', '>', 0)->first(),
            // Sumber dengan minimal 5 pendaftar agar persentase konversinya bermakna.
            'bestConversion' => $filled->where('total', '>=', 5)->sortByDesc('paid_rate')->first(),
            'trend' => self::trend($rows, $filled),
            'programs' => self::programs($grouped, $sources),
        ];
    }

    private static function sources(Collection $grouped, int $total): Collection
    {
        // Sumber aktif tetap tampil walau belum ada pendaftar, supaya kanal yang sepi ikut terlihat.
        $master = InformationSource::query()->ordered()->get(['name', 'is_active']);

        return $master->where('is_active', true)->pluck('name')
            ->merge($grouped->keys()->reject(fn ($key) => $key === InformationSource::NOT_FILLED_KEY))
            ->unique()
            ->map(fn (string $name) => self::stats($grouped->get($name, collect()), $total, $name, $name, (bool) $master->firstWhere('name', $name)?->is_active))
            ->sortBy([['total', 'desc'], ['label', 'asc']])
            ->values()
            ->when($grouped->has(InformationSource::NOT_FILLED_KEY), fn (Collection $sources) => $sources->push(
                self::stats($grouped->get(InformationSource::NOT_FILLED_KEY), $total, InformationSource::NOT_FILLED_KEY, InformationSource::NOT_FILLED_LABEL, true)
            ));
    }

    private static function stats(Collection $group, int $total, string $key, string $label, bool $isActive): array
    {
        $rate = fn (int $value, int $base) => $base > 0 ? round($value / $base * 100, 1) : 0;
        $count = $group->count();
        $paid = $group->where('payment_status', 'valid')->count();
        $accepted = $group->where('selection_status', 'diterima')->count();
        $reRegistered = $group->where('re_registration_status', 'daftar_ulang_valid')->count();

        return [
            'key' => $key,
            'label' => $label,
            'is_active' => $isActive,
            'total' => $count,
            'share' => $rate($count, $total),
            'paid' => $paid,
            'paid_rate' => $rate($paid, $count),
            'biodata' => $group->where('registration_status', 'biodata_lengkap')->count(),
            'accepted' => $accepted,
            'accepted_rate' => $rate($accepted, $count),
            're_registered' => $reRegistered,
            're_registered_rate' => $rate($reRegistered, $count),
        ];
    }

    /**
     * Pendaftar per bulan untuk sumber teratas, sisanya (termasuk yang tidak diisi) digabung "Lainnya".
     */
    private static function trend(Collection $rows, Collection $filled): array
    {
        $month = fn ($row) => Carbon::parse($row->created_at)->format('Y-m');
        $emptyMonths = $rows->countBy($month)->map(fn () => 0);

        if ($emptyMonths->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        $topKeys = $filled->where('total', '>', 0)->take(self::TREND_SERIES)->pluck('key');
        $series = $topKeys->mapWithKeys(fn ($key) => [$key => $rows->where('source_information', $key)]);

        $rest = $rows->reject(fn ($row) => $topKeys->contains($row->source_information));
        if ($rest->isNotEmpty()) {
            $series->put('Lainnya', $rest);
        }

        $datasets = $series->map(fn (Collection $group, string $label) => [
            'label' => $label,
            'data' => MonthlySeries::fill($emptyMonths->merge($group->countBy($month)))->values(),
        ])->values();

        return [
            'labels' => MonthlySeries::fill($emptyMonths)->keys(),
            'datasets' => $datasets,
        ];
    }

    /**
     * Matriks sumber × prodi pilihan 1, hanya prodi aktif atau yang punya pendaftar.
     */
    private static function programs(Collection $grouped, Collection $sources): array
    {
        $used = $grouped->flatten(1)->pluck('study_program_id')->unique();
        $programs = StudyProgram::orderBy('code')->get()
            ->filter(fn ($program) => $program->is_active || $used->contains($program->id))
            ->values();

        $rows = $sources->where('total', '>', 0)->map(fn (array $source) => [
            'key' => $source['key'],
            'label' => $source['label'],
            'counts' => $programs->mapWithKeys(fn ($program) => [
                $program->id => $grouped->get($source['key'], collect())->where('study_program_id', $program->id)->count(),
            ]),
            'total' => $source['total'],
        ])->values();

        return ['columns' => $programs, 'rows' => $rows];
    }
}
