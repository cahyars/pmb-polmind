<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\Selection;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\Reports\ApplicantReport;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReRegistrationReport;
use App\Services\Reports\SelectionReport;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_report_menu_has_three_submenus_and_index_redirects(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/reports?study_program=1')
            ->assertRedirect('/admin/reports/pendaftar?study_program=1');

        $this->actingAs($admin, 'web')
            ->get('/admin/reports/pendaftar')
            ->assertOk()
            ->assertSeeInOrder(['Laporan Pendaftar', 'Laporan Seleksi', 'Laporan Daftar Ulang'])
            ->assertSee('PDF &amp; XLSX menyusul', false)
            ->assertSee('Belum ada data untuk ditampilkan.');

        $this->actingAs($admin, 'web')->get('/admin/reports/lainnya')->assertNotFound();
    }

    public function test_applicant_report_maps_schools_programs_gender_and_education(): void
    {
        $trm = StudyProgram::where('code', 'TRM')->value('id');

        $a = $this->registerApplicant(['email' => 'a@example.com', 'source_information' => 'Instagram']);
        $b = $this->registerApplicant(['email' => 'b@example.com', 'source_information' => 'Instagram']);
        $c = $this->registerApplicant([
            'email' => 'c@example.com',
            'study_program_id' => $trm,
            'school_npsn' => '20200001',
            'school_name' => 'MA Swasta Al Ikhlas',
            'school_type' => 'MA',
            'school_status' => 'Swasta',
        ]);

        $a->update(['gender' => 'L', 'second_study_program_id' => $trm, 'payment_status' => 'valid']);
        $b->update(['gender' => 'P']);
        $a->education->update(['major' => 'rpl ', 'graduation_year' => 2026]);
        $b->education->update(['major' => 'RPL', 'graduation_year' => 2027]);

        $report = ApplicantReport::build(new ReportFilter());

        $this->assertSame(3, $report['total']);
        $this->assertSame(['Laki-laki' => 1, 'Perempuan' => 1, 'Belum diisi' => 1], $report['gender']);

        $schools = $report['schools'];
        $this->assertSame('SMK NEGERI 1 BATAM', $schools[0]['name']);
        $this->assertSame(2, $schools[0]['total']);
        $this->assertSame(1, $schools[0]['paid']);
        $this->assertSame('MA SWASTA AL IKHLAS', $schools[1]['name']);

        $programs = $report['programs']->keyBy('code');
        $this->assertSame(2, $programs['TRPL']['first']);
        $this->assertSame(1, $programs['TRM']['first']);
        $this->assertSame(1, $programs['TRM']['second']);

        $this->assertSame(2, $report['sources']['Instagram']);
        $this->assertSame(1, $report['sources']['Tidak diisi']);
        $this->assertSame(2, $report['majors']['RPL']);
        $this->assertSame(['2026', '2027', 'Belum diisi'], $report['graduationYears']->keys()->map(fn ($key) => (string) $key)->all());

        $types = $report['schoolTypes']->keyBy('type');
        $this->assertSame(2, $types['SMK']['negeri']);
        $this->assertSame(1, $types['MA']['swasta']);
        $this->assertSame(3, $report['monthly']->sum());

        // Filter prodi pilihan 1.
        $this->assertSame(1, ApplicantReport::build(new ReportFilter(['study_program' => $trm]))['total']);

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/reports/pendaftar')
            ->assertOk()
            ->assertSee('Pendaftar per Asal Sekolah')
            ->assertSee('MA SWASTA AL IKHLAS')
            ->assertSee('data-chart=', false)
            ->assertSee('Salin Ringkasan');
    }

    public function test_selection_and_re_registration_reports(): void
    {
        $accepted = $this->decided('a@example.com', 'diterima', 85);
        $reserve = $this->decided('b@example.com', 'cadangan', 65);
        $this->decided('c@example.com', 'ditolak', 40);
        $waiting = $this->registerApplicant(['email' => 'd@example.com']);
        $waiting->update(['document_status' => 'valid', 'payment_status' => 'valid']);

        $selection = SelectionReport::build(new ReportFilter());

        $this->assertSame(4, $selection['summary']['ready']);
        $this->assertSame(1, $selection['summary']['waiting']);
        $trpl = $selection['programs']->firstWhere('code', 'TRPL');
        $this->assertSame(25, $trpl['acceptance_rate']);
        $this->assertSame(63.3, $trpl['average_score']);
        $this->assertSame(1, $selection['scores']['80-89']);
        $this->assertSame($accepted->id, $selection['results']->first()->id);

        $invoice = Invoice::create([
            'applicant_id' => $accepted->id,
            'invoice_number' => 'INV/DU/2027/1',
            'type' => 're_registration',
            'total_amount' => 5000000,
            'status' => 'partial',
        ]);
        Payment::create([
            'applicant_id' => $accepted->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/DU/1',
            'transfer_date' => '2027-04-05',
            'amount' => 2000000,
            'status' => 'valid',
        ]);
        $accepted->update(['nim' => 'A20270001']);

        $reRegistration = ReRegistrationReport::build(new ReportFilter());

        $this->assertSame(1, $reRegistration['summary']['accepted']);
        $this->assertSame(1, $reRegistration['summary']['installment']);
        $this->assertSame(1, $reRegistration['summary']['nim']);
        $this->assertEquals(3000000, $reRegistration['summary']['total_remaining']);
        $this->assertSame(['Apr 2027' => 1], $reRegistration['monthly']->all());

        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/reports/seleksi')
            ->assertOk()
            ->assertSee('Hasil Seleksi per Program Studi')
            ->assertSee($reserve->full_name);

        $this->actingAs($admin, 'web')
            ->get('/admin/reports/daftar-ulang')
            ->assertOk()
            ->assertSee('A20270001')
            ->assertSee('Rp2.000.000')
            ->assertSee('Cicilan');
    }

    private function decided(string $email, string $status, float $score): Applicant
    {
        $applicant = $this->registerApplicant(['email' => $email]);
        $applicant->update(['document_status' => 'valid', 'payment_status' => 'valid', 'selection_status' => $status]);

        Selection::create([
            'applicant_id' => $applicant->id,
            'final_score' => $score,
            'status' => $status,
            'decided_at' => now(),
        ]);

        return $applicant;
    }
}
