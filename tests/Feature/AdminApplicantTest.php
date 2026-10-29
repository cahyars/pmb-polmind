<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class AdminApplicantTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_applicant_list_shows_new_columns_and_statuses(): void
    {
        $peminat = $this->registerApplicant(['full_name' => 'Sulaiman Laa', 'email' => 'sulaiman@example.com', 'phone' => '085219669610']);

        $lulus = $this->registerApplicant(['full_name' => 'Zaidan Azffa', 'email' => 'zaidan@example.com']);
        $lulus->update([
            'second_study_program_id' => StudyProgram::where('code', 'TRM')->value('id'),
            'payment_status' => 'valid',
            'selection_status' => 'diterima',
            're_registration_status' => 'daftar_ulang_valid',
            'nim' => '2741001',
        ]);

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/applicants')
            ->assertOk()
            ->assertSeeInOrder(['Foto', 'Nama &amp; Kode Pendaftar', 'Jalur Pendaftaran', 'Program Studi', 'Status Pendaftar', 'Kontak', 'Aksi'], false)
            ->assertSee('SULAIMAN LAA')
            ->assertSee($peminat->registration_number)
            ->assertSee('PMB 2027 - Gelombang 1')
            ->assertSee('Umum - Reguler A')
            ->assertSee('D4 - Teknologi Rekayasa Perangkat Lunak')
            ->assertSee('D4 - Teknologi Rekayasa Manufaktur')
            ->assertSee('Belum Bayar (Peminat)')
            ->assertSee('Lulus')
            ->assertSee('Sudah Daftar Ulang')
            ->assertSee('Sudah Memiliki NIM')
            ->assertSee('https://wa.me/6285219669610', false)
            // URL hapus sama dengan URL detail (beda method), jadi yang dicek adalah form hapusnya.
            ->assertSee('action="' . route('admin.applicants.destroy', $peminat->registration_number) . '"', false)
            ->assertDontSee('action="' . route('admin.applicants.destroy', $lulus->registration_number) . '"', false);
    }

    public function test_admin_can_delete_only_unpaid_applicants(): void
    {
        $admin = User::factory()->create();

        $peminat = $this->registerApplicant(['email' => 'peminat@example.com']);
        $paid = $this->registerApplicant(['email' => 'lunas@example.com']);
        $paid->update(['payment_status' => 'valid']);

        $this->actingAs($admin, 'web')
            ->delete(route('admin.applicants.destroy', $paid->registration_number))
            ->assertSessionHasErrors();

        $this->assertNotSoftDeleted($paid);

        $this->actingAs($admin, 'web')
            ->delete(route('admin.applicants.destroy', $peminat->registration_number))
            ->assertSessionHas('success');

        $this->assertSame(0, Applicant::withTrashed()->whereKey($peminat->id)->count());
        $this->assertDatabaseMissing('invoices', ['applicant_id' => $peminat->id]);
        $this->assertSame(1, Applicant::count());
    }

    public function test_deleting_latest_applicant_reuses_registration_and_invoice_numbers(): void
    {
        $admin = User::factory()->create();

        $first = $this->registerApplicant(['email' => 'satu@example.com']);
        $dummy = $this->registerApplicant(['email' => 'dummy@example.com']);

        $this->assertSame('A120270002', $dummy->registration_number);
        $this->assertSame('INV/PMB/2027/00002', $dummy->registrationInvoice->invoice_number);

        $this->actingAs($admin, 'web')
            ->delete(route('admin.applicants.destroy', $dummy->registration_number))
            ->assertSessionHas('success');

        auth('web')->logout();

        // Email dan NIK camaba yang dihapus bisa dipakai mendaftar lagi.
        $again = $this->registerApplicant(['email' => 'dummy@example.com', 'nik' => $dummy->nik]);

        $this->assertSame('A120270002', $again->registration_number);
        $this->assertSame('INV/PMB/2027/00002', $again->registrationInvoice->invoice_number);
        $this->assertSame('A120270001', $first->refresh()->registration_number);
    }

    public function test_purge_command_cleans_previously_soft_deleted_applicants(): void
    {
        $this->registerApplicant(['email' => 'satu@example.com'])->delete();
        $this->registerApplicant(['email' => 'dua@example.com'])->delete();

        $this->artisan('pmb:hapus-permanen')->assertSuccessful();

        $this->assertSame(0, Applicant::withTrashed()->count());

        $this->assertSame('A120270001', $this->registerApplicant(['email' => 'baru@example.com'])->registration_number);
    }
}
