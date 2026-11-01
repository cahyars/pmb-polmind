<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\Setting;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class CamabaRegistrationTest extends TestCase
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

    public function test_register_form_asks_birth_place_date_and_graduation_year(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSeeInOrder(['Tempat Lahir', 'Tanggal Lahir', 'Sekolah Asal', 'Tahun Lulus']);

        $this->post('/register', $this->registrationData([
            'birth_place' => 'Bekasi',
            'birth_date' => '2007-05-15',
            'graduation_year' => 2026,
        ]))->assertRedirect('/camaba/dashboard');

        $applicant = Applicant::where('email', 'ammar@example.com')->firstOrFail();

        $this->assertSame('Bekasi', $applicant->birth_place);
        $this->assertSame('2007-05-15', $applicant->birth_date->format('Y-m-d'));
        $this->assertSame(2026, (int) $applicant->education->graduation_year);
    }

    public function test_birth_place_date_and_graduation_year_are_required(): void
    {
        $this->post('/register', $this->registrationData(['birth_place' => '', 'birth_date' => '', 'graduation_year' => '']))
            ->assertSessionHasErrors(['birth_place', 'birth_date', 'graduation_year']);

        $this->post('/register', $this->registrationData(['birth_date' => now()->addDay()->format('Y-m-d')]))
            ->assertSessionHasErrors(['birth_date']);

        $this->assertSame(0, Applicant::count());
    }
}
