<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\IntegrationLog;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\NimGenerator;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SiakadApiTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private const TOKEN = 'token-rahasia-siakad';

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);

        config(['services.siakad.token' => self::TOKEN, 'services.siakad.allowed_ips' => null]);
    }

    private function api(): static
    {
        return $this->withToken(self::TOKEN);
    }

    private function student(string $email, ?string $nim = null): Applicant
    {
        $applicant = $this->registerApplicant(['email' => $email]);

        $applicant->update([
            'payment_status' => 'valid',
            'selection_status' => 'diterima',
            're_registration_status' => 'daftar_ulang_valid',
        ]);

        if ($nim) {
            NimGenerator::assign($applicant, $nim);
        }

        return $applicant->refresh();
    }

    public function test_api_requires_configured_token_and_allowed_ip(): void
    {
        $this->getJson('/api/siakad/mahasiswa')->assertUnauthorized();
        $this->withToken('salah')->getJson('/api/siakad/mahasiswa')->assertUnauthorized();
        $this->api()->getJson('/api/siakad/mahasiswa')->assertOk();

        config(['services.siakad.allowed_ips' => '10.0.0.5, 10.0.0.6']);
        $this->api()->getJson('/api/siakad/mahasiswa')->assertForbidden();

        config(['services.siakad.token' => '']);
        $this->api()->getJson('/api/siakad/mahasiswa')->assertStatus(503);
    }

    public function test_siakad_pulls_students_with_nim_in_body_request_format(): void
    {
        $ready = $this->student('siap@example.com', 'A20270001');
        $this->student('tanpa-nim@example.com');

        $response = $this->api()->get('/api/siakad/mahasiswa')->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id_pendaftar', $ready->registration_number)
            ->assertJsonPath('data.0.nim', 'A20270001')
            ->assertJsonPath('data.0.status_sinkron', 'siap_sinkron')
            ->assertJsonPath('data.0.tahun_masuk', 2027)
            ->assertJsonPath('data.0.program_studi.kode', 'TRPL')
            ->assertJsonPath('data.0.record.nama_mahasiswa', $ready->full_name)
            ->assertJsonPath('data.0.record.nik', $ready->nik);

        $sample = json_decode(file_get_contents(base_path('body_request_mahasiswa.json')), true);
        $this->assertSame(array_keys($sample['record']), array_keys($response->json('data.0.record')));

        // Biodata belum lengkap ikut ditandai, supaya SIAKAD bisa melewatinya.
        $this->assertFalse($response->json('data.0.data_lengkap'));
        $this->assertNotEmpty($response->json('data.0.data_kurang'));

        $this->api()->getJson('/api/siakad/mahasiswa?prodi=TRM')->assertJsonCount(0, 'data');
        $this->api()->getJson('/api/siakad/mahasiswa?tahun=2026')->assertJsonCount(0, 'data');
        $this->api()->getJson('/api/siakad/mahasiswa?status=asal')->assertUnprocessable();

        $this->api()->getJson('/api/siakad/mahasiswa/' . $ready->registration_number)
            ->assertOk()
            ->assertJsonPath('data.nim', 'A20270001');
    }

    public function test_applicant_without_nim_is_not_exposed(): void
    {
        $waiting = $this->student('tunggu@example.com');

        $this->api()->getJson('/api/siakad/mahasiswa/' . $waiting->registration_number)->assertNotFound();
        $this->api()->postJson('/api/siakad/mahasiswa/' . $waiting->registration_number . '/konfirmasi', ['status' => 'gagal', 'pesan' => 'x'])->assertNotFound();
        $this->api()->getJson('/api/siakad/mahasiswa/TIDAKADA')->assertNotFound();
    }

    public function test_successful_confirmation_marks_synced_and_locks_nim(): void
    {
        $student = $this->student('sukses@example.com', 'A20270001');
        $url = '/api/siakad/mahasiswa/' . $student->registration_number . '/konfirmasi';

        $this->api()->postJson($url, ['status' => 'berhasil'])->assertUnprocessable()->assertJsonValidationErrors('nim');
        $this->api()->postJson($url, ['status' => 'berhasil', 'nim' => 'A20279999'])->assertStatus(409);

        $this->api()->postJson($url, ['status' => 'berhasil', 'nim' => 'a20270001', 'id_mahasiswa' => 'uuid-siakad-1'])
            ->assertOk()
            ->assertJsonPath('data.status_sinkron', 'sudah_sinkron');

        $student->refresh();
        $this->assertSame('sudah_sinkron', $student->sync_status);
        $this->assertNotNull($student->synced_at);

        $log = IntegrationLog::where('applicant_id', $student->id)->sole();
        $this->assertSame('success', $log->status);
        $this->assertSame('uuid-siakad-1', $log->response_payload['id_mahasiswa']);
        $this->assertSame($student->nik, $log->request_payload['record']['nik']);

        // Konfirmasi ulang aman, tidak menambah log.
        $this->api()->postJson($url, ['status' => 'berhasil', 'nim' => 'A20270001'])->assertOk();
        $this->assertSame(1, IntegrationLog::where('applicant_id', $student->id)->count());
        $this->api()->postJson($url, ['status' => 'gagal', 'pesan' => 'x'])->assertStatus(409);

        // Hilang dari daftar default, tetap bisa dilihat dengan filter status.
        $this->api()->getJson('/api/siakad/mahasiswa')->assertJsonCount(0, 'data');
        $this->api()->getJson('/api/siakad/mahasiswa?status=sudah_sinkron')->assertJsonCount(1, 'data');

        $this->actingAs(User::factory()->create(), 'web')
            ->delete(route('admin.nim.destroy', $student))
            ->assertSessionHasErrors();
        $this->assertSame('A20270001', $student->refresh()->nim);
    }

    public function test_failed_confirmation_returns_student_to_admin(): void
    {
        $student = $this->student('gagal@example.com', 'A20270001');
        $url = '/api/siakad/mahasiswa/' . $student->registration_number . '/konfirmasi';

        // Tanpa header Accept JSON pun error tetap dikembalikan sebagai JSON.
        $this->api()->post($url, ['status' => 'gagal'])->assertUnprocessable()->assertJsonValidationErrors('pesan');
        $this->api()->post($url, ['status' => 'gagal', 'pesan' => 'id_wilayah tidak dikenal'])->assertOk();

        $this->assertSame('gagal_sinkron', $student->refresh()->sync_status);
        $this->assertSame('id_wilayah tidak dikenal', IntegrationLog::where('applicant_id', $student->id)->sole()->error_message);
        $this->api()->getJson('/api/siakad/mahasiswa')->assertJsonCount(0, 'data');

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/integrations')
            ->assertOk()
            ->assertSee('API untuk SIAKAD')
            ->assertSee(url('/api/siakad/mahasiswa'))
            ->assertSee('id_wilayah tidak dikenal');
    }

    public function test_admin_can_mark_synced_manually_with_nim_from_pmb(): void
    {
        $student = $this->student('manual@example.com', 'A20270001');
        $withoutNim = $this->student('kosong@example.com');
        $withoutNim->update(['sync_status' => 'siap_sinkron']);

        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->patch(route('admin.integrations.synced', $withoutNim))
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'web')
            ->patch(route('admin.integrations.synced', $student))
            ->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('sudah_sinkron', $student->sync_status);
        $this->assertSame('A20270001', $student->nim);
    }
}
