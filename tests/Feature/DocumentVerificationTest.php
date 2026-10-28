<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\DocumentType;
use App\Models\RefOption;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);

        Storage::fake('local');
    }

    private function upload(Applicant $applicant, string $code, string $status = 'menunggu_verifikasi'): ApplicantDocument
    {
        $path = 'applicant-documents/' . $applicant->registration_number . '/' . $code . '.jpg';
        Storage::disk('local')->put($path, 'isi');

        return ApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'document_type_id' => DocumentType::where('code', $code)->value('id'),
            'file_name' => strtolower($code) . '.jpg',
            'file_path' => $path,
            'file_extension' => 'jpg',
            'file_size_kb' => 20,
            'status' => $status,
            'uploaded_at' => now(),
        ]);
    }

    public function test_queue_shows_one_row_per_applicant_with_waiting_documents(): void
    {
        $alex = $this->registerApplicant(['full_name' => 'Alex', 'email' => 'alex@example.com']);
        $this->upload($alex, 'KK');
        $this->upload($alex, 'IDENTITAS');

        $budi = $this->registerApplicant(['full_name' => 'Budi', 'email' => 'budi@example.com']);
        $this->upload($budi, 'KK', 'diterima');

        $this->registerApplicant(['full_name' => 'Tanpa Berkas', 'email' => 'kosong@example.com']);

        $html = $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/documents')
            ->assertOk()
            ->assertSee('2 Camaba')
            ->assertSee('2 berkas perlu diverifikasi')
            ->assertSee('Kartu Keluarga')
            ->assertDontSee('TANPA BERKAS')
            ->assertSeeInOrder([$alex->registration_number, $budi->registration_number])
            ->assertSee(route('admin.documents.show', $alex->registration_number))
            ->getContent();

        $this->assertSame(1, substr_count($html, '>' . $alex->registration_number . '<'));

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/documents?status=selesai')
            ->assertOk()
            ->assertSee($budi->registration_number)
            ->assertDontSee($alex->registration_number);
    }

    public function test_detail_page_lets_admin_accept_reject_and_accept_all(): void
    {
        $admin = User::factory()->create();
        $applicant = $this->registerApplicant(['email' => 'alex@example.com']);
        $kk = $this->upload($applicant, 'KK');
        $identity = $this->upload($applicant, 'IDENTITAS');

        $this->actingAs($admin, 'web')
            ->get(route('admin.documents.show', $applicant->registration_number))
            ->assertOk()
            ->assertSee('Terima Semua (2)')
            ->assertSee(route('admin.documents.accept', $kk), false)
            ->assertSee(route('admin.documents.reject', $identity), false);

        $this->actingAs($admin, 'web')
            ->from(route('admin.documents.show', $applicant->registration_number))
            ->patch(route('admin.documents.reject', $identity), ['admin_note' => 'Buram'])
            ->assertRedirect(route('admin.documents.show', $applicant->registration_number));

        $this->assertSame('ditolak', $identity->fresh()->status);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.documents.accept-all', $applicant->registration_number))
            ->assertSessionHas('success', '1 berkas berhasil diterima.');

        $this->assertSame('diterima', $kk->fresh()->status);
        $this->assertSame('ditolak', $identity->fresh()->status);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.documents.accept-all', $applicant->registration_number))
            ->assertSessionHasErrors();
    }
}
