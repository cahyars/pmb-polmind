<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LoginAsController;
use App\Models\AdmissionWave;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class LoginAsTest extends TestCase
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

    public function test_only_admin_pmb_can_open_login_as(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/dashboard')
            ->assertSee(route('admin.login-as.index'));

        $operator = User::factory()->operator()->create();

        $this->actingAs($operator, 'web')
            ->get('/admin/dashboard')
            ->assertDontSee(route('admin.login-as.index'));

        $this->actingAs($operator, 'web')->get(route('admin.login-as.index'))->assertForbidden();
    }

    public function test_admin_finds_applicant_by_any_part_of_name_and_sees_detail(): void
    {
        $applicant = $this->registerApplicant(['full_name' => 'Earlyana Jasmine Wijatniko']);
        $this->registerApplicant(['full_name' => 'Budi Santoso', 'email' => 'budi@example.com']);
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->getJson(route('admin.login-as.suggestions', ['q' => 'jasmine']))
            ->assertOk()
            ->assertExactJson([['id' => $applicant->registration_number, 'name' => 'EARLYANA JASMINE WIJATNIKO']]);

        $this->actingAs($admin, 'web')
            ->getJson(route('admin.login-as.suggestions', ['q' => '%%']))
            ->assertExactJson([]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.login-as.index', ['applicant' => $applicant->registration_number . ' - EARLYANA JASMINE WIJATNIKO']))
            ->assertOk()
            ->assertSee('EARLYANA JASMINE WIJATNIKO')
            ->assertSee($applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name)
            ->assertSee(route('admin.login-as.store', $applicant->registration_number));
    }

    public function test_login_as_opens_camaba_portal_and_logout_keeps_admin_session(): void
    {
        $applicant = $this->registerApplicant();
        $admin = User::factory()->create(['name' => 'Bu Admin']);

        $this->actingAs($admin, 'web')
            ->post(route('admin.login-as.store', $applicant->registration_number))
            ->assertRedirect(route('camaba.dashboard'));

        $this->assertAuthenticatedAs($applicant, 'applicant');

        $this->get(route('camaba.dashboard'))
            ->assertOk()
            ->assertSee('Login As oleh Bu Admin');

        // Password camaba tidak bisa diganti saat Login As.
        $this->put(route('camaba.account.password'), [
            'current_password' => 'rahasia123',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertSessionHasErrors();
        $this->assertTrue(Hash::check('rahasia123', $applicant->fresh()->password));

        $this->post(route('logout'))
            ->assertRedirect(route('admin.login-as.index', ['applicant' => $applicant->registration_number]));

        $this->assertGuest('applicant');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertNull(session(LoginAsController::SESSION_KEY));
    }
}
