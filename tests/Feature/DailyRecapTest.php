<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\Payment;
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
     * Camaba online yang formulirnya divalidasi pada $paidAt.
     */
    private function official(array $data, array $education, string $paidAt, array $applicant = []): Applicant
    {
        $registered = $this->registerApplicant($data);
        $registered->education->update($education);
        $registered->update(['payment_status' => 'valid'] + $applicant);

        $invoice = $registered->registrationInvoice;
        $invoice->update(['status' => 'paid']);

        Payment::create([
            'applicant_id' => $registered->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/PMB/2027/' . $registered->id,
            'amount' => $invoice->total_amount,
            'status' => 'valid',
            'verified_at' => $paidAt,
        ]);

        return $registered;
    }

    public function test_recap_follows_daily_whatsapp_template(): void
    {
        $classB = ClassType::where('code', 'REG_B')->value('id');

        $this->official(['email' => 'a@example.com', 'source_information' => 'Instagram'],
            ['school_type' => 'SMA', 'school_status' => 'Negeri', 'graduation_year' => 2026], '2027-04-08 10:00');
        $this->official(['email' => 'b@example.com', 'source_information' => 'Instagram', 'class_type_id' => $classB],
            ['school_name' => 'SMK MITRA INDUSTRI', 'school_type' => 'SMK', 'school_status' => 'Swasta', 'graduation_year' => 2025], '2027-04-09 09:00');
        $this->official(['email' => 'c@example.com', 'source_information' => 'Website'],
            ['school_name' => 'SMK MITRA INDUSTRI MM2100', 'school_type' => 'SMK', 'school_status' => 'Swasta', 'graduation_year' => 2026], '2027-04-10 08:00',
            ['registered_via' => 'kampus']);
        $this->official(['email' => 'd@example.com'],
            ['school_type' => 'MA', 'school_status' => 'Swasta'], '2027-04-10 13:00');

        // Peminat yang belum membayar formulir tidak dihitung, begitu pula pembayaran setelah tanggal rekap.
        $this->registerApplicant(['email' => 'peminat@example.com']);
        $this->official(['email' => 'besok@example.com'], ['school_type' => 'SMA'], '2027-04-11 08:00');

        $this->travelTo(Carbon::parse('2027-04-10 16:30'));

        $text = DailyRecap::for(null, AdmissionWave::where('code', 'GEL1')->first(), Carbon::parse('2027-04-10'))->text();

        $this->assertSame(implode("\n", [
            'Selamat sore Bapak/Ibu, berikut merupakan hasil rekap Pendaftaran PMB *Gelombang 1* pada hari Sabtu, 10 April 2027',
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
        $this->official(['email' => 'a@example.com'], ['school_type' => 'SMK', 'school_status' => 'Negeri', 'graduation_year' => 2026], now()->toDateTimeString());

        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('hasil rekap Pendaftaran PMB 2027 *Gelombang 1*', false)
            ->assertSee('sebanyak *1 pendaftar*', false)
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('Salin Teks');

        $this->actingAs($admin, 'web')
            ->get('/admin/dashboard?rekap_gelombang=semua&rekap_tanggal=' . now()->subDays(3)->toDateString())
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
