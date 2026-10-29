<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\RefOption;
use App\Models\RegistrationPath;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class RegistrationPathTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_admin_adds_path_that_appears_on_landing_and_register(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/master-data/jalur')
            ->assertOk()
            ->assertSeeInOrder(['Umum', 'Prestasi', 'Undangan']);

        $this->actingAs($admin, 'web')
            ->post(route('admin.master-data.registration-paths.store'), [
                'name' => 'Kerja Sama Industri',
                'description' => 'Untuk karyawan perusahaan mitra.',
                'sort_order' => 4,
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $path = RegistrationPath::where('name', 'Kerja Sama Industri')->firstOrFail();
        $this->assertSame('kerja_sama_industri', $path->code);

        auth('web')->logout();

        $this->get('/')
            ->assertSee('Jalur Kerja Sama Industri')
            ->assertSee('Untuk karyawan perusahaan mitra.')
            ->assertSee(route('register', ['jalur' => 'kerja_sama_industri']), false);

        $this->get('/register?jalur=kerja_sama_industri')
            ->assertSee('Jalur Kerja Sama Industri');

        $applicant = $this->registerApplicant(['registration_path' => 'kerja_sama_industri']);

        $this->assertSame('Kerja Sama Industri', $applicant->registration_path_label);

        $this->actingAs($admin, 'web')
            ->get('/admin/applicants?registration_path=kerja_sama_industri')
            ->assertSee($applicant->full_name)
            ->assertSee('Jalur Kerja Sama Industri');
    }

    public function test_inactive_path_is_hidden_and_rejected_at_register(): void
    {
        $admin = User::factory()->create();
        $undangan = RegistrationPath::where('code', 'undangan')->firstOrFail();

        $this->actingAs($admin, 'web')
            ->patch(route('admin.master-data.registration-paths.toggle', $undangan))
            ->assertSessionHasNoErrors();

        $this->assertFalse($undangan->refresh()->is_active);

        auth('web')->logout();

        $this->get('/')->assertDontSee('Jalur Undangan');
        $this->get('/register')->assertDontSee('Jalur Undangan')->assertSee('Jalur Prestasi');

        $this->post('/register', $this->registrationData(['registration_path' => 'undangan']))
            ->assertSessionHasErrors('registration_path');
    }

    public function test_used_path_cannot_be_deleted_and_last_active_path_stays_active(): void
    {
        $admin = User::factory()->create();

        $this->registerApplicant(['registration_path' => 'prestasi']);

        $prestasi = RegistrationPath::where('code', 'prestasi')->firstOrFail();
        $undangan = RegistrationPath::where('code', 'undangan')->firstOrFail();
        $umum = RegistrationPath::where('code', 'umum')->firstOrFail();

        $this->actingAs($admin, 'web')
            ->delete(route('admin.master-data.registration-paths.destroy', $prestasi))
            ->assertSessionHasErrors();
        $this->assertModelExists($prestasi);

        $this->actingAs($admin, 'web')
            ->delete(route('admin.master-data.registration-paths.destroy', $undangan))
            ->assertSessionHas('success');
        $this->assertModelMissing($undangan);

        $this->actingAs($admin, 'web')->patch(route('admin.master-data.registration-paths.toggle', $prestasi));

        $this->actingAs($admin, 'web')
            ->patch(route('admin.master-data.registration-paths.toggle', $umum))
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'web')
            ->put(route('admin.master-data.registration-paths.update', $umum), ['name' => 'Umum', 'sort_order' => 1])
            ->assertSessionHasErrors();

        $this->assertTrue($umum->refresh()->is_active);
    }

    public function test_operator_cannot_manage_paths(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator, 'web')->get('/admin/master-data/jalur')->assertForbidden();
    }
}
