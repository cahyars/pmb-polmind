<?php

namespace Database\Seeders;

use App\Models\AchievementLevel;
use App\Models\AdmissionWave;
use App\Models\FeeComponent;
use App\Models\FeeComponentPathAmount;
use App\Models\FeeComponentWaveAmount;
use App\Models\PmbYear;
use App\Models\RegistrationPath;
use Illuminate\Database\Seeder;

/**
 * Biaya daftar ulang resmi PMB 2027 (polmind.ac.id/pmb): SPI per gelombang, SPI khusus
 * jalur Undangan, kategori Prestasi, dan UKT yang sama untuk semua. Aman dijalankan ulang.
 */
class FeeScheme2027Seeder extends Seeder
{
    private const SPI_PER_WAVE = [
        'GEL1' => 9000000,
        'GEL2' => 10000000,
        'GEL3' => 12000000,
        'GEL4' => 14000000,
        'GEL5' => 15000000,
    ];

    private const SPI_UNDANGAN = 7500000;

    private const ACHIEVEMENT_LEVELS = [
        ['name' => 'Tingkat 1 · Nasional', 'description' => 'Prestasi tingkat nasional.', 'spi_amount' => 4500000, 'sort_order' => 1],
        ['name' => 'Tingkat 2 · Provinsi', 'description' => 'Prestasi tingkat provinsi.', 'spi_amount' => 5000000, 'sort_order' => 2],
        ['name' => 'Tingkat 3', 'description' => null, 'spi_amount' => 6000000, 'sort_order' => 3],
    ];

    public function run(): void
    {
        $spi = FeeComponent::updateOrCreate(['code' => FeeComponent::SPI_CODE], [
            'name' => 'Sumbangan Pengembangan Institusi',
            'type' => 're_registration',
            'amount' => 15000000,
            // Default dibayar penuh; cicilan hanya bila diaktifkan Admin PMB per camaba.
            'is_installable' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $ukt = FeeComponent::updateOrCreate(['code' => 'UKT_SEMESTER_1'], [
            'name' => 'Uang Kuliah Tunggal - Semester 1',
            'type' => 're_registration',
            'amount' => 7000000,
            'is_installable' => false,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Per model (bukan update massal) agar perubahan tercatat di Log Aktivitas.
        FeeComponent::where('code', 'DAFTAR_ULANG')->get()->each->update(['is_active' => false]);

        // UKT sama untuk semua jalur dan gelombang.
        $ukt->pathAmounts()->get()->each->delete();
        $ukt->waveAmounts()->get()->each->delete();

        $year = PmbYear::where('code', 'PMB2027')->first();
        $waveCount = 0;

        foreach ($year ? self::SPI_PER_WAVE : [] as $code => $amount) {
            $wave = AdmissionWave::where('pmb_year_id', $year->id)->where('code', $code)->first();

            if ($wave) {
                FeeComponentWaveAmount::updateOrCreate(
                    ['fee_component_id' => $spi->id, 'admission_wave_id' => $wave->id],
                    ['amount' => $amount],
                );
                $waveCount++;
            }
        }

        $this->command?->info('SPI per gelombang terpasang untuk ' . $waveCount . ' dari ' . count(self::SPI_PER_WAVE) . ' gelombang PMB2027.');

        // SPI jalur Undangan tetap; jalur Prestasi mengikuti kategori prestasi.
        foreach (RegistrationPath::all() as $path) {
            $match = ['fee_component_id' => $spi->id, 'registration_path_id' => $path->id];

            if ($path->code === 'undangan') {
                FeeComponentPathAmount::updateOrCreate($match, ['amount' => self::SPI_UNDANGAN]);
            } else {
                FeeComponentPathAmount::where($match)->get()->each->delete();
            }
        }

        foreach (self::ACHIEVEMENT_LEVELS as $level) {
            AchievementLevel::firstOrCreate(['name' => $level['name']], $level + ['is_active' => true]);
        }

        $this->command?->info('Kategori prestasi aktif: ' . AchievementLevel::where('is_active', true)->count() . '.');
    }
}
