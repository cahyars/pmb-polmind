<?php

namespace Database\Seeders;

use App\Models\AdmissionWave;
use App\Models\PmbYear;
use App\Models\SelectionComponent;
use App\Models\SelectionSchedule;
use Illuminate\Database\Seeder;

/**
 * Komponen tes dan jadwal seleksi PMB 2027 (mengacu pada https://polmind.ac.id/pmb).
 * Hanya mengisi data yang masih kosong, sehingga aman dijalankan ulang tanpa
 * menimpa perubahan admin.
 */
class SelectionScheduleSeeder extends Seeder
{
    private const TIMELINE = [
        'GEL1' => ['test' => '2027-01-30', 'announcement' => '2027-02-02', 're_registration' => ['2027-02-02', '2027-02-12']],
        'GEL2' => ['test' => '2027-04-03', 'announcement' => '2027-04-06', 're_registration' => ['2027-04-06', '2027-04-16']],
        'GEL3' => ['test' => '2027-06-05', 'announcement' => '2027-06-09', 're_registration' => ['2027-06-09', '2027-06-18']],
        'GEL4' => ['test' => '2027-07-31', 'announcement' => '2027-08-03', 're_registration' => ['2027-08-03', '2027-08-13']],
    ];

    public function run(): void
    {
        $writtenTest = SelectionComponent::firstOrCreate(
            ['code' => 'TES_TULIS'],
            ['name' => 'Tes Tertulis', 'description' => 'Tes potensi akademik dan pengetahuan dasar.', 'weight' => 60, 'sort_order' => 1, 'is_active' => true]
        );

        SelectionComponent::firstOrCreate(
            ['code' => 'WAWANCARA'],
            ['name' => 'Wawancara', 'description' => 'Wawancara minat, motivasi, dan kesiapan kuliah.', 'weight' => 40, 'sort_order' => 2, 'is_active' => true]
        );

        $pmbYear = PmbYear::where('code', 'PMB2027')->first();

        if (! $pmbYear) {
            return;
        }

        foreach (self::TIMELINE as $code => $timeline) {
            $wave = AdmissionWave::where('pmb_year_id', $pmbYear->id)->where('code', $code)->first();

            if (! $wave) {
                continue;
            }

            $wave->fill(array_filter([
                'announcement_date' => $wave->announcement_date ? null : $timeline['announcement'],
                're_registration_start_date' => $wave->re_registration_start_date ? null : $timeline['re_registration'][0],
                're_registration_end_date' => $wave->re_registration_end_date ? null : $timeline['re_registration'][1],
            ]))->save();

            if (! SelectionSchedule::where('admission_wave_id', $wave->id)->exists()) {
                SelectionSchedule::create([
                    'admission_wave_id' => $wave->id,
                    'selection_component_id' => $writtenTest->id,
                    'test_date' => $timeline['test'],
                ]);
            }
        }
    }
}
