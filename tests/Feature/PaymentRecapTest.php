<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reports\PaymentRecap;
use App\Services\Reports\ReportFilter;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class PaymentRecapTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private int $paymentCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    private function pay(Invoice $invoice, int $amount, string $date, string $status = 'valid'): Payment
    {
        return Payment::create([
            'applicant_id' => $invoice->applicant_id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/' . (++$this->paymentCounter),
            'transfer_date' => $date,
            'amount' => $amount,
            'status' => $status,
            'verified_at' => $status === 'valid' ? $date . ' 12:00' : null,
        ]);
    }

    /**
     * Invoice daftar ulang: Daftar Ulang 2,5 jt + SPI 5 jt.
     */
    private function reRegistrationInvoice(Applicant $applicant, array $installments = []): Invoice
    {
        $invoice = Invoice::create([
            'applicant_id' => $applicant->id,
            'invoice_number' => 'INV/DU/' . $applicant->id,
            'type' => 're_registration',
            'total_amount' => 7500000,
            'status' => 'unpaid',
        ]);

        foreach ([['DAFTAR_ULANG', 'Daftar Ulang', 2500000], ['SPI_AWAL', 'SPI', 5000000]] as $order => [$code, $name, $amount]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_component_id' => FeeComponent::where('code', $code)->value('id'),
                'name' => $name,
                'amount' => $amount,
                'quantity' => 1,
                'subtotal' => $amount,
                'sort_order' => $order + 1,
            ]);
        }

        foreach ($installments as [$code, $sequence, $amount, $due]) {
            $invoice->installments()->create([
                'fee_component_id' => FeeComponent::where('code', $code)->value('id'),
                'name' => $code,
                'sequence' => $sequence,
                'amount' => $amount,
                'due_date' => $due,
            ]);
        }

        return $invoice;
    }

    public function test_week_of_month_starts_on_monday(): void
    {
        $this->assertSame(1, PaymentRecap::weekOfMonth(Carbon::parse('2026-03-01'))); // Minggu
        $this->assertSame(2, PaymentRecap::weekOfMonth(Carbon::parse('2026-03-02'))); // Senin
        $this->assertSame(6, PaymentRecap::weekOfMonth(Carbon::parse('2026-03-31')));
        $this->assertSame(5, PaymentRecap::weekOfMonth(Carbon::parse('2026-02-28')));
        $this->assertSame(6, PaymentRecap::weekOfMonth(Carbon::parse('2026-08-31')));
        $this->assertSame(1, PaymentRecap::weekOfMonth(Carbon::parse('2026-06-07')));
    }

    public function test_recap_groups_income_per_week_and_component(): void
    {
        $andi = $this->registerApplicant(['full_name' => 'Andi', 'email' => 'andi@example.com']);
        $budi = $this->registerApplicant(['full_name' => 'Budi', 'email' => 'budi@example.com', 'registration_path' => 'prestasi']);
        $belum = $this->registerApplicant(['full_name' => 'Belum Bayar', 'email' => 'belum@example.com']);

        // Andi: formulir, lalu daftar ulang dicicil tanpa jadwal (urutan komponen invoice).
        $this->pay($andi->registrationInvoice, 300000, '2027-03-02');
        $andiDu = $this->reRegistrationInvoice($andi);
        $this->pay($andiDu, 4000000, '2027-03-10');
        $this->pay($andiDu, 1000000, '2027-03-11', 'waiting_verification');

        // Budi: jadwal cicilan dari admin (angsuran ke-1 = DU penuh + SPI 2,5 jt).
        $this->pay($budi->registrationInvoice, 300000, '2027-03-03');
        $budiDu = $this->reRegistrationInvoice($budi, [
            ['DAFTAR_ULANG', 1, 2500000, '2027-04-01'],
            ['SPI_AWAL', 1, 2500000, '2027-04-01'],
            ['SPI_AWAL', 2, 2500000, '2027-05-01'],
        ]);
        $this->pay($budiDu, 5000000, '2027-04-05');
        $this->pay($budiDu, 2500000, '2027-04-06', 'rejected');

        $this->pay($belum->registrationInvoice, 300000, '2027-03-04', 'rejected');

        $report = PaymentRecap::build(new ReportFilter);

        $this->assertSame(['Formulir', 'Daftar Ulang', 'SPI'], $report['components']->all());
        $this->assertSame(['ANDI', 'BUDI'], $report['rows']->map(fn ($row) => $row['applicant']->full_name)->all());

        [$andiRow, $budiRow] = $report['rows']->all();

        $this->assertEquals(['Formulir' => 300000, 'Daftar Ulang' => 2500000, 'SPI' => 1500000], $andiRow['components']);
        $this->assertEquals(4300000, $andiRow['paid']);
        $this->assertEquals(3500000, $andiRow['outstanding']);

        $this->assertEquals(['Formulir' => 300000, 'Daftar Ulang' => 2500000, 'SPI' => 2500000], $budiRow['components']);
        $this->assertEquals(2500000, $budiRow['outstanding']);

        // Minggu berurutan 1 Maret s.d. 5 April 2027 (1 Maret 2027 hari Senin).
        $this->assertSame(['2027-03-1', '2027-03-2', '2027-03-3', '2027-03-4', '2027-03-5', '2027-04-1', '2027-04-2'], $report['weeks']->pluck('key')->all());
        $this->assertEquals(300000, $andiRow['weekly']['2027-03-1']);
        $this->assertEquals(4000000, $andiRow['weekly']['2027-03-2']);
        $this->assertEquals(5000000, $budiRow['weekly']['2027-04-2']);
        $this->assertEquals(['label' => 'Maret 2027', 'span' => 5], $report['months']->first());

        $this->assertEquals(9600000, $report['totals']['paid']);
        $this->assertEquals(['Formulir' => 600000, 'Daftar Ulang' => 5000000, 'SPI' => 4000000], $report['totals']['components']);

        // Filter tanggal bayar: formulir di luar periode, pembagian komponen tetap benar.
        $april = PaymentRecap::build(new ReportFilter(['start_date' => '2027-04-01']));
        $this->assertSame(['BUDI'], $april['rows']->map(fn ($row) => $row['applicant']->full_name)->all());
        $this->assertEquals(['Formulir' => 0, 'Daftar Ulang' => 2500000, 'SPI' => 2500000], $april['rows']->first()['components']);
    }

    public function test_report_page_and_excel_follow_template(): void
    {
        $andi = $this->registerApplicant(['full_name' => 'Andi', 'email' => 'andi@example.com']);
        $this->pay($andi->registrationInvoice, 300000, '2027-03-02');
        $this->pay($this->reRegistrationInvoice($andi), 7500000, '2027-03-16');

        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/reports/pembayaran')
            ->assertOk()
            ->assertSee('Rekap Pembayaran')
            ->assertSee('Per Komponen')
            ->assertSeeInOrder(['ANDI', 'D4 - Teknologi Rekayasa Perangkat Lunak', 'Reguler A'])
            ->assertSee('7.800.000')
            ->assertSee(route('admin.reports.export-payments'));

        $response = $this->actingAs($admin, 'web')->get(route('admin.reports.export-payments'))->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));

        $path = tempnam(sys_get_temp_dir(), 'rekap') . '.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame(['No', 'Gelombang', 'Nama Lengkap', 'Prodi', 'Kelas'], [
            $sheet->getCell('A2')->getValue(), $sheet->getCell('B2')->getValue(), $sheet->getCell('C2')->getValue(),
            $sheet->getCell('D2')->getValue(), $sheet->getCell('E2')->getValue(),
        ]);
        $this->assertSame('Maret 2027', $sheet->getCell('F1')->getValue());
        $this->assertContains('F1:H1', array_keys($sheet->getMergeCells()));
        $this->assertSame([1, 2, 3], [$sheet->getCell('F2')->getValue(), $sheet->getCell('G2')->getValue(), $sheet->getCell('H2')->getValue()]);
        $this->assertSame('ANDI', $sheet->getCell('C3')->getValue());
        $this->assertSame(1, $sheet->getCell('B3')->getValue());
        $this->assertEquals(300000, $sheet->getCell('F3')->getValue());
        $this->assertEquals(7500000, $sheet->getCell('H3')->getValue());
        $this->assertSame(['Formulir', 'Daftar Ulang', 'SPI', 'Pembayaran', 'Outstanding', 'Jalur Daftar'], [
            $sheet->getCell('I2')->getValue(), $sheet->getCell('J2')->getValue(), $sheet->getCell('K2')->getValue(),
            $sheet->getCell('L2')->getValue(), $sheet->getCell('M2')->getValue(), $sheet->getCell('N2')->getValue(),
        ]);
        $this->assertEquals(7800000, $sheet->getCell('L3')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('C4')->getValue());
        $this->assertSame('=SUM(L3:L3)', $sheet->getCell('L4')->getValue());
        $this->assertSame('F3', $sheet->getFreezePane());

        @unlink($path);
    }
}
