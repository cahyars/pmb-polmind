<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class WalkInApplicantTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();
        Storage::fake('local');

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    /**
     * Isian minimal dari formulir fisik: data pendaftaran, biodata inti, dan sekolah.
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'admission_wave_id' => AdmissionWave::where('code', 'GEL1')->value('id'),
            'registration_path' => 'umum',
            'class_type_id' => ClassType::where('code', 'REG_B')->value('id'),
            'study_program_id' => StudyProgram::where('code', 'TRPL')->value('id'),
            'second_study_program_id' => StudyProgram::where('code', 'BD')->value('id'),
            'almamater_size' => 'XL',
            'source_information' => 'Brosur/Poster',

            'full_name' => 'siti rahma',
            'nik' => '3216015505070002',
            'gender' => 'P',
            'birth_place' => 'Bekasi',
            'birth_date' => '2007-05-15',
            'religion_id' => 1,
            'citizenship' => 'ID',
            'email' => 'Siti.Rahma@example.com',
            'phone' => '081298765432',
            'special_need_id' => 0,
            'kps_recipient' => 0,

            'school_region_id' => '316101',
            'is_manual_school' => 0,
            'school_npsn' => '11001234',
            'school_name' => 'SMK Negeri 1 Batam',
            'school_type' => 'SMK',
            'school_status' => 'Negeri',

            'father_special_need_id' => 0,
            'mother_special_need_id' => 0,

            'registration_paid' => 0,
        ], $overrides);
    }

    public function test_admin_and_operator_open_add_applicant_form_from_data_camaba(): void
    {
        foreach ([User::factory()->create(), User::factory()->operator()->create()] as $user) {
            $this->actingAs($user, 'web')
                ->get('/admin/applicants')
                ->assertOk()
                ->assertSee(route('admin.applicants.create'))
                ->assertSee(route('admin.reports.export-applicants'));

            $this->actingAs($user, 'web')
                ->get(route('admin.applicants.create'))
                ->assertOk()
                ->assertSee('Ukuran Jas Almamater')
                ->assertSee('Pembayaran Formulir')
                ->assertSee('Gelombang 1');
        }
    }

    public function test_walk_in_applicant_gets_account_with_birth_date_password(): void
    {
        $operator = User::factory()->operator()->create(['name' => 'Pak Operator']);

        $response = $this->actingAs($operator, 'web')->post(route('admin.applicants.store'), $this->form());

        $applicant = Applicant::where('email', 'siti.rahma@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.applicants.show', $applicant->registration_number))
            ->assertSessionHas('walk_in_account', ['email' => 'siti.rahma@example.com', 'password' => '15052007']);

        $this->assertSame('SITI RAHMA', $applicant->full_name);
        $this->assertSame('B120270001', $applicant->registration_number);
        $this->assertSame('kampus', $applicant->registered_via);
        $this->assertSame('Pak Operator', $applicant->registered_by_name);
        $this->assertSame('XL', $applicant->almamater_size);
        $this->assertSame('belum_bayar', $applicant->payment_status);
        $this->assertSame('unpaid', $applicant->registrationInvoice->status);
        $this->assertSame('SMK NEGERI 1 BATAM', $applicant->education->school_name);
        $this->assertNull($applicant->address);
        $this->assertSame('registrasi_awal', $applicant->registration_status);

        $this->actingAs($operator, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertSee('Kirim informasi login ke camaba')
            ->assertSee('Kirim Info Login ke WhatsApp')
            ->assertSee(route('admin.applicants.login-info', $applicant->registration_number))
            ->assertSee('Datang ke kampus, diinput oleh Pak Operator');

        // Pesan persis format Informasi Pendaftar, dibuka di WhatsApp camaba, dan tercatat sebagai follow up.
        $redirect = $this->actingAs($operator, 'web')
            ->post(route('admin.applicants.login-info', $applicant->registration_number))
            ->assertRedirect()
            ->headers->get('Location');

        $this->assertStringStartsWith('https://wa.me/6281298765432?text=', $redirect);
        $this->assertSame(implode("\n", [
            'Informasi Pendaftar PMB Politeknik Mitra Industri TA 2027',
            '',
            'Halo, selamat datang di Politeknik Mitra Industri 👋',
            'Mohon segera melengkapi profil pendaftar pada portal berikut:',
            '🔗 ' . route('login'),
            '',
            '*Akses Masuk*',
            '> Nama Pendaftar : SITI RAHMA',
            '> ID Pendaftar : *B120270001*',
            '> PIN : *15052007*',
            '',
            '👉 Lengkapi data diri Anda agar proses seleksi dan administrasi berjalan lancar.',
            '',
            'Terima kasih atas kerja samanya 🙏',
            '*Politeknik Mitra Industri*',
        ]), rawurldecode(substr($redirect, strlen('https://wa.me/6281298765432?text='))));

        $followUp = $applicant->followUps()->sole();
        $this->assertSame('whatsapp', $followUp->contact_method);
        $this->assertSame('Pak Operator', $followUp->officer_name);

        auth('web')->logout();

        // Camaba masuk dengan ID Pendaftar + PIN dari pesan, atau dengan email.
        $this->post('/login', ['email' => 'b120270001', 'password' => '15052007'])
            ->assertRedirect('/camaba/dashboard');
        auth('applicant')->logout();

        $this->post('/login', ['email' => 'siti.rahma@example.com', 'password' => '15052007'])
            ->assertRedirect('/camaba/dashboard');
        auth('applicant')->logout();

        $this->post('/login', ['email' => 'B120270001', 'password' => 'salah'])
            ->assertSessionHasErrors();

        // Pendaftar online memakai password sendiri: tombol info login tidak muncul.
        $online = $this->registerApplicant(['email' => 'online@example.com']);
        $this->actingAs($operator, 'web')
            ->get(route('admin.applicants.show', $online->registration_number))
            ->assertDontSee('Kirim Info Login ke WhatsApp');
        $this->actingAs($operator, 'web')
            ->post(route('admin.applicants.login-info', $online->registration_number))
            ->assertSessionHasErrors();
    }

    public function test_complete_paper_form_paid_at_campus_is_ready_for_next_steps(): void
    {
        $cash = PaymentMethod::create(['type' => 'cash', 'provider' => 'Loket Keuangan', 'is_active' => true]);
        $admin = User::factory()->create(['name' => 'Bu Admin']);

        $this->actingAs($admin, 'web')->post(route('admin.applicants.store'), $this->form([
            'almamater_size' => 'Lainnya',
            'almamater_size_other' => 'xxxl',

            'address' => 'Jl. Melati No. 5',
            'rt' => '3',
            'rw' => '7',
            'village_name' => 'Teluk Tering',
            'region_id' => '316101',
            'postal_code' => '17530',

            'major' => 'Akuntansi',
            'graduation_year' => 2026,
            'school_address' => 'Jl. Pendidikan No. 1',

            'mother_name' => 'aminah',
            'father_name' => 'Budi',
            'father_job_id' => 2,

            'registration_paid' => 1,
            'payment_method_id' => $cash->id,
            'paid_at' => now()->toDateString(),
            'payment_proof' => UploadedFile::fake()->image('kwitansi.jpg'),
        ]))->assertSessionHasNoErrors();

        $applicant = Applicant::where('nik', '3216015505070002')->firstOrFail();

        $this->assertSame('XXXL', $applicant->almamater_size);
        $this->assertSame('biodata_lengkap', $applicant->registration_status);
        $this->assertSame('AMINAH', $applicant->parentData->mother_name);
        $this->assertSame('003', $applicant->address->rt);
        $this->assertSame('Jl. Pendidikan No. 1', $applicant->education->school_address);

        $this->assertSame('valid', $applicant->payment_status);
        $this->assertSame('paid', $applicant->registrationInvoice->status);

        $payment = Payment::where('applicant_id', $applicant->id)->sole();
        $this->assertSame('valid', $payment->status);
        $this->assertSame('Bu Admin', $payment->verified_by_name);
        $this->assertSame('Tunai', $payment->sender_bank);
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame('PAY/PMB/2027/00001', $payment->payment_number);
        Storage::disk('local')->assertExists($payment->proof_file_path);

        // Camaba langsung bisa membuka biodata karena formulir sudah dibayar.
        $this->actingAs($applicant, 'applicant')->get('/camaba/biodata')->assertOk()->assertSee('XXXL');
    }

    public function test_walk_in_form_validation(): void
    {
        $admin = User::factory()->create();
        $existing = $this->registerApplicant(['email' => 'online@example.com']);

        $this->actingAs($admin, 'web')
            ->post(route('admin.applicants.store'), $this->form(['nik' => $existing->nik]))
            ->assertSessionHasErrors('nik');

        $this->actingAs($admin, 'web')
            ->post(route('admin.applicants.store'), $this->form(['address' => 'Jl. Tanpa Kecamatan']))
            ->assertSessionHasErrors(['region_id', 'village_name']);

        $this->actingAs($admin, 'web')
            ->post(route('admin.applicants.store'), $this->form(['almamater_size' => '']))
            ->assertSessionHasErrors('almamater_size');

        $this->actingAs($admin, 'web')
            ->post(route('admin.applicants.store'), $this->form(['registration_paid' => 1]))
            ->assertSessionHasErrors(['payment_method_id']);

        $this->assertSame(1, Applicant::count());
    }

    public function test_camaba_biodata_requires_almamater_size(): void
    {
        $applicant = $this->registerApplicant();
        $applicant->update(['payment_status' => 'valid']);

        $personal = [
            'step' => 'pribadi',
            'full_name' => 'AMMAR',
            'nik' => $applicant->nik,
            'gender' => 'L',
            'birth_place' => 'Batam',
            'birth_date' => '2007-03-16',
            'religion_id' => 1,
            'citizenship' => 'ID',
            'special_need_id' => 0,
            'kps_recipient' => 0,
            'email' => $applicant->email,
            'phone' => '081234567890',
        ];

        $this->actingAs($applicant, 'applicant')->get('/camaba/biodata?step=pribadi')->assertSee('Ukuran Jas Almamater');

        $this->actingAs($applicant, 'applicant')->put('/camaba/biodata', $personal)->assertSessionHasErrors('almamater_size');

        $this->actingAs($applicant, 'applicant')
            ->put('/camaba/biodata', $personal + ['almamater_size' => 'M'])
            ->assertSessionHasNoErrors();

        $this->assertSame('M', $applicant->refresh()->almamater_size);
    }
}
