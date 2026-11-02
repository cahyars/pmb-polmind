<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\InformationSource;
use App\Models\RefOption;
use App\Models\User;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\SourceReport;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class InformationSourceTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_operator_manages_sources_and_register_form_follows_them(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator, 'web')
            ->get('/admin/information-sources')
            ->assertOk()
            ->assertSee('Instagram')
            ->assertSee('Tambah Sumber Informasi');

        $this->actingAs($operator, 'web')
            ->post('/admin/information-sources', ['name' => 'Pameran Pendidikan', 'sort_order' => 5])
            ->assertSessionHasNoErrors();

        $this->actingAs($operator, 'web')
            ->post('/admin/information-sources', ['name' => 'instagram'])
            ->assertSessionHasErrors('name');

        auth('web')->logout();

        $this->get('/register')->assertOk()->assertSeeInOrder(['Pameran Pendidikan', 'Instagram']);

        $applicant = $this->registerApplicant(['email' => 'pameran@example.com', 'source_information' => 'Pameran Pendidikan']);
        $this->assertSame('Pameran Pendidikan', $applicant->source_information);

        // Ganti nama ikut memperbarui camaba yang sudah memilihnya.
        $source = InformationSource::where('name', 'Pameran Pendidikan')->firstOrFail();
        $this->actingAs($operator, 'web')
            ->put('/admin/information-sources/' . $source->id, ['name' => 'Expo Kampus', 'sort_order' => 5])
            ->assertSessionHasNoErrors();
        $this->assertSame('Expo Kampus', $applicant->fresh()->source_information);

        // Sumber yang sudah dipakai tidak bisa dihapus, cukup disembunyikan.
        $this->actingAs($operator, 'web')
            ->delete('/admin/information-sources/' . $source->id)
            ->assertSessionHasErrors();
        $this->assertModelExists($source);

        $this->actingAs($operator, 'web')->patch('/admin/information-sources/' . $source->id . '/toggle');
        $this->assertFalse($source->fresh()->is_active);
        auth('web')->logout();

        $this->get('/register')->assertOk()->assertDontSee('<option value="Expo Kampus"', false);
        $this->post('/register', $this->registrationData(['email' => 'lain@example.com', 'source_information' => 'Expo Kampus']))
            ->assertSessionHasErrors('source_information');

        $unused = InformationSource::where('name', 'TikTok')->firstOrFail();
        $this->actingAs($operator, 'web')
            ->delete('/admin/information-sources/' . $unused->id)
            ->assertSessionHasNoErrors();
        $this->assertModelMissing($unused);
    }

    public function test_source_report_counts_conversion_per_source(): void
    {
        $a = $this->registerApplicant(['email' => 'a@example.com', 'source_information' => 'Instagram']);
        $this->registerApplicant(['email' => 'b@example.com', 'source_information' => 'Instagram']);
        $this->registerApplicant(['email' => 'c@example.com', 'source_information' => 'Website']);
        $this->registerApplicant(['email' => 'd@example.com']);

        $a->updateQuietly(['payment_status' => 'valid', 'selection_status' => 'diterima']);

        $report = SourceReport::build(new ReportFilter());
        $sources = $report['sources']->keyBy('key');

        $this->assertSame(4, $report['total']);
        $this->assertSame(3, $report['filled']);
        $this->assertSame('Instagram', $report['top']['label']);
        $this->assertSame(2, $sources['Instagram']['total']);
        $this->assertSame(1, $sources['Instagram']['paid']);
        $this->assertEquals(50, $sources['Instagram']['paid_rate']);
        $this->assertSame(1, $sources['Instagram']['accepted']);
        $this->assertSame(1, $sources[InformationSource::NOT_FILLED_KEY]['total']);
        // Sumber aktif tanpa pendaftar tetap muncul; "Tidak diisi" selalu di akhir.
        $this->assertSame(0, $sources['TikTok']['total']);
        $this->assertSame(InformationSource::NOT_FILLED_KEY, $report['sources']->last()['key']);
        $this->assertSame(['Instagram', 'Website', 'Lainnya'], $report['trend']['datasets']->pluck('label')->all());

        $this->assertSame(2, SourceReport::build(new ReportFilter(['source' => 'Instagram']))['total']);

        $admin = User::factory()->operator()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/reports/sumber-informasi')
            ->assertOk()
            ->assertSee('Konversi per Sumber Informasi')
            ->assertSee(route('admin.applicants.index', ['source' => 'Instagram', 'summary' => 'belum_bayar']));

        // Angka di laporan membuka Data Camaba yang tersaring per sumber.
        $this->actingAs($admin, 'web')
            ->get('/admin/applicants?source=Website')
            ->assertOk()
            ->assertSee('c@example.com')
            ->assertDontSee('a@example.com');

        $this->actingAs($admin, 'web')
            ->get('/admin/applicants?source=' . InformationSource::NOT_FILLED_KEY)
            ->assertOk()
            ->assertSee('d@example.com')
            ->assertDontSee('c@example.com');
    }
}
