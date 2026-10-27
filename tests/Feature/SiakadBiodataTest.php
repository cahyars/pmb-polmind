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
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SiakadBiodataTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_register_saves_nik_school_program_and_class(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Reguler A')
            ->assertSee('Senin-Jumat, pagi-sore')
            ->assertSee('Perkuliahan tatap muka penuh di kampus');

        $this->post('/register', $this->registrationData([
            'full_name' => 'Ammar Musyaffa Firdaus',
            'nik' => '2171 0116 0307 0001',
            'school_name' => 'smk negeri 1 batam',
        ]))->assertRedirect('/camaba/dashboard');

        $applicant = Applicant::firstOrFail();

        $this->assertSame('AMMAR MUSYAFFA FIRDAUS', $applicant->full_name);
        $this->assertSame('2171011603070001', $applicant->nik);
        $this->assertSame('A120270001', $applicant->registration_number);
        $this->assertSame('TRPL', $applicant->studyProgram->code);
        $this->assertSame('REG_A', $applicant->classType->code);
        $this->assertSame('GEL1', $applicant->admissionWave->code);
        $this->assertSame('SMK NEGERI 1 BATAM', $applicant->education->school_name);
        $this->assertSame('11001234', $applicant->education->school_npsn);
        $this->assertSame('316101', $applicant->education->school_region_id);
        $this->assertSame('300000.00', (string) $applicant->registrationInvoice->total_amount);
        $this->assertSame('INV/PMB/2027/00001', $applicant->registrationInvoice->invoice_number);
    }

    public function test_admin_sets_class_scheme_shown_on_registration(): void
    {
        $classB = ClassType::where('code', 'REG_B')->firstOrFail();

        $this->actingAs(User::factory()->create(), 'web')
            ->put(route('admin.master-data.class-types.update', $classB), [
                'code' => 'REG_B',
                'registration_code' => 'B',
                'name' => 'Reguler B',
                'description' => 'Untuk karyawan.',
                'schedule' => 'Sabtu-Minggu, 08.00-17.00',
                'scheme' => "- Kuliah akhir pekan\n\n• Bisa sambil bekerja",
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Kuliah akhir pekan', 'Bisa sambil bekerja'], $classB->refresh()->scheme_points);

        $this->get('/register')
            ->assertOk()
            ->assertSee('Sabtu-Minggu, 08.00-17.00')
            ->assertSee('Kuliah akhir pekan')
            ->assertSee('Bisa sambil bekerja');
    }

    public function test_registration_path_is_chosen_at_register_and_preselected_from_landing(): void
    {
        // Kartu jalur di landing page mengarah ke form registrasi dengan jalur terpilih.
        $this->get('/')
            ->assertOk()
            ->assertSee(route('register', ['jalur' => 'prestasi']), false)
            ->assertSee(route('register', ['jalur' => 'undangan']), false);

        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="prestasi"[^>]*\bchecked\b[^>]*>/',
            $this->get('/register?jalur=prestasi')->assertOk()->getContent()
        );

        // Jalur tidak dikenal diabaikan: tidak ada yang terpilih.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*name="registration_path"[^>]*\bchecked\b[^>]*>/',
            $this->get('/register?jalur=sembarang')->assertOk()->getContent()
        );

        $applicant = $this->registerApplicant(['registration_path' => 'prestasi']);

        $this->assertSame('prestasi', $applicant->registration_path);
    }

    public function test_pages_use_line_icons_instead_of_colored_emoji(): void
    {
        // Emoji bergambar (mis. 💳 🎓 💻) diganti ikon garis <x-icon> seperti panel admin.
        $emoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2B50}\x{2705}\x{23F0}-\x{23FA}\x{2197}]/u';

        foreach (['/', '/register', '/login'] as $url) {
            $this->assertDoesNotMatchRegularExpression($emoji, $this->get($url)->assertOk()->getContent(), $url);
        }

        $applicant = $this->registeredApplicant();

        foreach (['/camaba/dashboard', '/camaba/biodata', '/camaba/upload-berkas', '/camaba/pembayaran', '/camaba/status-seleksi', '/camaba/pengumuman', '/camaba/daftar-ulang'] as $url) {
            $content = $this->actingAs($applicant, 'applicant')->get($url)->assertOk()->getContent();

            $this->assertDoesNotMatchRegularExpression($emoji, $content, $url);
            $this->assertStringContainsString('stroke-linejoin="round"', $content, $url);
        }
    }

    public function test_whatsapp_button_and_forgot_password_note(): void
    {
        foreach (['/', '/register', '/login'] as $url) {
            $this->get($url)->assertOk()->assertSee('Chat admin PMB lewat WhatsApp')->assertSee('rel="apple-touch-icon"', false);
        }

        // Favicon juga ada di panel admin.
        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee(asset('favicon.ico') . '?v=', false);

        $this->get('/login')
            ->assertSee('Lupa kata sandi? Silakan')
            ->assertSee('chat admin')
            ->assertSee('https://wa.me/6282113296897?text=' . rawurlencode('Halo Admin PMB Polmind, saya lupa kata sandi akun PMB saya.'), false);

        $applicant = $this->registeredApplicant();

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/biodata?step=pribadi')
            ->assertOk()
            ->assertSee(rawurlencode('saya AMMAR (' . $applicant->registration_number . ')'), false)
            ->assertSee('bottom-32 sm:bottom-28')
            ->assertSee('rel="apple-touch-icon"', false);
    }

    public function test_register_requires_nik_program_class_and_school(): void
    {
        $this->post('/register', $this->registrationData([
            'nik' => '12345',
            'study_program_id' => '',
            'class_type_id' => '',
            'registration_path' => '',
            'school_region_id' => '',
            'school_npsn' => '',
            'school_name' => '',
            'school_type' => '',
        ]))->assertSessionHasErrors(['nik', 'study_program_id', 'class_type_id', 'registration_path', 'school_region_id', 'school_npsn', 'school_name', 'school_type']);

        $this->assertSame(0, Applicant::count());
    }

    public function test_nik_registers_once_per_wave_but_again_in_another_wave(): void
    {
        $nik = '2171011603070001';

        $first = $this->registerApplicant(['nik' => $nik, 'email' => 'gel1@example.com']);

        // Gelombang sama: ditolak.
        $this->post('/register', $this->registrationData(['nik' => $nik, 'email' => 'lagi@example.com']))
            ->assertSessionHasErrors(['nik' => 'NIK ini sudah terdaftar di Gelombang 1. Satu NIK hanya bisa mendaftar satu kali per gelombang. Silakan login dengan akun yang sudah dibuat.']);

        $this->assertSame(1, Applicant::where('nik', $nik)->count());

        // Gelombang berbeda: boleh mendaftar lagi.
        AdmissionWave::where('code', 'GEL1')->update(['is_active' => false, 'status' => 'closed']);
        AdmissionWave::where('code', 'GEL2')->update(['is_active' => true, 'status' => 'open']);

        $second = $this->registerApplicant(['nik' => $nik, 'email' => 'gel2@example.com']);

        $this->assertSame('GEL2', $second->admissionWave->code);
        $this->assertSame('A220270002', $second->registration_number);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_biodata_rejects_nik_used_by_another_applicant_in_same_wave(): void
    {
        $this->registerApplicant(['nik' => '2171011603070001', 'email' => 'lain@example.com']);
        $applicant = $this->registeredApplicant();

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', ['step' => 'pribadi'] + $this->biodata())
            ->assertSessionHasErrors('nik');

        $this->assertNotSame('2171011603070001', $applicant->refresh()->nik);
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
            $applicant = $this->registerApplicant(['email' => "camaba{$index}@example.com", 'class_type_id' => $classId]);

            $numbers[] = $applicant->registration_number;
        }

        $this->assertSame(['A120270001', 'A120270002', 'B120270001', 'A120270003'], $numbers);

        // Kelas terkunci sejak registrasi; prodi dan jalur masih bisa diperbarui di biodata.
        $applicant->update(['payment_status' => 'valid']);

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', ['step' => 'prodi'] + array_merge($this->biodata(), ['class_type_id' => $classB]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('camaba.biodata.edit', ['step' => 'alamat']));

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

        foreach (['/admin/dashboard', '/admin/applicants', '/admin/applicants/' . $applicant->registration_number, '/admin/integrations', '/admin/reports'] as $url) {
            $this->actingAs($admin, 'web')->get($url)->assertOk();
        }

        // Master Data dipecah menjadi submenu; halaman induk mengarah ke submenu pertama.
        $this->actingAs($admin, 'web')->get('/admin/master-data')->assertRedirect(route('admin.master-data.show', 'tahun-pmb'));

        foreach (array_keys(\App\Http\Controllers\Admin\MasterDataController::SECTIONS) as $section) {
            $this->actingAs($admin, 'web')->get(route('admin.master-data.show', $section))->assertOk();
        }

        $this->actingAs($admin, 'web')
            ->get('/admin/applicants/' . $applicant->registration_number)
            ->assertSee('Kec. Tanjung Pinang Barat')
            ->assertSee('SMA/SMK');
    }

    public function test_upload_page_is_single_list_with_rules_and_toast(): void
    {
        Storage::fake('local');

        DocumentType::where('code', 'KK')->update(['description' => 'Scan Kartu Keluarga terbaru.']);
        $identity = DocumentType::where('code', 'IDENTITAS')->firstOrFail();
        $applicant = $this->registeredApplicant();

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/upload-berkas')
            ->assertOk()
            ->assertSeeInOrder(['Berkas Pendukung', 'Pas Foto', 'Kartu Keluarga', 'Ketentuan Upload'])
            ->assertSee('Scan Kartu Keluarga terbaru.')
            ->assertSee('Unggah');

        // Format salah ditolak dengan pesan yang jelas.
        $this->actingAs($applicant, 'applicant')
            ->from('/camaba/upload-berkas')
            ->post('/camaba/upload-berkas', ['document_type_id' => $identity->id, 'file' => UploadedFile::fake()->create('ktp.exe', 10)])
            ->assertSessionHasErrors(['file' => 'Format file ' . $identity->name . ' harus ' . strtoupper(implode(', ', $identity->allowed_extensions)) . '.']);

        $this->actingAs($applicant, 'applicant')
            ->from('/camaba/upload-berkas')
            ->post('/camaba/upload-berkas', ['document_type_id' => $identity->id, 'file' => UploadedFile::fake()->image('ktp.jpg')])
            ->assertRedirect('/camaba/upload-berkas')
            ->assertSessionHas('success', 'Unggah berkas ' . $identity->name . ' berhasil disimpan.');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/upload-berkas')
            ->assertSee('Unggah berkas ' . $identity->name . ' berhasil disimpan.')
            ->assertSee('Menunggu')
            ->assertSee('Ubah');

        // Berkas yang sudah diterima terkunci.
        ApplicantDocument::where('document_type_id', $identity->id)->update(['status' => 'diterima']);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/upload-berkas')
            ->assertSee('Berkas sudah diterima admin dan tidak dapat diubah');
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

        // Bisa diakses tamu karena sekolah dipilih di form registrasi.
        $this->getJson('/referensi/sekolah?regency=316000')
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
        $applicant = $this->registerApplicant(['email' => $email]);

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
