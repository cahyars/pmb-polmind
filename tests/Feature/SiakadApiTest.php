<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\IntegrationLog;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\NimGenerator;
use App\Services\SiakadApiAccess;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SiakadApiTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);

        $this->token = SiakadApiAccess::generate('Admin PMB');
    }

    private function api(): static
    {
        return $this->withToken($this->token);
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

        SiakadApiAccess::setAllowedIps(['10.0.0.5', '10.0.0.6']);
        $this->api()->getJson('/api/siakad/mahasiswa')->assertForbidden();

        SiakadApiAccess::setAllowedIps(['127.0.0.1']);
        $this->api()->getJson('/api/siakad/mahasiswa')->assertOk();

        SiakadApiAccess::revoke();
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

    public function test_admin_generates_token_from_panel_and_sees_it_once(): void
    {
        SiakadApiAccess::revoke();

        $operator = User::factory()->operator()->create();
        $this->actingAs($operator, 'web')->get(route('admin.integrations.api'))->assertForbidden();
        $this->actingAs($operator, 'web')->post(route('admin.integrations.api.generate'))->assertForbidden();
        $this->actingAs($operator, 'web')->get('/admin/integrations')->assertOk()->assertDontSee(route('admin.integrations.api'));

        $admin = User::factory()->create(['name' => 'Bu Admin']);

        $this->actingAs($admin, 'web')
            ->get('/admin/integrations')
            ->assertSee('Token API')
            ->assertSee(route('admin.integrations.api'));

        $this->actingAs($admin, 'web')->get(route('admin.integrations.api'))->assertOk()->assertSee('Nonaktif');

        $response = $this->actingAs($admin, 'web')
            ->followingRedirects()
            ->post(route('admin.integrations.api.generate'))
            ->assertOk()
            ->assertSee('Salin token sekarang')
            ->assertSee('PMB_API_URL=' . url('/api/siakad'));

        preg_match('/PMB_API_TOKEN=([a-f0-9]{64})/', $response->getContent(), $match);
        $first = $match[1];

        // Token tidak tersimpan utuh dan tidak tampil lagi setelah halaman dimuat ulang.
        $this->assertSame(0, \App\Models\Setting::where('value', $first)->count());
        $this->actingAs($admin, 'web')
            ->get(route('admin.integrations.api'))
            ->assertDontSee($first)
            ->assertSee('…' . substr($first, -4))
            ->assertSee('Bu Admin');

        $this->withToken($first)->getJson('/api/siakad/mahasiswa')->assertOk();

        // Token baru membatalkan token lama.
        $this->actingAs($admin, 'web')->post(route('admin.integrations.api.generate'));
        $this->withToken($first)->getJson('/api/siakad/mahasiswa')->assertUnauthorized();

        // Pembatasan IP divalidasi.
        $this->actingAs($admin, 'web')
            ->put(route('admin.integrations.api.ips'), ['allowed_ips' => '103.10.20.30, bukan-ip'])
            ->assertSessionHasErrors();
        $this->actingAs($admin, 'web')
            ->put(route('admin.integrations.api.ips'), ['allowed_ips' => "103.10.20.30\n103.10.20.31"])
            ->assertSessionHasNoErrors();
        $this->assertSame(['103.10.20.30', '103.10.20.31'], SiakadApiAccess::allowedIps());

        $this->actingAs($admin, 'web')->delete(route('admin.integrations.api.revoke'))->assertSessionHasNoErrors();
        $this->assertFalse(SiakadApiAccess::enabled());
    }
}
