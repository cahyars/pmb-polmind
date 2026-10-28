<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\Terbilang;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class PrintDocumentsTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_terbilang_rupiah(): void
    {
        $this->assertSame('Tiga ratus ribu rupiah', Terbilang::rupiah(300000));
        $this->assertSame('Satu juta dua ratus lima puluh ribu rupiah', Terbilang::rupiah('1250000.00'));
        $this->assertSame('Seribu seratus lima belas rupiah', Terbilang::rupiah(1115));
        $this->assertSame('Sebelas juta rupiah', Terbilang::rupiah(11000000));
        $this->assertSame('Dua puluh satu rupiah', Terbilang::rupiah(21));
    }

    public function test_validated_payment_gets_receipt_number_and_applicant_prints_receipt(): void
    {
        $applicant = $this->registerApplicant();
        $other = $this->registerApplicant(['email' => 'lain@example.com']);
        $payment = $this->waitingPayment($applicant);
        $admin = User::factory()->create(['name' => 'Rina PMB']);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/kwitansi/' . $payment->id)
            ->assertNotFound();

        $this->actingAs($admin, 'web')->patch('/admin/payments/' . $payment->id . '/accept');

        $this->assertSame('KW/PMB/2027/00001', $payment->refresh()->receipt_number);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertSee('Cetak Kwitansi')
            ->assertSee(route('camaba.print.receipt', $payment), false);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/kwitansi/' . $payment->id)
            ->assertOk()
            ->assertSee('KW/PMB/2027/00001')
            ->assertSee('Tiga ratus ribu rupiah')
            ->assertSee('LUNAS');

        $this->actingAs($other, 'applicant')
            ->get('/camaba/cetak/kwitansi/' . $payment->id)
            ->assertNotFound();
    }

    public function test_admin_configures_receipt_number_format(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/master-data/penomoran')
            ->assertSee('Nomor Kwitansi')
            ->assertSee('KW/{JENIS}/{TAHUN}/{URUT}');

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering/receipt', ['receipt_format' => 'KW/{TAHUN}', 'receipt_digits' => 4])
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering/receipt', ['receipt_format' => '{URUT}/KW-{JENIS}/{ROMAWI}/{TAHUN}', 'receipt_digits' => 4])
            ->assertSessionHasNoErrors();

        $payment = $this->waitingPayment($this->registerApplicant());

        $this->travelTo(now()->setDate(2027, 4, 15));
        $this->actingAs($admin, 'web')->patch('/admin/payments/' . $payment->id . '/accept');

        $this->assertSame('0001/KW-PMB/IV/2027', $payment->refresh()->receipt_number);
    }

    public function test_applicant_prints_own_invoice_only(): void
    {
        $applicant = $this->registerApplicant();
        $other = $this->registerApplicant(['email' => 'lain@example.com']);
        $invoice = $applicant->registrationInvoice()->first();

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertSee(route('camaba.print.invoice', $invoice), false);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/invoice/' . $invoice->id)
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('BELUM DIBAYAR')
            ->assertSee('Cetak / Simpan PDF');

        $this->actingAs($other, 'applicant')
            ->get('/camaba/cetak/invoice/' . $invoice->id)
            ->assertNotFound();

        $invoice->refresh()->waive('Perintah pimpinan', 'Admin PMB');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/invoice/' . $invoice->id)
            ->assertSee('GRATIS')
            ->assertSee('Dibebaskan');
    }

    public function test_exam_card_requires_paid_form_and_complete_biodata(): void
    {
        $applicant = $this->registerApplicant(['full_name' => 'Zaidan Azffa']);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/kartu-ujian')
            ->assertRedirect('/camaba/dashboard');

        $applicant->update(['payment_status' => 'valid']);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/status-seleksi')
            ->assertSee('Kartu ujian dapat dicetak setelah biodata lengkap.');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/kartu-ujian')
            ->assertForbidden();

        $applicant->update(['registration_status' => 'biodata_lengkap']);

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/status-seleksi')
            ->assertSee('Cetak Kartu Ujian');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/kartu-ujian')
            ->assertOk()
            ->assertSee('Kartu Peserta Ujian Seleksi')
            ->assertSee('ZAIDAN AZFFA')
            ->assertSee($applicant->registration_number);
    }

    private function waitingPayment(Applicant $applicant): Payment
    {
        $invoice = $applicant->registrationInvoice()->first();
        $invoice->update(['status' => 'waiting_verification']);
        $applicant->update(['payment_status' => 'menunggu_verifikasi']);

        return Payment::create([
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/PMB/2027/' . str_pad((string) $applicant->id, 5, '0', STR_PAD_LEFT),
            'transfer_date' => '2027-04-10',
            'sender_name' => $applicant->full_name,
            'sender_bank' => 'BRI',
            'amount' => $invoice->total_amount,
            'status' => 'waiting_verification',
        ]);
    }
}
