<?php

namespace App\Services;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\SelectionComponent;
use App\Services\Reports\DailyRecap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pembagian peserta tes seleksi ke kelompok. TPA diikuti bersama dalam satu sesi, lalu Tes Fisik,
 * MCU, dan Interview bergiliran per kelompok. Pendaftar SMK Mitra Industri dan SMK Ananda Mitra
 * Industri dites terpisah dalam kelompok mitra.
 */
class SelectionGroups
{
    public const DEFAULT_GROUPS = 3;

    /**
     * Peserta tes gelombang ini: camaba yang sudah bernomor peserta, urut nomor.
     */
    public static function participants(AdmissionWave $wave): Builder
    {
        return Applicant::query()
            ->where('admission_wave_id', $wave->id)
            ->whereNotNull('selection_sequence')
            ->orderBy('selection_sequence');
    }

    /**
     * Batasi ke pendaftar dari sekolah mitra ($partner true) atau pendaftar umum (false).
     */
    public static function wherePartner(Builder $query, bool $partner = true): Builder
    {
        $match = fn (Builder $education) => $education->where('school_name', 'like', '%' . DailyRecap::PARTNER_KEYWORD . '%');

        return $partner ? $query->whereHas('education', $match) : $query->whereDoesntHave('education', $match);
    }

    public static function isPartner(Applicant $applicant): bool
    {
        return DailyRecap::isPartnerSchool($applicant->education?->school_name);
    }

    /**
     * Kelompok 1-3 yang mengikuti semua tes aktif, dan Kelompok Mitra yang hanya MCU.
     */
    public static function createDefaults(AdmissionWave $wave): void
    {
        $components = SelectionComponent::where('is_active', true)->orderBy('sort_order')->pluck('id', 'code');
        $partnerComponents = $components->only('MCU')->values();

        DB::transaction(function () use ($wave, $components, $partnerComponents) {
            for ($number = 1; $number <= self::DEFAULT_GROUPS; $number++) {
                $wave->selectionGroups()->create(['name' => 'Kelompok ' . $number, 'sort_order' => $number])
                    ->components()->sync($components->values());
            }

            $wave->selectionGroups()->create([
                'name' => 'Kelompok Mitra',
                'is_partner' => true,
                'sort_order' => self::DEFAULT_GROUPS + 1,
                'notes' => 'Pendaftar SMK Mitra Industri dan SMK Ananda Mitra Industri',
            ])->components()->sync($partnerComponents->isNotEmpty() ? $partnerComponents : $components->values());
        });
    }

    /**
     * Bagi peserta ke kelompok; pendaftar mitra masuk kelompok mitra bila ada.
     * $reset true: semua peserta umum dibagi ulang dalam blok nomor yang sama besar (contoh 45 peserta:
     * 001-015, 016-030, 031-045). false: hanya yang belum berkelompok, masing-masing ke kelompok terkecil.
     *
     * @return int jumlah peserta yang kelompoknya berubah
     */
    public static function distribute(AdmissionWave $wave, bool $reset): int
    {
        $groups = $wave->selectionGroups()->get();
        $regular = $groups->where('is_partner', false)->values();
        $partnerGroup = $groups->firstWhere('is_partner', true);

        if ($regular->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($wave, $reset, $regular, $partnerGroup) {
            $participants = self::participants($wave)->with('education')->get();

            if (! $reset) {
                $participants = $participants->whereNull('selection_group_id');
            }

            [$partners, $others] = $partnerGroup
                ? $participants->partition(fn (Applicant $applicant) => self::isPartner($applicant))
                : [collect(), $participants];

            $target = $partners->mapWithKeys(fn (Applicant $applicant) => [$applicant->id => $partnerGroup->id])->all();

            if ($reset) {
                $size = max((int) ceil($others->count() / $regular->count()), 1);

                foreach ($others->values() as $index => $applicant) {
                    $target[$applicant->id] = $regular[intdiv($index, $size)]->id;
                }
            } else {
                $counts = $regular->mapWithKeys(fn ($group) => [$group->id => $group->applicants()->count()])->all();

                foreach ($others as $applicant) {
                    $groupId = array_search(min($counts), $counts, true);
                    $target[$applicant->id] = $groupId;
                    $counts[$groupId]++;
                }
            }

            // Per model agar perpindahan kelompok tercatat di Log Aktivitas.
            $moved = 0;
            foreach ($participants as $applicant) {
                if ($applicant->selection_group_id !== $target[$applicant->id]) {
                    $applicant->update(['selection_group_id' => $target[$applicant->id]]);
                    $moved++;
                }
            }

            return $moved;
        });
    }
}
