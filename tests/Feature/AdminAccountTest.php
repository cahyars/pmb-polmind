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

class AdminAccountTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_operator_has_full_access_except_master_data_payment_methods_and_accounts(): void
    {
        $operator = User::factory()->operator()->create(['name' => 'Budi Operator']);

        foreach (['/admin/dashboard', '/admin/applicants', '/admin/documents', '/admin/payments', '/admin/selections', '/admin/re-registrations', '/admin/follow-ups', '/admin/reports/pendaftar', '/admin/integrations'] as $url) {
            $this->actingAs($operator, 'web')->get($url)->assertOk();
        }

        foreach (['/admin/master-data/prodi', '/admin/master-data/penomoran', '/admin/payment-methods', '/admin/users'] as $url) {
            $this->actingAs($operator, 'web')->get($url)->assertForbidden();
        }

        $this->actingAs($operator, 'web')
            ->put('/admin/master-data/numbering', ['nim_format' => '{URUT}', 'nim_digits' => 4, 'nim_scope' => 'prodi'])
            ->assertForbidden();

        $this->actingAs($operator, 'web')
            ->post('/admin/users', ['name' => 'X', 'email' => 'x@example.com', 'role' => 'admin', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertForbidden();

        $this->actingAs($operator, 'web')
            ->get('/admin/dashboard')
            ->assertSee('Operator PMB')
            ->assertDontSee('Kelola Akun')
            ->assertDontSee('href="' . url('/admin/master-data') . '"', false);
    }

    public function test_verification_records_the_logged_in_account_name(): void
    {
        $operator = User::factory()->operator()->create(['name' => 'Budi Operator']);
        $applicant = $this->registerApplicant();
        $invoice = $applicant->registrationInvoice()->first();

        $payment = Payment::create([
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/PMB/2027/00001',
            'amount' => $invoice->total_amount,
            'status' => 'waiting_verification',
        ]);

        $this->actingAs($operator, 'web')->patch('/admin/payments/' . $payment->id . '/accept');

        $this->assertSame('Budi Operator', $payment->refresh()->verified_by_name);
    }

    public function test_admin_manages_accounts_with_safeguards(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Utama']);

        $this->actingAs($admin, 'web')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Admin Utama')
            ->assertSee('Kelola Akun');

        $this->actingAs($admin, 'web')
            ->post('/admin/users', ['name' => 'Sari', 'email' => 'Sari@Polmind.ac.id', 'role' => 'operator', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSessionHasNoErrors();

        $operator = User::where('email', 'sari@polmind.ac.id')->firstOrFail();
        $this->assertSame('operator', $operator->role);

        // Operator baru dapat login.
        auth('web')->logout();
        $this->post('/admin/login', ['email' => 'sari@polmind.ac.id', 'password' => 'rahasia123'])->assertRedirect(route('admin.dashboard'));
        auth('web')->logout();

        // Admin tidak dapat menurunkan peran atau menonaktifkan diri sendiri / admin terakhir.
        $this->actingAs($admin, 'web')
            ->put('/admin/users/' . $admin->id, ['name' => 'Admin Utama', 'email' => $admin->email, 'role' => 'operator'])
            ->assertSessionHasErrors();
        $this->assertTrue($admin->refresh()->isAdmin());

        $this->actingAs($admin, 'web')->patch('/admin/users/' . $admin->id . '/toggle')->assertSessionHasErrors();

        // Nonaktifkan operator: tidak bisa login dan sesi aktifnya diputus.
        $this->actingAs($admin, 'web')->patch('/admin/users/' . $operator->id . '/toggle')->assertSessionHasNoErrors();
        $this->assertFalse($operator->refresh()->is_active);

        auth('web')->logout();
        $this->post('/admin/login', ['email' => 'sari@polmind.ac.id', 'password' => 'rahasia123'])->assertSessionHasErrors();

        $this->actingAs($operator, 'web')->get('/admin/dashboard')->assertRedirect(route('admin.login'));

        // Ganti password tanpa mengubah field lain.
        $this->actingAs($admin, 'web')
            ->put('/admin/users/' . $operator->id, ['name' => 'Sari Dewi', 'email' => 'sari@polmind.ac.id', 'role' => 'operator', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Sari Dewi', $operator->refresh()->name);
        $this->assertTrue(password_verify('baru12345', $operator->password));
    }
}
