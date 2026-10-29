<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class CamabaPasswordTest extends TestCase
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

    private function applicant(): Applicant
    {
        $applicant = $this->registerApplicant();
        $applicant->update(['birth_date' => '2007-03-16']);

        return $applicant->refresh();
    }

    public function test_admin_or_operator_resets_forgotten_password_to_birth_date_pin(): void
    {
        $applicant = $this->applicant();
        $operator = User::factory()->operator()->create(['name' => 'Pak Operator']);
        $show = route('admin.applicants.show', $applicant->registration_number);

        // Camaba online memakai password sendiri: yang tampil tombol reset, bukan kirim PIN.
        $this->actingAs($operator, 'web')
            ->get($show)
            ->assertSee('Reset PIN ke Tanggal Lahir')
            ->assertDontSee('Kirim Info Login ke WhatsApp');

        $this->actingAs($operator, 'web')
            ->from($show)
            ->patch(route('admin.applicants.reset-password', $applicant->registration_number))
            ->assertRedirect($show)
            ->assertSessionHas('login_reset', true);

        $this->actingAs($operator, 'web')
            ->get($show)
            ->assertDontSee('Reset PIN ke Tanggal Lahir')
            ->assertSee('Kirim Info Login ke WhatsApp');

        $followUp = $applicant->followUps()->sole();
        $this->assertSame('Pak Operator', $followUp->officer_name);
        $this->assertStringContainsString('direset', $followUp->note);

        auth('web')->logout();

        $this->post('/login', ['email' => $applicant->email, 'password' => 'rahasia123'])->assertSessionHasErrors();
        $this->post('/login', ['email' => $applicant->registration_number, 'password' => '16032007'])->assertRedirect('/camaba/dashboard');
    }

    public function test_reset_needs_birth_date(): void
    {
        $applicant = $this->registerApplicant();
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertSee('Reset PIN butuh tanggal lahir');

        $this->actingAs($admin, 'web')
            ->patch(route('admin.applicants.reset-password', $applicant->registration_number))
            ->assertSessionHasErrors();

        $this->post('/login', ['email' => $applicant->email, 'password' => 'rahasia123']);
        $this->assertAuthenticatedAs($applicant->refresh(), 'applicant');
    }

    public function test_camaba_changes_default_pin_to_own_password(): void
    {
        $applicant = $this->applicant();
        $this->actingAs(User::factory()->create(), 'web')
            ->patch(route('admin.applicants.reset-password', $applicant->registration_number));
        auth('web')->logout();

        $this->post('/login', ['email' => $applicant->email, 'password' => '16032007']);

        $this->get('/camaba/dashboard')->assertSee('PIN tanggal lahir')->assertSee(route('camaba.account.edit'));
        $this->get('/camaba/akun')->assertOk()->assertSee('Ganti Password')->assertSee('Anda masih memakai');

        $this->put('/camaba/akun/password', ['current_password' => 'salah', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasErrors('current_password');
        $this->put('/camaba/akun/password', ['current_password' => '16032007', 'password' => '16032007', 'password_confirmation' => '16032007'])
            ->assertSessionHasErrors('password');
        $this->put('/camaba/akun/password', ['current_password' => '16032007', 'password' => 'baru123', 'password_confirmation' => 'baru123'])
            ->assertSessionHasErrors('password');

        $this->put('/camaba/akun/password', ['current_password' => '16032007', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->get('/camaba/dashboard')->assertDontSee('PIN tanggal lahir');

        // PIN lama tidak berlaku lagi, dan admin tidak lagi melihat tombol kirim PIN.
        $this->post('/logout');
        $this->post('/login', ['email' => $applicant->email, 'password' => '16032007'])->assertSessionHasErrors();
        $this->post('/login', ['email' => $applicant->email, 'password' => 'passwordbaru1'])->assertRedirect('/camaba/dashboard');

        $this->actingAs(User::factory()->create(), 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertDontSee('Kirim Info Login ke WhatsApp')
            ->assertSee('Reset PIN ke Tanggal Lahir');
    }
}
