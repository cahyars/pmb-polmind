<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\RefOption;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\SiakadPayload;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiakadBiodataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_register_only_creates_account(): void
    {
        $this->post('/register', [
            'full_name' => 'Ammar Musyaffa Firdaus',
            'email' => 'ammar@example.com',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/camaba/dashboard');

        $applicant = Applicant::firstOrFail();

        $this->assertSame('AMMAR MUSYAFFA FIRDAUS', $applicant->full_name);
        $this->assertSame('TMP20270001', $applicant->registration_number);
        $this->assertNull($applicant->study_program_id);
        $this->assertSame('GEL1', $applicant->admissionWave->code);
        $this->assertSame('300000.00', (string) $applicant->registrationInvoice->total_amount);
        $this->assertSame('INV/PMB/2027/00001', $applicant->registrationInvoice->invoice_number);
    }

    public function test_unpaid_applicant_cannot_open_biodata_or_documents(): void
    {
        $applicant = $this->registeredApplicant(paid: false);

        foreach (['/camaba/biodata', '/camaba/upload-berkas', '/camaba/status-seleksi', '/camaba/daftar-ulang'] as $url) {
            $this->actingAs($applicant, 'applicant')
                ->get($url)
                ->assertRedirect(route('camaba.dashboard'))
                ->assertSessionHas('payment_required', true);
        }

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/dashboard')
            ->assertOk()
            ->assertSee('Segera bayar biaya formulir')
            ->assertSee('Rp300.000');

        $this->actingAs($applicant, 'applicant')->get('/camaba/pembayaran')->assertOk();
    }

    public function test_final_registration_number_follows_class_and_wave(): void
    {
        $classA = ClassType::where('code', 'REG_A')->value('id');
        $classB = ClassType::where('code', 'REG_B')->value('id');

        $numbers = [];

        foreach ([$classA, $classA, $classB, $classA] as $index => $classId) {
            $applicant = $this->registeredApplicant(email: "camaba{$index}@example.com");

            $this->actingAs($applicant, 'applicant')
                ->put('/camaba/biodata', ['step' => 'prodi'] + array_merge($this->biodata(), ['class_type_id' => $classId]))
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('camaba.biodata.edit', ['step' => 'alamat']));

            $numbers[] = $applicant->refresh()->registration_number;
        }

        $this->assertSame(['A120270001', 'A120270002', 'B120270001', 'A120270003'], $numbers);

        // Kelas terkunci setelah nomor final dibuat.
        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', ['step' => 'prodi'] + array_merge($this->biodata(), ['class_type_id' => $classB]));

        $this->assertSame('A120270003', $applicant->refresh()->registration_number);
        $this->assertSame($classA, $applicant->class_type_id);
    }

    public function test_biodata_produces_siakad_payload_matching_api_body(): void
    {
        $applicant = $this->registeredApplicant();

        $this->fillBiodata($applicant);

        $applicant->refresh();

        $this->assertSame('biodata_lengkap', $applicant->registration_status);
        $this->assertSame('A120270001', $applicant->registration_number);

        $this->assertSame([], SiakadPayload::missingFields($applicant));
        $this->assertSame('Prov. Kepulauan Riau', $applicant->address->province_name);

        $payload = SiakadPayload::build($applicant);

        $sample = json_decode(file_get_contents(base_path('body_request_mahasiswa.json')), true);

        $this->assertSame(array_keys($sample['record']), array_keys($payload['record']));

        $this->assertSame([
            'nama_mahasiswa' => 'AMMAR MUSYAFFA FIRDAUS',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Batam',
            'tanggal_lahir' => '2007-03-16',
            'id_agama' => 1,
            'nik' => '2171011603070001',
            'nisn' => '0071234567',
            'npwp' => '123456789012345',
            'kewarganegaraan' => 'ID',
            'jalan' => 'Jl. Merdeka No. 10',
            'dusun' => 'Dusun 1',
            'rt' => 1,
            'rw' => 2,
            'kelurahan' => 'Teluk Tering',
            'kode_pos' => 29461,
            'id_wilayah' => '316101',
            'id_jenis_tinggal' => 1,
            'id_alat_transportasi' => 1,
            'telepon' => '0778123456',
            'handphone' => '081234567890',
            'email' => 'ammar@example.com',
            'penerima_kps' => 0,
            'nomor_kps' => '',
            'nik_ayah' => '2171011603700001',
            'nama_ayah' => 'Ayah Ammar',
            'tanggal_lahir_ayah' => '1970-01-01',
            'id_pendidikan_ayah' => 3,
            'id_pekerjaan_ayah' => 2,
            'id_penghasilan_ayah' => 11,
            'nik_ibu' => '2171011603750002',
            'nama_ibu_kandung' => 'SUMARNI',
            'tanggal_lahir_ibu' => '1975-01-01',
            'id_pendidikan_ibu' => 3,
            'id_pekerjaan_ibu' => 2,
            'id_penghasilan_ibu' => 11,
            'nama_wali' => '',
            'tanggal_lahir_wali' => null,
            'id_pendidikan_wali' => null,
            'id_pekerjaan_wali' => null,
            'id_penghasilan_wali' => null,
            'id_kebutuhan_khusus_mahasiswa' => 0,
            'id_kebutuhan_khusus_ayah' => 0,
            'id_kebutuhan_khusus_ibu' => 0,
        ], $payload['record']);
    }

    public function test_biodata_rejects_codes_outside_siakad_references(): void
    {
        $applicant = $this->registeredApplicant();

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', array_merge($this->biodata(), [
                'step' => 'pribadi',
                'religion_id' => 50,
                'nik' => '123',
            ]))
            ->assertSessionHasErrors(['religion_id', 'nik']);

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', array_merge($this->biodata(), ['step' => 'alamat', 'region_id' => '316100']))
            ->assertSessionHasErrors(['region_id']);

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', array_merge($this->biodata(), ['step' => 'orangtua', 'father_job_id' => 1000, 'mother_name' => '']))
            ->assertSessionHasErrors(['father_job_id', 'mother_name']);

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', array_merge($this->biodata(), ['step' => 'sekolah', 'school_npsn' => '']))
            ->assertSessionHasErrors(['school_npsn']);
    }

    public function test_pages_render_with_complete_biodata(): void
    {
        $applicant = $this->registeredApplicant();

        $this->fillBiodata($applicant);

        foreach (['/camaba/dashboard', '/camaba/status-seleksi', '/camaba/pengumuman', '/camaba/daftar-ulang', '/camaba/upload-berkas', '/camaba/pembayaran'] as $url) {
            $this->actingAs($applicant, 'applicant')->get($url)->assertOk();
        }

        foreach (['pribadi', 'prodi', 'alamat', 'sekolah', 'orangtua', 'ringkasan'] as $step) {
            $this->actingAs($applicant, 'applicant')
                ->get('/camaba/biodata?step=' . $step)
                ->assertOk();
        }

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/biodata?step=ringkasan')
            ->assertSee('Biodata Anda sudah lengkap');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/dashboard')
            ->assertSee('✓ Lengkap')
            ->assertSee('Lanjutkan: Upload Berkas');

        $admin = User::factory()->create();

        foreach (['/admin/dashboard', '/admin/applicants', '/admin/applicants/' . $applicant->registration_number, '/admin/integrations', '/admin/reports', '/admin/master-data'] as $url) {
            $this->actingAs($admin, 'web')->get($url)->assertOk();
        }

        $this->actingAs($admin, 'web')
            ->get('/admin/applicants/' . $applicant->registration_number)
            ->assertSee('Kec. Tanjung Pinang Barat')
            ->assertSee('SMA/SMK');
    }

    public function test_uploaded_documents_are_private(): void
    {
        Storage::fake('local');

        $owner = $this->registeredApplicant();

        $this->actingAs($owner, 'applicant')->post('/camaba/upload-berkas', [
            'document_type_id' => DocumentType::where('code', 'IDENTITAS')->value('id'),
            'file' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertSessionHasNoErrors();

        $document = ApplicantDocument::firstOrFail();

        Storage::disk('local')->assertExists($document->file_path);
        $this->assertFalse(Storage::disk('public')->exists($document->file_path));

        $this->actingAs($owner, 'applicant')
            ->get(route('camaba.documents.file', $document))
            ->assertOk();

        $other = Applicant::create([
            'pmb_year_id' => $owner->pmb_year_id,
            'registration_number' => 'PMB20279999',
            'full_name' => 'Camaba Lain',
            'email' => 'lain@example.com',
            'password' => 'rahasia123',
        ]);

        $this->actingAs($other, 'applicant')
            ->get(route('camaba.documents.file', $document))
            ->assertNotFound();

        auth('applicant')->logout();

        $this->get(route('admin.documents.file', $document))
            ->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create(), 'web')
            ->get(route('admin.documents.file', $document))
            ->assertOk();
    }

    public function test_school_search_uses_regency_and_falls_back_when_api_down(): void
    {
        $apiDown = false;

        Http::fake(function ($request) use (&$apiDown) {
            if ($apiDown) {
                return Http::response('error', 500);
            }

            $school = str_contains($request->url(), '/sekolah/sma')
                ? ['npsn' => '11000001', 'sekolah' => 'SMAN 1 BATAM', 'bentuk' => 'SMA', 'status' => 'N', 'kode_kec' => '316012  ', 'alamat_jalan' => 'Jl. Contoh']
                : ['npsn' => '11000382', 'sekolah' => 'SMKN 1 BATAM', 'bentuk' => 'SMK', 'status' => 'N', 'kode_kec' => '316014  ', 'alamat_jalan' => null];

            return Http::response(['status' => 'success', 'dataSekolah' => [$school]]);
        });

        $applicant = $this->registeredApplicant();

        $this->actingAs($applicant, 'applicant')
            ->getJson('/referensi/sekolah?regency=316000')
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonCount(2, 'schools')
            ->assertJsonFragment(['npsn' => '11000382', 'type' => 'SMK', 'status' => 'Negeri', 'district_id' => '316014']);

        Cache::flush();
        $apiDown = true;

        $this->actingAs($applicant, 'applicant')
            ->getJson('/referensi/sekolah?regency=316000')
            ->assertOk()
            ->assertJsonPath('available', false);

        // Isian manual tetap diterima (misalnya MA yang tidak ada di daftar).
        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', array_merge($this->biodata(), [
                'step' => 'sekolah',
                'is_manual_school' => 1,
                'school_npsn' => '',
                'school_name' => 'man 1 batam',
                'school_type' => 'MA',
                'school_status' => 'Negeri',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('MAN 1 BATAM', $applicant->education->school_name);
        $this->assertTrue($applicant->education->is_manual_school);
    }

    public function test_region_endpoint_returns_children(): void
    {
        $this->getJson('/referensi/wilayah')
            ->assertOk()
            ->assertJsonFragment(['id' => '310000']);

        $this->getJson('/referensi/wilayah?parent=316100')
            ->assertOk()
            ->assertJsonFragment(['id' => '316101']);
    }

    private function registeredApplicant(bool $paid = true, string $email = 'ammar@example.com'): Applicant
    {
        $this->post('/register', [
            'full_name' => 'Ammar',
            'email' => $email,
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        auth('applicant')->logout();

        $applicant = Applicant::where('email', $email)->firstOrFail();

        if ($paid) {
            $applicant->update(['payment_status' => 'valid']);
        }

        return $applicant;
    }

    private function fillBiodata(Applicant $applicant): void
    {
        foreach (['pribadi', 'prodi', 'alamat', 'sekolah', 'orangtua'] as $step) {
            $this->actingAs($applicant, 'applicant')
                ->put('/camaba/biodata', ['step' => $step] + $this->biodata())
                ->assertSessionHasNoErrors();
        }
    }

    private function biodata(): array
    {
        return [
            'full_name' => 'AMMAR MUSYAFFA FIRDAUS',
            'email' => 'ammar@example.com',
            'phone' => '081234567890',
            'home_phone' => '0778123456',
            'nik' => '2171011603070001',
            'nisn' => '0071234567',
            'npwp' => '123456789012345',
            'gender' => 'L',
            'birth_place' => 'Batam',
            'birth_date' => '2007-03-16',
            'religion_id' => 1,
            'citizenship' => 'ID',
            'transportation_id' => 1,
            'special_need_id' => 0,
            'kps_recipient' => 0,
            'kps_number' => '',

            'study_program_id' => StudyProgram::where('code', 'TRPL')->value('id'),
            'second_study_program_id' => StudyProgram::where('code', 'BD')->value('id'),
            'class_type_id' => ClassType::where('code', 'REG_A')->value('id'),
            'registration_path' => 'umum',

            'address' => 'Jl. Merdeka No. 10',
            'hamlet' => 'Dusun 1',
            'rt' => '1',
            'rw' => '2',
            'village_name' => 'Teluk Tering',
            'region_id' => '316101',
            'postal_code' => '29461',
            'residence_type_id' => 1,

            'school_region_id' => '316101',
            'is_manual_school' => 0,
            'school_npsn' => '11001234',
            'school_name' => 'SMK Negeri 1 Batam',
            'school_type' => 'SMK',
            'school_status' => 'Negeri',
            'major' => 'Rekayasa Perangkat Lunak',
            'graduation_year' => 2026,

            'father_name' => 'Ayah Ammar',
            'father_nik' => '2171011603700001',
            'father_birth_date' => '1970-01-01',
            'father_education_id' => 3,
            'father_job_id' => 2,
            'father_income_id' => 11,
            'father_special_need_id' => 0,

            'mother_name' => 'SUMARNI',
            'mother_nik' => '2171011603750002',
            'mother_birth_date' => '1975-01-01',
            'mother_education_id' => 3,
            'mother_job_id' => 2,
            'mother_income_id' => 11,
            'mother_special_need_id' => 0,

            'guardian_name' => '',
        ];
    }
}
