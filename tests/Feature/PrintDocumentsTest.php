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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('LUNAS')
            ->assertSee('Bagian Keuangan PMB');

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
            ->put('/admin/master-data/numbering/receipt', ['receipt_format' => 'KW/{TAHUN}', 'receipt_digits' => 4, 'receipt_signer_title' => 'Bendahara'])
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering/receipt', [
                'receipt_format' => '{URUT}/KW-{JENIS}/{ROMAWI}/{TAHUN}',
                'receipt_digits' => 4,
                'receipt_signer_title' => 'Bendahara PMB',
                'receipt_signer_name' => 'Siti Rahmawati, S.E.',
            ])
            ->assertSessionHasNoErrors();

        $payment = $this->waitingPayment($this->registerApplicant());

        $this->travelTo(now()->setDate(2027, 4, 15));
        $this->actingAs($admin, 'web')->patch('/admin/payments/' . $payment->id . '/accept');

        $this->assertSame('0001/KW-PMB/IV/2027', $payment->refresh()->receipt_number);

        $this->actingAs($payment->applicant, 'applicant')
            ->get('/camaba/cetak/kwitansi/' . $payment->id)
            ->assertSee('Bendahara PMB')
            ->assertSee('Siti Rahmawati, S.E.');
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

    public function test_admin_prints_invoice_and_receipt_from_payment_table(): void
    {
        $applicant = $this->registerApplicant(['full_name' => 'Nadia Putri']);
        $waiting = $this->waitingPayment($this->registerApplicant(['email' => 'tunggu@example.com']));
        $payment = $this->waitingPayment($applicant);
        $admin = User::factory()->create();
        $operator = User::factory()->operator()->create();

        $this->actingAs($admin, 'web')->patch('/admin/payments/' . $payment->id . '/accept');
        $payment->refresh();

        foreach ([$admin, $operator] as $user) {
            $this->actingAs($user, 'web')
                ->get('/admin/payments')
                ->assertOk()
                ->assertSee(route('admin.invoices.print', $payment->invoice_id))
                ->assertSee(route('admin.payments.receipt', $payment))
                ->assertSee(route('admin.invoices.print', $waiting->invoice_id))
                ->assertDontSee(route('admin.payments.receipt', $waiting))
                // Progress tagihan cukup satu status terbaru per baris.
                ->assertSeeInOrder(['Menunggu verifikasi', 'Jika divalidasi: lunas'])
                ->assertSee('Rp300.000 terbayar')
                ->assertDontSee('Sisa Sekarang');

            $this->actingAs($user, 'web')
                ->get(route('admin.payments.receipt', $payment))
                ->assertOk()
                ->assertSee($payment->receipt_number)
                ->assertSee('NADIA PUTRI')
                ->assertSee('href="' . route('admin.payments.index') . '"', false);

            $this->actingAs($user, 'web')
                ->get(route('admin.invoices.print', $payment->invoice_id))
                ->assertOk()
                ->assertSee($payment->invoice->invoice_number);
        }

        $this->actingAs($admin, 'web')->get(route('admin.payments.receipt', $waiting))->assertNotFound();

        // Halaman admin tidak bisa dibuka camaba.
        auth('web')->logout();
        $this->actingAs($applicant, 'applicant')->get(route('admin.payments.receipt', $payment))->assertRedirect(route('admin.login'));
    }

    public function test_receipt_shows_uploaded_signature_and_stamp(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $settings = [
            'receipt_format' => 'KW/{JENIS}/{TAHUN}/{URUT}',
            'receipt_digits' => 5,
            'receipt_signer_title' => 'Bendahara PMB',
            'receipt_signer_name' => 'Siti Rahmawati, S.E.',
        ];

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering/receipt', $settings + [
                'receipt_signature' => UploadedFile::fake()->image('ttd.png', 300, 120),
                'receipt_stamp' => UploadedFile::fake()->image('stempel.png', 200, 200),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering/receipt', $settings + ['receipt_signature' => UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('receipt_signature');

        $this->actingAs($admin, 'web')->get('/admin/master-data/penomoran')->assertSee('Gambar Tanda Tangan')->assertSee('Hapus gambar');

        $applicant = $this->registerApplicant();
        $payment = $this->waitingPayment($applicant);
        $this->actingAs($admin, 'web')->patch('/admin/payments/' . $payment->id . '/accept');

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/cetak/kwitansi/' . $payment->id)
            ->assertOk()
            ->assertSee('alt="Tanda tangan Siti Rahmawati, S.E."', false)
            ->assertSee('alt="Stempel"', false)
            ->assertSee('src="data:image/png;base64,', false);

        // Hapus tanda tangan: kwitansi kembali ditandatangani manual, stempel tetap.
        $this->actingAs($admin, 'web')
            ->put('/admin/master-data/numbering/receipt', $settings + ['remove_receipt_signature' => 1])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin, 'web')
            ->get(route('admin.payments.receipt', $payment))
            ->assertDontSee('alt="Tanda tangan', false)
            ->assertSee('alt="Stempel"', false);

        $this->assertCount(1, Storage::disk('local')->files('settings'));
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
