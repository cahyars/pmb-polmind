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

        $this->assertSoftDeleted($peminat);
        $this->assertSame(1, Applicant::count());
    }
}
