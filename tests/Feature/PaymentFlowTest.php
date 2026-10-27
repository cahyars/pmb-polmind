<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\RefOption;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Storage::fake('local');

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_unpaid_invoice_follows_fee_from_master_data(): void
    {
        $applicant = $this->register();

        FeeComponent::where('code', 'PENDAFTARAN')->update(['amount' => 250000]);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertOk()
            ->assertSee('Rp250.000');

        $invoice = $applicant->registrationInvoice()->first();

        $this->assertSame('250000.00', (string) $invoice->total_amount);
        $this->assertSame('250000.00', (string) $invoice->items()->first()->subtotal);
    }

    public function test_proof_upload_requires_destination_and_shows_waiting_notice(): void
    {
        $bca = PaymentMethod::create(['type' => 'bank_transfer', 'provider' => 'Bank BCA', 'account_number' => '1234567890', 'account_name' => 'Politeknik Mitra Industri', 'is_active' => true]);
        PaymentMethod::create(['type' => 'bank_transfer', 'provider' => 'Bank Mandiri', 'account_number' => '9876543210', 'is_active' => true]);
        PaymentMethod::create(['type' => 'ewallet', 'provider' => 'Rekening Lama', 'account_number' => '000', 'is_active' => false]);

        $applicant = $this->register();
        $invoice = $applicant->registrationInvoice()->first();

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertSee('Bank BCA')
            ->assertSee('1234567890')
            ->assertSee('Bank Mandiri')
            ->assertDontSee('Rekening Lama')
            ->assertDontSee('Sisa Tagihan');

        $proof = fn () => [
            'sender_name' => 'Ammar',
            'sender_bank' => 'BRI',
            'transfer_date' => now()->format('Y-m-d'),
            'amount' => 300000,
            'proof_file' => UploadedFile::fake()->image('bukti.jpg'),
        ];

        $this->actingAs($applicant, 'applicant')
            ->post(route('camaba.payments.upload-proof', $invoice), $proof())
            ->assertSessionHasErrors('payment_method_id');

        $this->actingAs($applicant, 'applicant')
            ->post(route('camaba.payments.upload-proof', $invoice), array_merge($proof(), ['payment_method_id' => $bca->id, 'amount' => 100000]))
            ->assertSessionHasErrors();

        $this->actingAs($applicant, 'applicant')
            ->from('/camaba/pembayaran')
            ->post(route('camaba.payments.upload-proof', $invoice), array_merge($proof(), ['payment_method_id' => $bca->id]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('payment_submitted', true)
            ->assertRedirect('/camaba/pembayaran');

        $payment = Payment::firstOrFail();

        $this->assertSame($bca->id, $payment->payment_method_id);
        $this->assertSame('PAY/PMB/2027/00001', $payment->payment_number);
        $this->assertSame('menunggu_verifikasi', $applicant->refresh()->payment_status);

        $this->actingAs($applicant, 'applicant')
            ->withSession(['payment_submitted' => true])
            ->get('/camaba/pembayaran')
            ->assertSee('sedang diverifikasi')
            ->assertSee('Menunggu Verifikasi');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/dashboard')
            ->assertSee('Pembayaran sedang diverifikasi');
    }

    public function test_admin_manages_payment_methods(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->post('/admin/payment-methods', ['type' => 'bank_transfer', 'provider' => 'Bank BNI', 'account_number' => '12 3456 7890', 'account_name' => 'Polmind', 'is_active' => 1])
            ->assertSessionHasNoErrors();

        $method = PaymentMethod::firstOrFail();
        $this->assertSame('1234567890', $method->account_number);

        $this->actingAs($admin, 'web')
            ->post('/admin/payment-methods', ['type' => 'bank_transfer', 'provider' => 'Tanpa Nomor'])
            ->assertSessionHasErrors('account_number');

        $this->actingAs($admin, 'web')->get('/admin/payment-methods')->assertOk()->assertSee('Bank BNI');

        $this->actingAs($admin, 'web')->patch("/admin/payment-methods/{$method->id}/toggle");
        $this->assertFalse($method->refresh()->is_active);

        $this->actingAs($admin, 'web')->delete("/admin/payment-methods/{$method->id}");
        $this->assertDatabaseCount('payment_methods', 0);
    }

    public function test_admin_sidebar_shows_badges_for_pending_work(): void
    {
        $admin = User::factory()->create();

        $first = $this->register('satu@example.com');
        $this->register('dua@example.com');

        Payment::create([
            'applicant_id' => $first->id,
            'invoice_id' => $first->registrationInvoice()->value('id'),
            'payment_number' => 'PAY/TEST/1',
            'amount' => 300000,
            'status' => 'waiting_verification',
        ]);

        ApplicantDocument::create([
            'applicant_id' => $first->id,
            'document_type_id' => DocumentType::where('code', 'IDENTITAS')->value('id'),
            'file_path' => 'x.jpg',
            'status' => 'menunggu_verifikasi',
        ]);

        $html = $this->actingAs($admin, 'web')->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/title="2 camaba baru/', $html);
        $this->assertMatchesRegularExpression('/title="1 berkas menunggu verifikasi/', $html);
        $this->assertMatchesRegularExpression('/title="1 pembayaran menunggu verifikasi/', $html);

        // Membuka Data Camaba menandai camaba baru sudah dilihat.
        $this->actingAs($admin, 'web')->get('/admin/applicants')->assertOk();
        $this->travel(1)->seconds();

        $this->assertDoesNotMatchRegularExpression('/camaba baru sejak/', $this->actingAs($admin, 'web')->get('/admin/dashboard')->getContent());
    }

    public function test_command_finalizes_legacy_numbers(): void
    {
        $applicant = $this->register();

        $applicant->update([
            'registration_number' => 'PMB20270001',
            'class_type_id' => ClassType::where('code', 'REG_A')->value('id'),
        ]);

        $this->artisan('pmb:finalisasi-nomor')->assertSuccessful();

        // A120270001 sudah terpakai saat registrasi, sehingga nomor berikutnya yang dipakai.
        $this->assertSame('A120270002', $applicant->refresh()->registration_number);
    }

    private function register(string $email = 'ammar@example.com'): Applicant
    {
        return $this->registerApplicant(['email' => $email]);
    }
}
