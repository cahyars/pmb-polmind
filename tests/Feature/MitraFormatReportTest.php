<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\Reports\DailyRecap;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class MitraFormatReportTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private AdmissionWave $wave;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        $this->wave = AdmissionWave::where('code', 'GEL1')->firstOrFail();
        // Gelombang dibuka Minggu 18 Oktober; hari H = Kamis 29 Oktober 2026.
        $this->wave->update(['is_active' => true, 'status' => 'open', 'start_date' => '2026-10-18', 'end_date' => '2026-12-31']);
    }

    private function registerAt(string $time, array $overrides = []): Applicant
    {
        $this->travelTo(Carbon::parse($time));

        return $this->registerApplicant($overrides);
    }

    public function test_report_rows_follow_18_oclock_cutoff_week_and_partner_school(): void
    {
        // Mendaftar sebelum gelombang dibuka: masuk baris hari pertama.
        $early = $this->registerAt('2026-10-10 09:00', ['email' => 'early@example.com']);

        $general = $this->registerAt('2026-10-25 10:00', ['email' => 'umum@example.com']);
        $general->update(['payment_status' => 'valid']);
        Payment::create([
            'applicant_id' => $general->id,
            'invoice_id' => $general->registrationInvoice->id,
            'payment_number' => 'PAY/TEST/1',
            'amount' => 300000,
            'status' => 'valid',
        ]);

        // Lulusan SMK Mitra Industri, daftar di kampus pukul 19.00: masuk rekap 27 Oktober, formulir gratis.
        $partner = $this->registerAt('2026-10-26 19:00', [
            'email' => 'mitra@example.com',
            'school_name' => 'SMKS MITRA INDUSTRI MM2100',
            'study_program_id' => StudyProgram::where('code', 'BD')->value('id'),
        ]);
        $partner->update(['registered_via' => 'kampus', 'payment_status' => 'valid']);
        // Pendaftar mitra gratis: pembayaran yang terlanjur masuk pun tidak dikalkulasi ke Total Uang.
        Payment::create([
            'applicant_id' => $partner->id,
            'invoice_id' => $partner->registrationInvoice->id,
            'payment_number' => 'PAY/TEST/2',
            'amount' => 300000,
            'status' => 'valid',
        ]);

        $offline = $this->registerAt('2026-10-29 16:00', [
            'email' => 'offline@example.com',
            'study_program_id' => StudyProgram::where('code', 'TRM')->value('id'),
        ]);
        $offline->update(['registered_via' => 'kampus']);

        // Setelah 18.00 hari H: masuk rekap besok, tidak dihitung.
        $this->registerAt('2026-10-29 18:30', ['email' => 'besok@example.com']);

        $this->travelTo(Carbon::parse('2026-10-29 18:45'));
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get(route('admin.reports.show', ['section' => 'format-mitra', 'wave' => $this->wave->id, 'date' => '2026-10-29']))
            ->assertOk()
            ->assertSee('Report Format Mitra')
            ->assertSee('REKAP AKUMULASI PEMBAYARAN REGISTRASI PMB')
            ->assertSee('DATA PENGISIAN FORMULIR PMB')
            ->assertSee('PERTANGGAL 29 OKTOBER 2026')
            ->assertSee('Download PNG')
            ->assertViewHas('report', function (array $report) use ($early) {
                // Minggu berjalan 25-29 Oktober, nomor baris lanjut dari awal gelombang.
                $this->assertSame(['2026-10-25', '2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29'], $report['rows']->map(fn ($row) => $row['date']->toDateString())->all());
                $this->assertSame(8, $report['rows']->first()['no']);

                $byDate = $report['rows']->keyBy(fn ($row) => $row['date']->toDateString());
                $this->assertSame(1, $byDate['2026-10-25']['general_online']);
                $this->assertEquals(300000, $byDate['2026-10-25']['money']);
                $this->assertSame(0, $byDate['2026-10-26']['total']);
                $this->assertSame(1, $byDate['2026-10-27']['partner_offline']);
                $this->assertEquals(0, $byDate['2026-10-27']['money']);
                $this->assertSame(1, $byDate['2026-10-29']['general_offline']);

                // Jumlah tetap akumulasi sejak awal gelombang, termasuk pendaftar sebelum gelombang dibuka.
                $totals = $report['totals'];
                $this->assertSame([1, 0, 1, 2, 4], [$totals['partner_offline'], $totals['partner_online'], $totals['general_offline'], $totals['general_online'], $totals['total']]);
                $this->assertEquals(300000, $totals['money']);
                $this->assertSame(2, $report['verified']);
                $this->assertSame(2, $report['unverified']);

                $programs = collect($report['programs'])->flip()->map(fn ($id) => $totals['programs'][$id]);
                $this->assertSame(['BD' => 1, 'TRM' => 1, 'TRPL' => 2], $programs->only(['BD', 'TRM', 'TRPL'])->sortKeys()->all());

                return $early->exists;
            });

        $this->actingAs($admin, 'web')
            ->get(route('admin.reports.show', ['section' => 'format-mitra', 'wave' => $this->wave->id, 'date' => '2026-10-29', 'range' => 'all']))
            ->assertViewHas('report', fn (array $report) => $report['rows']->count() === 12
                && $report['rows']->first()['date']->toDateString() === '2026-10-18'
                && $report['rows']->first()['general_online'] === 1);
    }

    public function test_partner_school_matches_private_smks_naming(): void
    {
        $this->assertTrue(DailyRecap::isPartnerSchool('SMKS MITRA INDUSTRI MM2100'));
        $this->assertTrue(DailyRecap::isPartnerSchool('SMK Mitra Industri'));
        $this->assertFalse(DailyRecap::isPartnerSchool('SMK NEGERI 1 BATAM'));
        $this->assertFalse(DailyRecap::isPartnerSchool(null));
    }
}
