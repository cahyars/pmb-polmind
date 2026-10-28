<?php

namespace Tests\Feature;

use App\Models\Brochure;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrochureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);
    }

    public function test_operator_uploads_brochure_and_visitor_downloads_it(): void
    {
        $operator = User::factory()->operator()->create();

        $this->get('/')->assertOk()->assertDontSee('Unduh Brosur');

        $this->actingAs($operator, 'web')
            ->get('/admin/brochures')
            ->assertOk()
            ->assertSee('Unggah Brosur');

        $this->actingAs($operator, 'web')
            ->post('/admin/brochures', [
                'title' => 'Brosur PMB 2027',
                'description' => 'Prodi, jalur masuk, dan biaya',
                'file' => UploadedFile::fake()->create('brosur final.pdf', 900, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $brochure = Brochure::firstOrFail();
        Storage::disk('local')->assertExists($brochure->file_path);
        $this->assertStringStartsWith('brochures/', $brochure->file_path);

        auth('web')->logout();

        // Satu brosur: tombol di hero dan Jalur Masuk langsung mengunduh.
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Konsultasi WA', 'Unduh Brosur'])
            ->assertDontSee('id="brosur"', false)
            ->assertSee('href="' . route('brochures.download', $brochure) . '"', false);

        $this->get('/brosur/' . $brochure->id)
            ->assertOk()
            ->assertDownload('brosur-pmb-2027.pdf');

        $this->assertSame(1, $brochure->refresh()->download_count);

        // Lebih dari satu brosur: tombol menampilkan pilihan brosur.
        Brochure::create(['title' => 'Brosur Kelas Karyawan', 'file_path' => 'brochures/b.pdf', 'file_name' => 'b.pdf', 'file_size' => 2400000]);

        $this->get('/')
            ->assertSee('aria-haspopup="true"', false)
            ->assertSee('Brosur Kelas Karyawan')
            ->assertSee('PDF · 2,3 MB');

        Brochure::where('title', 'Brosur Kelas Karyawan')->delete();

        // Disembunyikan: tidak tampil dan tidak bisa diunduh.
        $this->actingAs($operator, 'web')->patch('/admin/brochures/' . $brochure->id . '/toggle');
        auth('web')->logout();

        $this->get('/')->assertDontSee('Brosur PMB 2027');
        $this->get('/brosur/' . $brochure->id)->assertNotFound();
    }

    public function test_brochure_upload_validation_replace_and_delete(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->post('/admin/brochures', ['title' => 'Script', 'file' => UploadedFile::fake()->create('brosur.php', 10, 'text/x-php')])
            ->assertSessionHasErrors('file');

        $this->actingAs($admin, 'web')
            ->post('/admin/brochures', ['title' => 'Terlalu besar', 'file' => UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf')])
            ->assertSessionHasErrors('file');

        $this->actingAs($admin, 'web')
            ->post('/admin/brochures', ['title' => 'Poster', 'file' => UploadedFile::fake()->image('poster.png')]);

        $brochure = Brochure::firstOrFail();
        $oldPath = $brochure->file_path;

        $this->actingAs($admin, 'web')
            ->put('/admin/brochures/' . $brochure->id, ['title' => 'Poster Baru', 'sort_order' => 2, 'file' => UploadedFile::fake()->create('poster.pdf', 50, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $brochure->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($brochure->file_path);
        $this->assertSame('PDF', $brochure->extension);

        $this->actingAs($admin, 'web')->delete('/admin/brochures/' . $brochure->id);

        Storage::disk('local')->assertMissing($brochure->file_path);
        $this->assertSame(0, Brochure::count());
    }
}
