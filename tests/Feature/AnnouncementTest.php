<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private Applicant $peminat;

    private Applicant $seleksi;

    private Applicant $diterima;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);

        $this->peminat = $this->registerApplicant(['email' => 'peminat@example.com']);

        $this->seleksi = $this->registerApplicant(['email' => 'seleksi@example.com']);
        // Tanpa event agar pengumuman kaos otomatis tidak ikut terhitung di test ini.
        $this->seleksi->updateQuietly(['payment_status' => 'valid', 'registration_status' => 'biodata_lengkap']);

        $this->diterima = $this->registerApplicant(['email' => 'diterima@example.com']);
        $this->diterima->updateQuietly(['payment_status' => 'valid', 'registration_status' => 'biodata_lengkap', 'selection_status' => 'diterima']);
    }

    public function test_admin_sends_announcement_only_to_selected_categories(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->getJson(route('admin.announcements.recipient-count', ['audiences' => ['peminat', 'diterima']]))
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->actingAs($admin, 'web')
            ->getJson(route('admin.announcements.recipient-count', ['audiences' => ['semua']]))
            ->assertJson(['count' => 3]);

        $this->actingAs($admin, 'web')
            ->post(route('admin.announcements.store'), [
                'title' => 'Jadwal Daftar Ulang',
                'body' => "Daftar ulang dibuka.\nSegera lunasi.",
                'link_url' => 'https://example.com/info',
                'link_label' => 'Lihat Info',
                'audiences' => ['peminat', 'diterima'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.announcements.index'))
            ->assertSessionHas('success', 'Pengumuman "Jadwal Daftar Ulang" terkirim ke 2 camaba.');

        $announcement = Announcement::firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$this->peminat->id, $this->diterima->id],
            $announcement->applicants()->pluck('applicants.id')->all()
        );

        $this->actingAs($admin, 'web')
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Jadwal Daftar Ulang')
            ->assertSee('Peminat, Lolos Seleksi');

        $this->actingAs($admin, 'web')
            ->get(route('admin.announcements.show', $announcement))
            ->assertOk()
            ->assertSee($this->peminat->full_name)
            ->assertSee('Belum dibaca');
    }

    public function test_store_rejects_target_without_recipients(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->post(route('admin.announcements.store'), [
                'title' => 'Info Cadangan',
                'body' => 'Isi',
                'audiences' => ['cadangan'],
            ])
            ->assertSessionHasErrors('audiences');

        $this->assertSame(0, Announcement::count());
    }

    public function test_camaba_sees_bell_and_reading_marks_announcement_read(): void
    {
        $announcement = $this->sendTo(['tahap_seleksi'], 'Tes Seleksi Besok');

        // Peminat belum bayar tetap bisa membuka halaman pengumuman, tapi tidak menerima pengumuman ini.
        $this->actingAs($this->peminat, 'applicant')
            ->get(route('camaba.announcements.index'))
            ->assertOk()
            ->assertDontSee('Tes Seleksi Besok');

        $this->actingAs($this->peminat, 'applicant')
            ->get(route('camaba.announcements.show', $announcement->id))
            ->assertNotFound();

        $this->actingAs($this->seleksi, 'applicant')
            ->get(route('camaba.dashboard'))
            ->assertSee('Tes Seleksi Besok')
            ->assertSee('Tandai semua dibaca');

        $this->actingAs($this->seleksi, 'applicant')
            ->get(route('camaba.announcements.show', $announcement->id))
            ->assertOk()
            ->assertSee('Tes Seleksi Besok');

        $this->assertNotNull($this->seleksi->announcements()->first()->pivot->read_at);

        $this->actingAs($this->seleksi, 'applicant')
            ->get(route('camaba.dashboard'))
            ->assertDontSee('Tandai semua dibaca');
    }

    public function test_mark_all_read_and_withdraw(): void
    {
        $first = $this->sendTo(['semua'], 'Info Pertama');
        $this->sendTo(['semua'], 'Info Kedua');

        $this->actingAs($this->diterima, 'applicant')
            ->post(route('camaba.announcements.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $this->diterima->announcements()->wherePivotNull('read_at')->count());
        $this->assertSame(2, $this->peminat->announcements()->wherePivotNull('read_at')->count());

        $this->actingAs(User::factory()->create(), 'web')
            ->delete(route('admin.announcements.destroy', $first))
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertSame(1, $this->peminat->announcements()->count());
    }

    public function test_login_as_does_not_mark_read(): void
    {
        $announcement = $this->sendTo(['diterima'], 'Info Almamater');

        $this->actingAs(User::factory()->create(), 'web')
            ->post(route('admin.login-as.store', $this->diterima->registration_number));

        $this->get(route('camaba.announcements.show', $announcement->id))->assertOk();

        $this->assertNull($this->diterima->announcements()->first()->pivot->read_at);
    }

    private function sendTo(array $audiences, string $title): Announcement
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->post(route('admin.announcements.store'), [
                'title' => $title,
                'body' => 'Isi ' . $title,
                'audiences' => $audiences,
            ])
            ->assertSessionHasNoErrors();

        return Announcement::where('title', $title)->firstOrFail();
    }
}
