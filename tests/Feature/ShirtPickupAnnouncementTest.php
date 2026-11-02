<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\User;
use App\Services\AutoAnnouncements;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class ShirtPickupAnnouncementTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);
    }

    public function test_verified_payment_sends_shirt_pickup_announcement(): void
    {
        $applicant = $this->registerApplicant();

        $this->assertSame(0, $applicant->announcements()->count());

        $payment = $this->waitingPayment($applicant);

        $this->actingAs(User::factory()->create(), 'web')
            ->patch('/admin/payments/' . $payment->id . '/accept')
            ->assertSessionHasNoErrors();

        $this->assertSame('valid', $applicant->refresh()->payment_status);

        $announcement = $applicant->announcements()->firstOrFail();
        $this->assertSame(AutoAnnouncements::SHIRT_PICKUP, $announcement->auto_key);
        $this->assertNull($announcement->pivot->read_at);

        $this->actingAs($applicant, 'applicant')
            ->get(route('camaba.announcements.show', $announcement->id))
            ->assertOk()
            ->assertSee('Pengambilan Kaos PMB')
            ->assertSee('Lantai P1 Apartemen Vasanta Innopark')
            ->assertSee('Senin-Jumat, pukul 08.00 - 16.00 WIB')
            ->assertSee('Hubungi Admin PMB');
    }

    public function test_waived_fee_also_receives_and_announcement_is_shared_once_per_year(): void
    {
        $first = $this->registerApplicant(['email' => 'satu@example.com']);
        $second = $this->registerApplicant(['email' => 'dua@example.com']);
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->patch('/admin/applicants/' . $first->registration_number . '/waive-registration-fee', ['waiver_reason' => 'Perintah pimpinan'])
            ->assertSessionHasNoErrors();

        $second->update(['payment_status' => 'valid']);

        // Status berubah lalu valid lagi tidak menggandakan pengumuman.
        $second->update(['payment_status' => 'menunggu_verifikasi']);
        $second->update(['payment_status' => 'valid']);

        $this->assertSame(1, Announcement::count());

        $announcement = Announcement::firstOrFail();
        $this->assertSame(2, $announcement->recipient_count);
        $this->assertSame(1, $second->announcements()->count());
        $this->assertSame(1, $first->announcements()->count());

        $this->actingAs($admin, 'web')
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Pengambilan Kaos PMB')
            ->assertSee('Otomatis')
            ->assertSee('Sistem');
    }

    public function test_command_sends_to_applicants_already_valid(): void
    {
        $valid = $this->registerApplicant(['email' => 'valid@example.com']);
        $peminat = $this->registerApplicant(['email' => 'peminat@example.com']);

        // Sudah valid sebelum fitur berlaku (tanpa event).
        $valid->updateQuietly(['payment_status' => 'valid']);

        $this->artisan('pmb:pengumuman-kaos --dry-run')->assertSuccessful();
        $this->assertSame(0, Announcement::count());

        $this->artisan('pmb:pengumuman-kaos')->assertSuccessful();
        $this->artisan('pmb:pengumuman-kaos')->assertSuccessful();

        $this->assertSame(1, $valid->announcements()->count());
        $this->assertSame(0, $peminat->announcements()->count());
        $this->assertSame(1, Announcement::firstOrFail()->recipient_count);
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
