<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\LoginInfoMessage;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class AdminEditBiodataTest extends TestCase
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

    /**
     * Isian form edit biodata admin untuk $applicant, mempertahankan sekolahnya.
     */
    private function form(Applicant $applicant, array $overrides = []): array
    {
        return array_merge([
            'full_name' => $applicant->full_name,
            'nik' => $applicant->nik,
            'gender' => $applicant->gender,
            'birth_place' => 'Batam',
            'birth_date' => '2006-03-14',
            'religion_id' => 1,
            'citizenship' => 'ID',
            'special_need_id' => 0,
            'kps_recipient' => 0,
            'almamater_size' => 'L',
            'email' => $applicant->email,
            'phone' => $applicant->phone,

            'school_region_id' => '316101',
            'is_manual_school' => 0,
            'school_npsn' => '11001234',
            'school_name' => 'SMK Negeri 1 Batam',
            'school_type' => 'SMK',
            'school_status' => 'Negeri',
        ], $overrides);
    }

    public function test_operator_edits_biodata_of_online_applicant(): void
    {
        $applicant = $this->registerApplicant();
        $operator = User::factory()->operator()->create(['name' => 'Pak Operator']);

        $this->actingAs($operator, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertOk()
            ->assertSee(route('admin.applicants.edit', $applicant->registration_number))
            ->assertDontSee('Kebutuhan Khusus</th>', false);

        $this->actingAs($operator, 'web')
            ->get(route('admin.applicants.edit', $applicant->registration_number))
            ->assertOk()
            ->assertSee('Edit Biodata Camaba')
            ->assertSee('Daftar online');

        $this->actingAs($operator, 'web')
            ->put(route('admin.applicants.update', $applicant->registration_number), $this->form($applicant, [
                'full_name' => 'ammar maulana',
                'major' => 'Rekayasa Perangkat Lunak',
                'graduation_year' => 2024,
                'mother_name' => 'siti aminah',
                'father_name' => 'Budi',
                'father_special_need_id' => 0,
                'mother_special_need_id' => 0,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.applicants.show', $applicant->registration_number))
            ->assertSessionHas('success', 'Biodata AMMAR MAULANA berhasil diperbarui oleh Pak Operator.');

        $applicant->refresh();

        $this->assertSame('AMMAR MAULANA', $applicant->full_name);
        $this->assertSame('2006-03-14', $applicant->birth_date->format('Y-m-d'));
        $this->assertSame('Rekayasa Perangkat Lunak', $applicant->education->major);
        $this->assertSame('SMK NEGERI 1 BATAM', $applicant->education->school_name);
        $this->assertSame('SITI AMINAH', $applicant->parentData->mother_name);
        $this->assertNull($applicant->address);
        // Password pilihan camaba sendiri tidak disentuh.
        $this->assertTrue(Hash::check('rahasia123', $applicant->password));
    }

    public function test_birth_date_fix_moves_default_pin_of_walk_in_applicant(): void
    {
        $applicant = $this->registerApplicant(['email' => 'walkin@example.com']);
        $applicant->update(['birth_date' => '2007-05-15', 'password' => '15052007', 'registered_via' => 'kampus']);
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->put(route('admin.applicants.update', $applicant->registration_number), $this->form($applicant, [
                'birth_date' => '2007-05-16',
            ]))
            ->assertSessionHasNoErrors();

        $applicant->refresh();

        $this->assertTrue(Hash::check('16052007', $applicant->password));
        $this->assertSame('16052007', LoginInfoMessage::activePin($applicant));
    }

    public function test_filled_address_cannot_be_cleared_and_nik_stays_unique(): void
    {
        $other = $this->registerApplicant(['email' => 'lain@example.com']);
        $applicant = $this->registerApplicant();
        $applicant->address()->create(['address' => 'Jl. Merdeka 10', 'village_name' => 'Belian', 'region_id' => '316101']);
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->put(route('admin.applicants.update', $applicant->registration_number), $this->form($applicant, [
                'address' => '',
                'nik' => $other->nik,
            ]))
            ->assertSessionHasErrors(['address', 'nik']);

        $this->assertSame('Jl. Merdeka 10', $applicant->address()->value('address'));
    }
}
