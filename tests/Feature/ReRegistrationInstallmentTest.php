<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\RefOption;
use App\Models\RegistrationPath;
use App\Models\SelectionComponent;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\SelectionScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class ReRegistrationInstallmentTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private User $admin;

    private PaymentMethod $method;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();
        Storage::fake('local');

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class, SelectionScheduleSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);

        // Seeder: DAFTAR_ULANG 2,5 jt, SPI_AWAL 5 jt, SPP_SEMESTER_1 4 jt. SPI dan SPP bisa dicicil.
        FeeComponent::whereIn('code', ['SPI_AWAL', 'SPP_SEMESTER_1'])->update(['is_installable' => true]);

        $this->admin = User::factory()->create();
        $this->method = PaymentMethod::create(['type' => 'bank_transfer', 'provider' => 'Bank BCA', 'account_number' => '1234567890', 'is_active' => true]);
    }

    private function fee(string $code): FeeComponent
    {
        return FeeComponent::where('code', $code)->firstOrFail();
    }

    private function acceptedApplicant(string $path = 'umum'): Applicant
    {
        $applicant = $this->registerApplicant(['email' => $path . '@example.com', 'registration_path' => $path]);
        $applicant->update(['document_status' => 'valid', 'payment_status' => 'valid']);

        $components = SelectionComponent::where('is_active', true)->pluck('id');

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.selections.decide', $applicant), [
                'scores' => $components->mapWithKeys(fn ($id) => [$id => 80])->all(),
                'decision' => 'diterima_1',
            ])
            ->assertSessionHasNoErrors();

        auth('web')->logout();

        return $applicant->refresh();
    }

    private function pay(Applicant $applicant, Invoice $invoice, int $amount)
    {
        return $this->actingAs($applicant, 'applicant')
            ->post(route('camaba.payments.upload-proof', $invoice), [
                'payment_method_id' => $this->method->id,
                'sender_name' => 'Ammar',
                'sender_bank' => 'BRI',
                'transfer_date' => now()->format('Y-m-d'),
                'amount' => $amount,
                'proof_file' => UploadedFile::fake()->image('bukti.jpg'),
            ]);
    }

    public function test_re_registration_amount_follows_registration_path(): void
    {
        $prestasi = RegistrationPath::where('code', 'prestasi')->firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->get('/admin/master-data/biaya')
            ->assertOk()
            ->assertSee('Biaya Daftar Ulang per Jalur')
            ->assertSee('Template cicilan');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.master-data.registration-paths.fees', $prestasi), [
                'amounts' => [$this->fee('SPI_AWAL')->id => 1000000],
            ])
            ->assertSessionHasNoErrors();

        auth('web')->logout();

        $this->get('/')->assertSee('Jalur Prestasi')->assertSee('Rp1.000.000');

        $umum = $this->acceptedApplicant('umum');
        $berprestasi = $this->acceptedApplicant('prestasi');

        $this->assertSame('11500000.00', $umum->reRegistrationInvoice->total_amount);
        $this->assertSame('7500000.00', $berprestasi->reRegistrationInvoice->total_amount);
        $this->assertSame('1000000.00', $berprestasi->reRegistrationInvoice->items->firstWhere('fee_component_id', $this->fee('SPI_AWAL')->id)->subtotal);
    }

    public function test_installment_template_must_match_amount(): void
    {
        $umum = RegistrationPath::where('code', 'umum')->firstOrFail();
        $spi = $this->fee('SPI_AWAL');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.master-data.registration-paths.fees', $umum), [
                'installments' => [$spi->id => [
                    ['amount' => 2000000, 'due_date' => '2027-08-01'],
                    ['amount' => 2000000, 'due_date' => '2027-09-01'],
                ]],
            ])
            ->assertSessionHasErrors();

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.master-data.registration-paths.fees', $umum), [
                'installments' => [$spi->id => [
                    ['amount' => 3000000, 'due_date' => '2027-09-01'],
                    ['amount' => 2000000, 'due_date' => '2027-08-01'],
                ]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [[1, '2000000.00', '2027-08-01'], [2, '3000000.00', '2027-09-01']],
            $spi->installmentTemplates()->get()->map(fn ($row) => [$row->sequence, $row->amount, $row->due_date->toDateString()])->all()
        );
    }

    public function test_without_installments_re_registration_is_paid_at_once_and_nothing_mentions_installments(): void
    {
        $applicant = $this->acceptedApplicant();
        $invoice = $applicant->reRegistrationInvoice;

        $page = $this->actingAs($applicant, 'applicant')->get('/camaba/pembayaran')->assertOk()->getContent();
        // Nama komponen dari Master Data (mis. "Angsuran SPI") boleh tampil; fitur cicilan tidak.
        $this->assertDoesNotMatchRegularExpression('/cicil|jadwal pembayaran|tagihan berikutnya/i', $page);

        $this->pay($applicant, $invoice, 5000000)->assertSessionHasErrors();
        $this->pay($applicant, $invoice, 11500000)->assertSessionHasNoErrors();
    }

    public function test_admin_enables_installments_and_re_registration_is_valid_after_first_payment(): void
    {
        $umum = RegistrationPath::where('code', 'umum')->firstOrFail();
        $spi = $this->fee('SPI_AWAL');
        $spp = $this->fee('SPP_SEMESTER_1');

        $this->actingAs($this->admin, 'web')->put(route('admin.master-data.registration-paths.fees', $umum), [
            'installments' => [$spi->id => [
                ['amount' => 2500000, 'due_date' => '2027-08-01'],
                ['amount' => 2500000, 'due_date' => '2027-10-01'],
            ]],
        ])->assertSessionHasNoErrors();

        $applicant = $this->acceptedApplicant();
        $invoice = $applicant->reRegistrationInvoice;
        $items = $invoice->items->keyBy('fee_component_id');

        // Operator tidak melihat dan tidak bisa mengatur cicilan.
        $operator = User::factory()->operator()->create();
        $this->actingAs($operator, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertOk()
            ->assertDontSee('Skema Cicilan');
        $this->actingAs($operator, 'web')
            ->put(route('admin.applicants.installments.update', $applicant->registration_number), [])
            ->assertForbidden();

        // Admin melihat form terisi dari template jalur.
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertSee('Skema Cicilan Daftar Ulang')
            ->assertSee('2027-10-01');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.applicants.installments.update', $applicant->registration_number), [
                'installments' => [
                    $items[$spi->id]->id => [
                        ['amount' => 2500000, 'due_date' => '2027-08-01'],
                        ['amount' => 2500000, 'due_date' => '2027-10-01'],
                    ],
                    $items[$spp->id]->id => [
                        ['amount' => 2000000, 'due_date' => '2027-08-01'],
                        ['amount' => 2000000, 'due_date' => '2027-11-01'],
                    ],
                ],
                'installment_note' => 'Disetujui Wadir II',
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh()->load(['installments', 'payments']);
        $this->assertTrue($invoice->hasInstallments());
        // Angsuran ke-1: Daftar Ulang 2,5 jt + SPI 2,5 jt + SPP 2 jt.
        $this->assertSame(7000000.0, $invoice->firstInstallmentAmount());

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertSee('Jadwal pembayaran')
            ->assertSee('Tagihan berikutnya Rp7.000.000')
            ->assertDontSee('Disetujui Wadir II');

        $this->pay($applicant, $invoice, 7000000)->assertSessionHasNoErrors();
        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();

        $this->actingAs($this->admin, 'web')->patch(route('admin.payments.accept', $payment))->assertSessionHasNoErrors();

        $applicant->refresh();
        $this->assertSame('daftar_ulang_valid', $applicant->re_registration_status);
        $this->assertSame('partial', $invoice->refresh()->status);

        // Siap ditarik SIAKAD setelah NIM dibubuhkan.
        $this->assertSame('belum_siap', $applicant->sync_status);
        $this->actingAs($this->admin, 'web')->post(route('admin.nim.generate-one', $applicant))->assertSessionHasNoErrors();
        $this->assertSame('siap_sinkron', $applicant->refresh()->sync_status);

        // Angsuran berikutnya tidak membuat status daftar ulang turun lagi.
        $applicant->update(['sync_status' => 'sudah_sinkron']);
        $this->pay($applicant, $invoice, 2500000)->assertSessionHasNoErrors();
        $this->assertSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);

        $second = Payment::where('invoice_id', $invoice->id)->where('status', 'waiting_verification')->firstOrFail();
        $this->actingAs($this->admin, 'web')->patch(route('admin.payments.accept', $second))->assertSessionHasNoErrors();
        $this->assertSame('sudah_sinkron', $applicant->refresh()->sync_status);

        // Cicilan tidak bisa dimatikan setelah daftar ulang valid lewat cicilan.
        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.applicants.installments.destroy', $applicant->registration_number))
            ->assertSessionHasErrors();
    }

    public function test_detail_page_actions_link_to_process_pages(): void
    {
        $applicant = $this->registerApplicant();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertSee('href="' . route('admin.documents.show', $applicant->registration_number) . '"', false)
            ->assertSee('href="' . route('admin.payments.index', ['keyword' => $applicant->registration_number]) . '"', false)
            ->assertSee('href="' . route('admin.selections.show', $applicant->registration_number) . '"', false)
            ->assertDontSee('controller update di tahap berikutnya');
    }
}
