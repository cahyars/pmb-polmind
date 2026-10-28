<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class NimGenerationTest extends TestCase
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

    public function test_nim_follows_re_registration_date_per_study_program(): void
    {
        $late = $this->reRegistered('late@example.com', 'TRPL', '2027-04-10', 'valid', 2500000);
        $early = $this->reRegistered('early@example.com', 'TRPL', '2027-04-05');
        $manufaktur = $this->reRegistered('trm@example.com', 'TRM', '2027-04-07');
        $waiting = $this->reRegistered('waiting@example.com', 'TRPL', '2027-04-01', 'waiting_verification');

        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/re-registrations')
            ->assertOk()
            ->assertSee('Pembubuhan NIM')
            ->assertSee('Generate NIM (3)')
            ->assertSee('A20270001');

        $this->actingAs($admin, 'web')
            ->post('/admin/re-registrations/generate-nim')
            ->assertSessionHas('success', '3 NIM berhasil dibuat sesuai urutan tanggal daftar ulang.');

        $this->assertSame('A20270001', $early->refresh()->nim);
        $this->assertSame('A20270002', $late->refresh()->nim);
        $this->assertSame('B20270001', $manufaktur->refresh()->nim);
        $this->assertNull($waiting->refresh()->nim);
        $this->assertNotNull($early->nim_generated_at);

        // Mahasiswa yang daftar ulang belakangan melanjutkan nomor urut, NIM lama tidak berubah.
        $next = $this->reRegistered('next@example.com', 'TRPL', '2027-04-02');

        $this->actingAs($admin, 'web')->post('/admin/re-registrations/generate-nim');

        $this->assertSame('A20270003', $next->refresh()->nim);
        $this->assertSame('A20270001', $early->refresh()->nim);

        $this->actingAs($admin, 'web')
            ->post('/admin/re-registrations/generate-nim')
            ->assertSessionHasErrors();
    }

    public function test_study_program_without_nim_code_is_skipped(): void
    {
        StudyProgram::where('code', 'TRM')->update(['nim_code' => null]);

        $trpl = $this->reRegistered('trpl@example.com', 'TRPL', '2027-04-05');
        $trm = $this->reRegistered('trm@example.com', 'TRM', '2027-04-06');

        $this->actingAs(User::factory()->create(), 'web')
            ->post('/admin/re-registrations/generate-nim')
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 camaba dilewati') && str_contains($message, 'TRM'));

        $this->assertSame('A20270001', $trpl->refresh()->nim);
        $this->assertNull($trm->refresh()->nim);
    }

    public function test_admin_configures_nim_format(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/master-data/penomoran')
            ->assertOk()
            ->assertSee('Format NIM')
            ->assertSee('{PRODI}{TAHUN}{URUT}');

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering', ['nim_format' => '{PRODI}{TAHUN}', 'nim_digits' => 4, 'nim_scope' => 'prodi'])
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering', ['nim_format' => '{TAHUN2}{PRODI}<script>{URUT}', 'nim_digits' => 4, 'nim_scope' => 'prodi'])
            ->assertSessionHasErrors('nim_format');

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering', ['nim_format' => '{TAHUN2}{PRODI}{URUT}', 'nim_digits' => 3, 'nim_scope' => 'tahun'])
            ->assertSessionHasNoErrors();

        $trpl = $this->reRegistered('trpl@example.com', 'TRPL', '2027-04-05');
        $trm = $this->reRegistered('trm@example.com', 'TRM', '2027-04-06');

        $this->actingAs($admin, 'web')->post('/admin/re-registrations/generate-nim');

        $this->assertSame('27A001', $trpl->refresh()->nim);
        $this->assertSame('27B002', $trm->refresh()->nim);
    }

    public function test_admin_sets_nim_code_on_study_program(): void
    {
        $program = StudyProgram::where('code', 'BD')->first();

        $this->actingAs(User::factory()->create(), 'web')
            ->put('/admin/master-data/study-programs/' . $program->id, [
                'code' => 'BD',
                'nim_code' => 'd',
                'name' => $program->name,
                'degree' => 'D4',
                'quota' => 40,
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('D', $program->refresh()->nim_code);
    }

    /**
     * Camaba diterima dengan satu pembayaran daftar ulang pada tanggal tertentu.
     */
    private function reRegistered(string $email, string $programCode, string $transferDate, string $status = 'valid', int $amount = 5000000): Applicant
    {
        $applicant = $this->registerApplicant([
            'email' => $email,
            'study_program_id' => StudyProgram::where('code', $programCode)->value('id'),
        ]);

        $applicant->update(['payment_status' => 'valid', 'selection_status' => 'diterima']);

        $invoice = Invoice::create([
            'applicant_id' => $applicant->id,
            'invoice_number' => 'INV/DU/2027/' . $applicant->registration_number,
            'type' => 're_registration',
            'total_amount' => 5000000,
            'status' => $status === 'valid' && $amount >= 5000000 ? 'paid' : 'partial',
        ]);

        Payment::create([
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/DU/' . $applicant->id,
            'transfer_date' => $transferDate,
            'amount' => $amount,
            'status' => $status,
            'verified_at' => $status === 'valid' ? now() : null,
        ]);

        return $applicant;
    }
}
