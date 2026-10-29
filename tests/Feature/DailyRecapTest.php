<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reports\DailyRecap;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class DailyRecapTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    /**
     * Registrasi pada waktu tertentu (jam WIB), dengan data sekolah tambahan.
     */
    private function registerAt(string $at, array $data = [], array $education = [], array $applicant = []): Applicant
    {
        $this->travelTo(Carbon::parse($at));

        $registered = $this->registerApplicant(['email' => 'camaba' . (++$this->counter) . '@example.com'] + $data);
        $registered->education->update($education);
        $registered->update($applicant);

        return $registered;
    }

    private int $counter = 0;

    public function test_recap_counts_registrants_with_six_pm_daily_cutoff(): void
    {
        // 10 pendaftar lama.
        foreach (range(1, 10) as $i) {
            $this->registerAt('2027-04-20 10:00');
        }

        // 5 pendaftar masuk rekap 28 April: 27 April 18.00 s.d. 28 April 18.00 WIB, bayar atau belum.
        $this->registerAt('2027-04-27 19:00', ['source_information' => 'Instagram'], ['school_type' => 'SMA', 'school_status' => 'Negeri', 'graduation_year' => 2026]);
        $this->registerAt('2027-04-27 23:30');
        $this->registerAt('2027-04-28 08:00', [], [], ['payment_status' => 'valid']);
        $this->registerAt('2027-04-28 12:00', [], [], ['registered_via' => 'kampus']);
        $this->registerAt('2027-04-28 16:00');

        // Pendaftar 27 April sore (sebelum 18.00) sudah masuk rekap 27 April.
        $this->registerAt('2027-04-27 17:59');

        $this->travelTo(Carbon::parse('2027-04-28 16:30'));
        $wave = AdmissionWave::where('code', 'GEL1')->first();

        $april28 = DailyRecap::for(null, $wave, Carbon::parse('2027-04-28'))->text();
        $this->assertStringContainsString('sebanyak *16 pendaftar*', $april28);
        $this->assertStringContainsString("pada tanggal 28/4/2027 sebanyak *5 pendaftar*, dengan rincian:\n- Daftar Online: 4\n- Daftar Offline: 1", $april28);

        // Malam 28 April pukul 20.30 ada pendaftar baru: masuk rekap 29 April, bukan 28 April.
        $this->registerAt('2027-04-28 20:30');
        $this->travelTo(Carbon::parse('2027-04-29 16:30'));

        $this->assertStringContainsString('sebanyak *16 pendaftar*', DailyRecap::for(null, $wave, Carbon::parse('2027-04-28'))->text());

        $april29 = DailyRecap::for(null, $wave, Carbon::parse('2027-04-29'))->text();
        $this->assertStringContainsString('hingga tanggal 29/4/2027 adalah sebanyak *17 pendaftar*', $april29);
        $this->assertStringContainsString('pada tanggal 29/4/2027 sebanyak *1 pendaftar*', $april29);
    }

    public function test_recap_follows_daily_whatsapp_template(): void
    {
        $classB = ClassType::where('code', 'REG_B')->value('id');

        $this->registerAt('2027-04-08 10:00', ['source_information' => 'Instagram'],
            ['school_type' => 'SMA', 'school_status' => 'Negeri', 'graduation_year' => 2026]);
        $this->registerAt('2027-04-09 09:00', ['source_information' => 'Instagram', 'class_type_id' => $classB],
            ['school_name' => 'SMK MITRA INDUSTRI', 'school_type' => 'SMK', 'school_status' => 'Swasta', 'graduation_year' => 2025]);
        $this->registerAt('2027-04-09 19:00', ['source_information' => 'Website'],
            ['school_name' => 'SMK MITRA INDUSTRI MM2100', 'school_type' => 'SMK', 'school_status' => 'Swasta', 'graduation_year' => 2026],
            ['registered_via' => 'kampus']);
        $this->registerAt('2027-04-10 13:00', [], ['school_type' => 'MA', 'school_status' => 'Swasta']);

        // Setelah 18.00 masuk rekap besok.
        $this->registerAt('2027-04-10 18:30', [], ['school_type' => 'SMA']);

        $this->travelTo(Carbon::parse('2027-04-10 18:45'));

        $text = DailyRecap::for(null, AdmissionWave::where('code', 'GEL1')->first(), Carbon::parse('2027-04-10'))->text();

        $this->assertSame(implode("\n", [
            'Selamat malam Bapak/Ibu, berikut merupakan hasil rekap Pendaftaran PMB *Gelombang 1* pada hari Sabtu, 10 April 2027',
            '',
            '1. Total pendaftar resmi hingga tanggal 10/4/2027 adalah sebanyak *4 pendaftar*.',
            '',
            '2. Pendaftar bertambah pada tanggal 10/4/2027 sebanyak *2 pendaftar*, dengan rincian:',
            '- Daftar Online: 1',
            '- Daftar Offline: 1',
            '',
            '3. Pilihan Program Studi :',
            '- Bisnis Digital: 0',
            '- Teknologi Rekayasa Manufaktur: 0',
            '- Teknologi Rekayasa Perangkat Lunak: 4',
            '',
            '4. Total pendaftar berdasarkan asal sekolah:',
            '- SMA Negeri: 1',
            '- SMK Swasta: 2',
            '- MA Swasta: 1',
            '',
            '5. Total pendaftar berdasarkan tahun lulus:',
            '- 2026: 2',
            '- 2025: 1',
            '- Belum diisi: 1',
            '',
            '6. Total pendaftar berdasarkan pilihan kelas:',
            '- Reguler A: 3',
            '- Reguler B: 1',
            '',
            '7. Data pendaftar berdasarkan rekomendasi:',
            '- Instagram: 2',
            '- Website: 1',
            '- Tidak diisi: 1',
            '',
            '8. Pendaftar dari lulusan SMK Mitra Industri total ada 2 orang, dengan rincian tahun lulus:',
            '- 2026: 1',
            '- 2025: 1',
        ]), $text);
    }

    public function test_dashboard_shows_recap_with_copy_and_whatsapp_share(): void
    {
        $this->travelTo(Carbon::parse('2027-04-10 16:00'));
        $this->registerAt('2027-04-10 09:00');
        $this->travelTo(Carbon::parse('2027-04-10 16:30'));

        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('hasil rekap Pendaftaran PMB 2027 *Gelombang 1*', false)
            ->assertSee('sebanyak *1 pendaftar*', false)
            ->assertSee('9 Apr 18.00 s.d. 10 Apr 18.00 WIB')
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('Salin Teks');

        $this->actingAs($admin, 'web')
            ->get('/admin/dashboard?rekap_gelombang=semua&rekap_tanggal=2027-04-05')
            ->assertOk()
            ->assertSee('Pendaftaran PMB 2027 pada hari', false)
            ->assertSee('sebanyak *0 pendaftar*', false);

        $this->actingAs($admin, 'web')->get('/admin/dashboard?rekap_tanggal=bukan-tanggal')->assertOk();
    }

    public function test_greeting_follows_time_of_day(): void
    {
        $this->assertSame('pagi', DailyRecap::greeting(Carbon::parse('2027-04-10 07:00')));
        $this->assertSame('siang', DailyRecap::greeting(Carbon::parse('2027-04-10 12:00')));
        $this->assertSame('sore', DailyRecap::greeting(Carbon::parse('2027-04-10 16:25')));
        $this->assertSame('malam', DailyRecap::greeting(Carbon::parse('2027-04-10 20:00')));
    }
}
