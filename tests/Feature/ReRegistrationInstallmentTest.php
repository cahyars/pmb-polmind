<?php

namespace Tests\Feature;

use App\Models\AchievementLevel;
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

        // Seeder (biaya resmi 2027): SPI 15 jt (Gel. 1 9 jt, Undangan 7,5 jt, Prestasi per kategori),
        // UKT Semester 1 7 jt. Hanya SPI yang bisa dicicil.
        $this->admin = User::factory()->create();
        $this->method = PaymentMethod::create(['type' => 'bank_transfer', 'provider' => 'Bank BCA', 'account_number' => '1234567890', 'is_active' => true]);
    }

    private function fee(string $code): FeeComponent
    {
        return FeeComponent::where('code', $code)->firstOrFail();
    }

    private function level(string $name): AchievementLevel
    {
        return AchievementLevel::where('name', $name)->firstOrFail();
    }

    private function decide(Applicant $applicant, array $extra = [])
    {
        $components = SelectionComponent::where('is_active', true)->pluck('id');

        return $this->actingAs($this->admin, 'web')
            ->patch(route('admin.selections.decide', $applicant), [
                'scores' => $components->mapWithKeys(fn ($id) => [$id => 80])->all(),
                'decision' => 'diterima_1',
            ] + $extra);
    }

    private function acceptedApplicant(string $path = 'umum', array $extra = []): Applicant
    {
        $applicant = $this->registerApplicant(['email' => $path . '@example.com', 'registration_path' => $path]);
        $applicant->update(['document_status' => 'valid', 'payment_status' => 'valid']);

        $this->decide($applicant, $extra)->assertSessionHasNoErrors();

        auth('web')->logout();

        return $applicant->refresh();
    }

    /**
     * Unggah bukti transfer daftar ulang untuk komponen (kode) yang dipilih; nominal dihitung sistem.
     */
    private function pay(Applicant $applicant, Invoice $invoice, array $codes)
    {
        return $this->actingAs($applicant, 'applicant')
            ->post(route('camaba.payments.upload-proof', $invoice), [
                'payment_method_id' => $this->method->id,
                'sender_name' => 'Ammar',
                'sender_bank' => 'BRI',
                'transfer_date' => now()->format('Y-m-d'),
                'components' => array_map(fn ($code) => $this->fee($code)->id, $codes),
                'proof_file' => UploadedFile::fake()->image('bukti.jpg'),
            ]);
    }

    private function acceptLatestPayment(Invoice $invoice): Payment
    {
        $payment = Payment::where('invoice_id', $invoice->id)->where('status', 'waiting_verification')->latest('id')->firstOrFail();

        $this->actingAs($this->admin, 'web')->patch(route('admin.payments.accept', $payment))->assertSessionHasNoErrors();

        return $payment->refresh();
    }

    private function spiItem(Applicant $applicant)
    {
        return $applicant->reRegistrationInvoice->items->firstWhere('fee_component_id', $this->fee('SPI')->id);
    }

    public function test_spi_follows_registration_wave_path_and_achievement_level(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get('/admin/master-data/biaya')
            ->assertOk()
            ->assertSee('Biaya Daftar Ulang per Gelombang')
            ->assertSee('Biaya Daftar Ulang per Jalur')
            ->assertSee('Kategori Prestasi (SPI Jalur Prestasi)')
            ->assertSee('Tingkat 1 · Nasional')
            ->assertDontSee('Template cicilan');

        auth('web')->logout();

        // Landing: nominal final per gelombang, tanpa harga coret.
        $this->get('/')
            ->assertSeeInOrder(['Sumbangan Pengembangan Institusi', 'Gelombang 1', 'Berlaku sekarang', 'Rp9.000.000', 'Gelombang 2', 'Rp10.000.000', 'Gelombang 5', 'Rp15.000.000'])
            ->assertSee('Dapatkan Beasiswa SPI untuk Jalur Prestasi sampai 70%')
            // Nominal jalur Undangan dan kategori Prestasi tidak dipublikasikan.
            ->assertDontSee('Rp7.500.000')
            ->assertDontSee('Rp4.500.000')
            ->assertDontSee('Tingkat 1 · Nasional')
            ->assertSee('Rp7.000.000')
            ->assertDontSee('line-through');

        $umum = $this->acceptedApplicant('umum');
        $this->assertSame('16000000.00', $umum->reRegistrationInvoice->total_amount);
        $this->assertSame('Sumbangan Pengembangan Institusi (Gelombang 1)', $this->spiItem($umum)->name);
        $this->assertSame('9000000.00', $this->spiItem($umum)->subtotal);
        // Batas bayar: akhir periode daftar ulang gelombang camaba.
        $this->assertSame($umum->admissionWave->re_registration_end_date->toDateString(), $umum->reRegistrationInvoice->due_date->toDateString());

        $undangan = $this->acceptedApplicant('undangan');
        $this->assertSame('14500000.00', $undangan->reRegistrationInvoice->total_amount);
        $this->assertSame('Sumbangan Pengembangan Institusi (Jalur Undangan)', $this->spiItem($undangan)->name);

        // Jalur Prestasi: kategori wajib dipilih saat diterima.
        $prestasi = $this->registerApplicant(['email' => 'prestasi@example.com', 'registration_path' => 'prestasi']);
        $prestasi->update(['document_status' => 'valid', 'payment_status' => 'valid']);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.selections.show', $prestasi->registration_number))
            ->assertSee('Kategori Prestasi')
            ->assertSee('Tingkat 1 · Nasional · SPI Rp4.500.000')
            ->assertSee('Tanpa kategori · SPI Gelombang 1 Rp9.000.000');

        $this->decide($prestasi)->assertSessionHasErrors('achievement_level');
        $this->assertNull($prestasi->refresh()->reRegistrationInvoice);

        $this->decide($prestasi, ['achievement_level' => $this->level('Tingkat 1 · Nasional')->id])->assertSessionHasNoErrors();
        $prestasi->refresh();
        $this->assertSame($this->level('Tingkat 1 · Nasional')->id, $prestasi->achievement_level_id);
        $this->assertSame('11500000.00', $prestasi->reRegistrationInvoice->total_amount);
        $this->assertSame('Sumbangan Pengembangan Institusi (Tingkat 1 · Nasional)', $this->spiItem($prestasi)->name);

        // Belum dibayar: kategori bisa diganti dan invoice dihitung ulang.
        $this->decide($prestasi, ['achievement_level' => 'none'])->assertSessionHasNoErrors();
        $prestasi->refresh();
        $this->assertNull($prestasi->achievement_level_id);
        $this->assertSame('16000000.00', $prestasi->reRegistrationInvoice->total_amount);
        $this->assertCount(2, $prestasi->reRegistrationInvoice->items);

        // Rekap pembayaran mencantumkan kategori prestasi di Keterangan.
        $this->decide($prestasi, ['achievement_level' => $this->level('Tingkat 2 · Provinsi')->id])->assertSessionHasNoErrors();
        $this->pay($prestasi, $prestasi->refresh()->reRegistrationInvoice, ['UKT_SEMESTER_1'])->assertSessionHasNoErrors();
        $this->acceptLatestPayment($prestasi->reRegistrationInvoice);
        $row = \App\Services\Reports\PaymentRecap::build(new \App\Services\Reports\ReportFilter)['rows']->first(fn ($row) => $row['applicant']->is($prestasi));
        $this->assertSame('Prestasi Tingkat 2 · Provinsi', $row['note']);
        $this->assertEquals(7000000, $row['components']['UKT']);

        // Sudah ada pembayaran: kategori tidak bisa diganti lagi.
        $this->decide($prestasi, ['achievement_level' => $this->level('Tingkat 1 · Nasional')->id])->assertSessionHasErrors();
        $this->assertSame($this->level('Tingkat 2 · Provinsi')->id, $prestasi->refresh()->achievement_level_id);
    }

    public function test_admin_sets_fees_per_wave_and_path_and_manages_achievement_levels(): void
    {
        $spi = $this->fee('SPI');
        $gel1 = AdmissionWave::where('code', 'GEL1')->firstOrFail();
        $gel2 = AdmissionWave::where('code', 'GEL2')->firstOrFail();
        $undangan = RegistrationPath::where('code', 'undangan')->firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.master-data.wave-fees.update'), [
                'amounts' => [$gel1->id => [$spi->id => ''], $gel2->id => [$spi->id => 11000000]],
            ])
            ->assertSessionHasNoErrors();

        // Kosong kembali ke nominal default.
        $this->assertSame('15000000.00', $spi->refresh()->load(['pathAmounts', 'waveAmounts'])->amountFor('umum', $gel1->id));
        $this->assertSame('11000000.00', $spi->amountFor('umum', $gel2->id));

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.master-data.path-fees.update'), [
                'amounts' => [$undangan->id => [$spi->id => 8000000]],
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('8000000.00', $spi->refresh()->load(['pathAmounts', 'waveAmounts'])->amountFor('undangan', $gel2->id));

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.master-data.achievement-levels.store'), ['name' => 'Tingkat 4', 'spi_amount' => 7000000, 'sort_order' => 4, 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $level = $this->level('Tingkat 4');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.master-data.achievement-levels.update', $level), ['name' => 'Tingkat 4 · Kota', 'description' => 'Juara tingkat kota', 'spi_amount' => 7500000, 'sort_order' => 4, 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('7500000.00', $level->refresh()->spi_amount);

        $this->actingAs($this->admin, 'web')->patch(route('admin.master-data.achievement-levels.toggle', $level));
        $this->assertFalse($level->refresh()->is_active);

        // Kategori nonaktif tidak bisa dipilih.
        $applicant = $this->registerApplicant(['email' => 'p@example.com', 'registration_path' => 'prestasi']);
        $applicant->update(['document_status' => 'valid', 'payment_status' => 'valid']);
        $this->decide($applicant, ['achievement_level' => $level->id])->assertSessionHasErrors('achievement_level');

        $operator = User::factory()->operator()->create();
        $this->actingAs($operator, 'web')
            ->put(route('admin.master-data.wave-fees.update'), ['amounts' => []])
            ->assertForbidden();
    }

    public function test_camaba_pays_ukt_and_spi_separately_and_re_registration_is_valid_when_both_are_paid(): void
    {
        $applicant = $this->acceptedApplicant();
        $invoice = $applicant->reRegistrationInvoice;

        // Kartu per komponen; tanpa cicilan tidak ada yang menyebut cicilan/angsuran.
        $page = $this->actingAs($applicant, 'applicant')->get('/camaba/pembayaran')->assertOk()
            ->assertSeeInOrder(['Formulir Pendaftaran', 'Daftar Ulang', 'Sumbangan Pengembangan Institusi', 'Uang Kuliah Tunggal - Semester 1'])
            ->assertSee('boleh dibayar satu per satu atau sekaligus')
            ->getContent();
        $this->assertDoesNotMatchRegularExpression('/cicil|jadwal pembayaran|angsuran/i', $page);

        // UKT dulu.
        $this->pay($applicant, $invoice, ['UKT_SEMESTER_1'])->assertSessionHasNoErrors();
        $ukt = Payment::where('invoice_id', $invoice->id)->latest('id')->firstOrFail();
        $this->assertSame('7000000.00', $ukt->amount);
        $this->assertSame([$this->fee('UKT_SEMESTER_1')->id], $ukt->allocations->pluck('fee_component_id')->all());

        // UKT masih diverifikasi: tidak bisa dibayar lagi, tetapi SPI bisa.
        $this->pay($applicant, $invoice, ['UKT_SEMESTER_1'])->assertSessionHasErrors();
        $this->pay($applicant, $invoice, ['SPI'])->assertSessionHasNoErrors();
        $this->assertSame('9000000.00', Payment::where('invoice_id', $invoice->id)->latest('id')->value('amount'));
        $this->pay($applicant, $invoice, [])->assertSessionHasErrors('components');

        $this->acceptLatestPayment($invoice);   // SPI
        $this->assertSame('partial', $invoice->refresh()->status);
        $this->assertNotSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);

        $this->actingAs($applicant, 'applicant')->get('/camaba/pembayaran')
            ->assertSee('Sumbangan Pengembangan Institusi Lunas');

        $this->actingAs($this->admin, 'web')->patch(route('admin.payments.accept', $ukt))->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->refresh()->status);
        $this->assertSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);

        // Kwitansi menyebut komponen yang dibayar transfer itu.
        $this->actingAs($applicant, 'applicant')->get(route('camaba.print.receipt', $ukt))
            ->assertOk()
            ->assertSee('Uang Kuliah Tunggal - Semester 1 Rp7.000.000');
        $this->pay($applicant, $invoice, ['SPI'])->assertSessionHasErrors();
    }

    public function test_camaba_pays_ukt_and_spi_in_one_transfer(): void
    {
        $applicant = $this->acceptedApplicant();
        $invoice = $applicant->reRegistrationInvoice;

        $this->pay($applicant, $invoice, ['UKT_SEMESTER_1', 'SPI'])->assertSessionHasNoErrors();
        $payment = $this->acceptLatestPayment($invoice);

        $this->assertSame('16000000.00', $payment->amount);
        $this->assertCount(2, $payment->allocations);
        $this->assertSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);

        $this->actingAs($this->admin, 'web')->get('/admin/payments')
            ->assertSee('Uang Kuliah Tunggal - Semester 1: <span class="font-semibold">Rp7.000.000</span>', false);

        // Rekap: satu kolom per komponen.
        $row = \App\Services\Reports\PaymentRecap::build(new \App\Services\Reports\ReportFilter)['rows']->first(fn ($row) => $row['applicant']->is($applicant));
        $this->assertEquals(7000000, $row['components']['UKT']);
        $this->assertEquals(9000000, $row['components']['SPI']);
    }

    public function test_admin_enables_spi_installments_and_re_registration_is_valid_after_first_payment(): void
    {
        $spi = $this->fee('SPI');
        $ukt = $this->fee('UKT_SEMESTER_1');

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

        // Admin melihat pilihan skema; UKT dibayar penuh bersama angsuran ke-1.
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertSee('Skema Cicilan Daftar Ulang')
            ->assertSeeInOrder(['Bayar penuh', '50:50', '50:25:25', '25:25:25:25'])
            ->assertSee('dibayar penuh bersama angsuran ke-1');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.applicants.installments.update', $applicant->registration_number), [
                'installments' => [
                    $items[$spi->id]->id => [
                        ['amount' => 4500000, 'due_date' => '2027-02-12'],
                        ['amount' => 2250000, 'due_date' => '2027-03-12'],
                        ['amount' => 2250000, 'due_date' => '2027-04-12'],
                    ],
                    // UKT tidak bisa dicicil: baris ini diabaikan.
                    $items[$ukt->id]->id => [
                        ['amount' => 3500000, 'due_date' => '2027-02-12'],
                        ['amount' => 3500000, 'due_date' => '2027-03-12'],
                    ],
                ],
                'installment_note' => 'Disetujui Wadir II',
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh()->load(['installments', 'payments']);
        $this->assertTrue($invoice->hasInstallments());
        $this->assertSame(1, $invoice->installments->where('fee_component_id', $ukt->id)->count());
        // Angsuran ke-1: UKT 7 jt + SPI 4,5 jt.
        $this->assertSame(11500000.0, $invoice->firstInstallmentAmount());

        $this->actingAs($applicant, 'applicant')
            ->get('/camaba/pembayaran')
            ->assertSee('Lihat jadwal pembayaran')
            ->assertSeeInOrder(['Pembayaran ke-1', 'Rp11.500.000', 'Pembayaran ke-2', 'Rp2.250.000', 'Pembayaran ke-3'])
            ->assertSeeInOrder(['Sumbangan Pengembangan Institusi', 'Angsuran berikutnya', 'Rp4.500.000'])
            ->assertDontSee('Disetujui Wadir II');

        // Angsuran SPI ke-1 saja belum cukup: UKT juga wajib lunas.
        $this->pay($applicant, $invoice, ['SPI'])->assertSessionHasNoErrors();
        $this->assertSame('4500000.00', $this->acceptLatestPayment($invoice)->amount);
        $this->assertNotSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);

        $this->pay($applicant, $invoice, ['UKT_SEMESTER_1'])->assertSessionHasNoErrors();
        $this->acceptLatestPayment($invoice);

        $applicant->refresh();
        $this->assertSame('daftar_ulang_valid', $applicant->re_registration_status);
        $this->assertSame('partial', $invoice->refresh()->status);

        // Menyimpan ulang keputusan seleksi tidak memulai ulang daftar ulang yang sudah dibayar.
        $this->decide($applicant)->assertSessionHasNoErrors();
        $this->assertSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);
        $this->assertTrue($invoice->refresh()->hasInstallments());

        // Siap ditarik SIAKAD setelah NIM dibubuhkan.
        $this->assertSame('belum_siap', $applicant->sync_status);
        $this->actingAs($this->admin, 'web')->post(route('admin.nim.generate-one', $applicant))->assertSessionHasNoErrors();
        $this->assertSame('siap_sinkron', $applicant->refresh()->sync_status);

        // Angsuran berikutnya tidak membuat status daftar ulang turun lagi.
        $applicant->update(['sync_status' => 'sudah_sinkron']);
        $this->pay($applicant, $invoice, ['SPI'])->assertSessionHasNoErrors();
        $this->assertSame('daftar_ulang_valid', $applicant->refresh()->re_registration_status);

        $this->assertSame('2250000.00', $this->acceptLatestPayment($invoice)->amount);
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
            // Invoice bisa dilihat walau belum ada pembayaran.
            ->assertSee('href="' . route('admin.invoices.print', $applicant->registrationInvoice) . '"', false)
            ->assertSee('Lihat invoice (tagihan)')
            ->assertDontSee('controller update di tahap berikutnya');
    }
}
