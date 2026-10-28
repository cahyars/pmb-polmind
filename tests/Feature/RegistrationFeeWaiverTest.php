<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class RegistrationFeeWaiverTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_admin_waives_registration_fee_and_applicant_continues_to_biodata(): void
    {
        $applicant = $this->registerApplicant();
        $admin = User::factory()->create(['name' => 'Rina PMB']);
        $url = '/admin/applicants/' . $applicant->registration_number;

        $this->actingAs($admin, 'web')
            ->get($url)
            ->assertOk()
            ->assertSee('Gratiskan Formulir')
            ->assertSee('Perintah pimpinan: pendaftar dari SMK NEGERI 1 BATAM');

        $this->actingAs($admin, 'web')
            ->patch($url . '/waive-registration-fee', ['waiver_reason' => ''])
            ->assertSessionHasErrors('waiver_reason');

        $this->actingAs($admin, 'web')
            ->patch($url . '/waive-registration-fee', ['waiver_reason' => 'Perintah Direktur: SMK Negeri 1 Batam'])
            ->assertSessionHas('success');

        $invoice = $applicant->registrationInvoice()->first();

        $this->assertSame('paid', $invoice->status);
        $this->assertSame('Rina PMB', $invoice->waived_by_name);
        $this->assertSame('valid', $applicant->refresh()->payment_status);
        $this->assertSame(0, Payment::count());

        $this->actingAs($admin, 'web')
            ->get($url)
            ->assertSee('Biaya formulir sudah dibebaskan.')
            ->assertDontSee('Gratiskan Formulir</button>', false);

        $this->actingAs($admin, 'web')
            ->get('/admin/applicants')
            ->assertSee('Formulir Gratis');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertSee('Formulir Gratis')
            ->assertSee('Dibebaskan');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/biodata')
            ->assertOk();

        // Tidak bisa dibebaskan dua kali.
        $this->actingAs($admin, 'web')
            ->patch($url . '/waive-registration-fee', ['waiver_reason' => 'Ulang'])
            ->assertSessionHasErrors();
    }

    public function test_fee_with_payment_waiting_verification_cannot_be_waived(): void
    {
        $applicant = $this->registerApplicant();
        $invoice = $applicant->registrationInvoice()->first();

        Payment::create([
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/PMB/2027/00001',
            'amount' => $invoice->total_amount,
            'status' => 'waiting_verification',
        ]);
        $invoice->update(['status' => 'waiting_verification']);

        $this->actingAs(User::factory()->create(), 'web')
            ->patch('/admin/applicants/' . $applicant->registration_number . '/waive-registration-fee', ['waiver_reason' => 'Perintah pimpinan'])
            ->assertSessionHasErrors();

        $this->assertNull($invoice->refresh()->waived_at);
    }
}
