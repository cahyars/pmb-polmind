<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\DocumentType;
use App\Models\RefOption;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SecurityAndReliabilityTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);
        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_responses_contain_standard_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_document_upload_rejects_unsafe_extensions(): void
    {
        Storage::fake('local');

        $applicant = $this->registerApplicant();
        $applicant->update(['payment_status' => 'valid']);

        $docType = DocumentType::where('code', 'IDENTITAS')->firstOrFail();

        $maliciousFile = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        $response = $this->actingAs($applicant, 'applicant')
            ->post('/camaba/upload-berkas', [
                'document_type_id' => $docType->id,
                'file' => $maliciousFile,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_ref_options_are_cached(): void
    {
        $optionsFirst = RefOption::optionsFor(RefOption::AGAMA);
        $optionsSecond = RefOption::optionsFor(RefOption::AGAMA);

        $this->assertNotEmpty($optionsFirst);
        $this->assertEquals($optionsFirst->toArray(), $optionsSecond->toArray());
    }
}
