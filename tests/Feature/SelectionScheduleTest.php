<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\FeeComponent;
use App\Models\RefOption;
use App\Models\SelectionComponent;
use App\Models\SelectionSchedule;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\SelectionScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SelectionScheduleTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class, SelectionScheduleSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_admin_manages_components_schedules_timeline_and_pics(): void
    {
        $admin = User::factory()->create();
        $wave = AdmissionWave::where('code', 'GEL2')->firstOrFail();

        $this->actingAs($admin, 'web')
            ->post(route('admin.master-data.selection-components.store'), ['code' => 'tes_kesehatan', 'name' => 'Tes Kesehatan', 'is_active' => 1])
            ->assertSessionHasNoErrors();

        $component = SelectionComponent::where('code', 'TES_KESEHATAN')->firstOrFail();

        $this->actingAs($admin, 'web')
            ->post(route('admin.master-data.selection-schedules.store'), [
                'admission_wave_id' => $wave->id,
                'selection_component_id' => $component->id,
                'test_date' => '2027-04-04',
                'start_time' => '10:00',
                'end_time' => '09:00',
            ])
            ->assertSessionHasErrors('end_time');

        $this->actingAs($admin, 'web')
            ->post(route('admin.master-data.selection-schedules.store'), [
                'admission_wave_id' => $wave->id,
                'selection_component_id' => $component->id,
                'test_date' => '2027-04-04',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'location' => 'Klinik Kampus',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin, 'web')
            ->put(route('admin.master-data.admission-waves.timeline', $wave), [
                'announcement_date' => '2027-04-08',
                're_registration_start_date' => '2027-04-08',
                're_registration_end_date' => '2027-04-18',
            ])
            ->assertSessionHasNoErrors();

        $schedules = $wave->selectionSchedules()->get();

        $this->actingAs($admin, 'web')
            ->put(route('admin.master-data.admission-waves.pics', $wave), [
                'pic_name' => $schedules->mapWithKeys(fn ($schedule) => [$schedule->id => ' Budi Santoso, M.T. '])->all(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Budi Santoso, M.T.'], $wave->selectionSchedules()->pluck('pic_name')->unique()->values()->all());
        $this->assertSame('2027-04-18', $wave->refresh()->re_registration_end_date->toDateString());

        $this->actingAs($admin, 'web')
            ->get(route('admin.master-data.show', ['pic-tes', 'gelombang' => $wave->id]))
            ->assertOk()
            ->assertSee('Tes Kesehatan')
            ->assertSee('2 dari 2 tes sudah ada PIC');
    }

    public function test_landing_and_forms_follow_master_data(): void
    {
        FeeComponent::where('code', 'SPI_AWAL')->update(['amount' => 15000000, 'is_active' => true]);
        AdmissionWave::create([
            'pmb_year_id' => AdmissionWave::value('pmb_year_id'),
            'code' => 'GEL5',
            'name' => 'Gelombang 5',
            'start_date' => '2027-08-02',
            'end_date' => '2027-09-03',
            'registration_fee' => 300000,
            'status' => 'draft',
        ]);

        // Jadwal ringkas: Pendaftaran, Seleksi, Pengumuman Seleksi, Daftar Ulang (tanpa nama tes).
        $this->get('/')
            ->assertOk()
            ->assertSee('Gelombang 5')
            ->assertSeeInOrder(['Gelombang 1', 'Pendaftaran', '2 Nov 2026 – 29 Jan 2027', 'Seleksi', '30 Jan 2027', 'Pengumuman Seleksi', '2 Feb 2027', 'Daftar Ulang', '2 – 12 Feb 2027'])
            ->assertSeeInOrder(['Gelombang 5', 'Pendaftaran', '2 Agt – 3 Sep 2027', 'Seleksi', 'Menyusul'])
            ->assertDontSee('Tes Tertulis 30 Jan')
            ->assertSee('Rp15.000.000')
            ->assertSee('Wajib: Pas Foto')
            ->assertSee('D4 Teknologi Rekayasa Perangkat Lunak')
            ->assertSee('id="prodi-trpl"', false)
            ->assertSee(asset('images/prodi/trpl.webp'), false)
            ->assertSee(asset('images/prodi/bd.webp'), false)
            ->assertSee(asset('images/prodi/trm.webp'), false);

        $this->get('/register')
            ->assertOk()
            ->assertSeeInOrder(['Pendaftaran', '2 Nov 2026 – 29 Jan 2027', 'Seleksi', '30 Jan 2027', 'Pengumuman Seleksi', '2 Feb 2027', 'Daftar Ulang', '2 – 12 Feb 2027']);

        $applicant = $this->registerApplicant();
        $applicant->update(['payment_status' => 'valid']);

        SelectionSchedule::where('admission_wave_id', $applicant->admission_wave_id)->update(['pic_name' => 'Dosen Rahasia', 'location' => 'Lab Komputer']);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/status-seleksi')
            ->assertOk()
            ->assertSee('Jadwal Seleksi Gelombang 1')
            ->assertSee('Lab Komputer')
            ->assertDontSee('Dosen Rahasia');
    }
}
