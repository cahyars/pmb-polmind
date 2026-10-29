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
            ->get('/admin/nim')
            ->assertOk()
            ->assertSee('Generate NIM (3)')
            ->assertSee('A20270001');

        $this->actingAs($admin, 'web')
            ->post('/admin/nim/generate')
            ->assertSessionHas('success', '3 NIM berhasil dibuat sesuai urutan tanggal daftar ulang. Data siap ditarik SIAKAD.');

        $this->assertSame('A20270001', $early->refresh()->nim);
        $this->assertSame('A20270002', $late->refresh()->nim);
        $this->assertSame('B20270001', $manufaktur->refresh()->nim);
        $this->assertNull($waiting->refresh()->nim);
        $this->assertNotNull($early->nim_generated_at);

        // Mahasiswa yang daftar ulang belakangan melanjutkan nomor urut, NIM lama tidak berubah.
        $next = $this->reRegistered('next@example.com', 'TRPL', '2027-04-02');

        $this->actingAs($admin, 'web')->post('/admin/nim/generate');

        $this->assertSame('A20270003', $next->refresh()->nim);
        $this->assertSame('A20270001', $early->refresh()->nim);

        $this->actingAs($admin, 'web')
            ->post('/admin/nim/generate')
            ->assertSessionHasErrors();
    }

    public function test_nim_page_supports_manual_nim_and_release_until_pulled_by_siakad(): void
    {
        $admin = User::factory()->create();
        $first = $this->reRegistered('satu@example.com', 'TRPL', '2027-04-05');
        $second = $this->reRegistered('dua@example.com', 'TRPL', '2027-04-06');
        $waiting = $this->reRegistered('tunggu@example.com', 'TRPL', '2027-04-07', 'waiting_verification');

        $this->actingAs($admin, 'web')
            ->get('/admin/nim')
            ->assertOk()
            ->assertSee('Kelulusan')
            ->assertSee('href="' . route('admin.nim.index') . '"', false)
            ->assertSeeInOrder([$first->full_name, 'Menunggu daftar ulang'])
            ->assertSee('Generate NIM (2)');

        // Belum daftar ulang valid: tidak bisa diberi NIM.
        $this->actingAs($admin, 'web')->post(route('admin.nim.generate-one', $waiting))->assertSessionHasErrors();
        $this->actingAs($admin, 'web')->put(route('admin.nim.update', $waiting), ['nim' => 'X1'])->assertSessionHasErrors();

        $this->actingAs($admin, 'web')->post(route('admin.nim.generate-one', $first))->assertSessionHasNoErrors();
        $this->assertSame('A20270001', $first->refresh()->nim);
        $this->assertSame('siap_sinkron', $first->sync_status);

        // NIM manual dan tidak boleh kembar.
        $this->actingAs($admin, 'web')->put(route('admin.nim.update', $second), ['nim' => 'A20270001'])->assertSessionHasErrors('nim');
        $this->actingAs($admin, 'web')->put(route('admin.nim.update', $second), ['nim' => 'tf-2027-77'])->assertSessionHasNoErrors();
        $this->assertSame('TF-2027-77', $second->refresh()->nim);

        // NIM terakhir dihapus: nomor urutnya dipakai lagi dan data kembali belum siap sinkron.
        $this->actingAs($admin, 'web')->delete(route('admin.nim.destroy', $first))->assertSessionHasNoErrors();
        $this->assertNull($first->refresh()->nim);
        $this->assertSame('belum_siap', $first->sync_status);

        $this->actingAs($admin, 'web')->post(route('admin.nim.generate-one', $first));
        $this->assertSame('A20270001', $first->refresh()->nim);

        // Setelah ditarik SIAKAD, NIM terkunci.
        $first->update(['sync_status' => 'sudah_sinkron']);
        $this->actingAs($admin, 'web')->delete(route('admin.nim.destroy', $first))->assertSessionHasErrors();
        $this->actingAs($admin, 'web')->put(route('admin.nim.update', $first), ['nim' => 'BARU1'])->assertSessionHasErrors();
        $this->assertSame('A20270001', $first->refresh()->nim);
    }

    public function test_study_program_without_nim_code_is_skipped(): void
    {
        StudyProgram::where('code', 'TRM')->update(['nim_code' => null]);

        $trpl = $this->reRegistered('trpl@example.com', 'TRPL', '2027-04-05');
        $trm = $this->reRegistered('trm@example.com', 'TRM', '2027-04-06');

        $this->actingAs(User::factory()->create(), 'web')
            ->post('/admin/nim/generate')
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

        $this->actingAs($admin, 'web')->post('/admin/nim/generate');

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

        // Daftar ulang valid: lunas, atau angsuran ke-1 bagi camaba dengan cicilan.
        $applicant->update([
            'payment_status' => 'valid',
            'selection_status' => 'diterima',
            're_registration_status' => $status === 'valid' ? 'daftar_ulang_valid' : 'menunggu_verifikasi',
        ]);

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
