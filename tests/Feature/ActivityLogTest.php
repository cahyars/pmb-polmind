<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdmissionWave;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

    public function test_operator_biodata_edit_is_logged_with_before_and_after(): void
    {
        $applicant = $this->registerApplicant();
        $operator = User::factory()->operator()->create(['name' => 'Pak Operator']);
        $admin = User::factory()->create();

        $this->actingAs($operator, 'web')
            ->put(route('admin.applicants.update', $applicant->registration_number), [
                'full_name' => 'Ammar Maulana', 'nik' => $applicant->nik, 'gender' => 'L', 'birth_place' => 'Batam',
                'birth_date' => '2006-03-14', 'religion_id' => 1, 'citizenship' => 'ID', 'special_need_id' => 0,
                'kps_recipient' => 0, 'almamater_size' => 'L', 'email' => $applicant->email, 'phone' => $applicant->phone,
                'school_region_id' => '316101', 'is_manual_school' => 0, 'school_npsn' => '11001234',
                'school_name' => 'SMK Negeri 1 Batam', 'school_type' => 'SMK', 'school_status' => 'Negeri',
            ])
            ->assertSessionHasNoErrors();

        $log = ActivityLog::where('action', 'applicant.updated')->where('actor_id', $operator->id)->sole();
        $this->assertSame('user', $log->actor_type);
        $this->assertSame('Pak Operator', $log->actor_name);
        $this->assertSame('Operator PMB', $log->actor_role);
        $this->assertSame($applicant->id, $log->applicant_id);
        $this->assertSame(['label' => 'Nama lengkap', 'old' => 'AMMAR', 'new' => 'AMMAR MAULANA'], $log->changes['full_name']);

        // Hanya Admin PMB yang bisa melihat log, baik halaman Log Aktivitas maupun tab Riwayat.
        $this->actingAs($operator, 'web')->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($operator, 'web')->get(route('admin.applicants.show', $applicant->registration_number))->assertDontSee('Riwayat Perubahan');

        $this->actingAs($admin, 'web')
            ->get(route('admin.activity-logs.index', ['user' => $operator->id, 'group' => 'applicant']))
            ->assertOk()
            ->assertSee('Aktivitas Pak Operator')
            ->assertSee('Mengubah data camaba')
            ->assertSee('AMMAR MAULANA');

        $this->actingAs($admin, 'web')
            ->get(route('admin.applicants.show', $applicant->registration_number))
            ->assertSee('Riwayat Perubahan')
            ->assertSee('Pak Operator');

        $this->actingAs($admin, 'web')
            ->get(route('admin.users.index'))
            ->assertSee(route('admin.activity-logs.index', ['user' => $operator->id]));
    }

    public function test_logins_failed_logins_and_login_as_are_logged_without_passwords(): void
    {
        $applicant = $this->registerApplicant();
        $admin = User::factory()->create(['name' => 'Bu Admin', 'email' => 'admin@polmind.test', 'password' => 'rahasia-admin']);

        $this->post(route('admin.login.store'), ['email' => 'admin@polmind.test', 'password' => 'salah']);
        $failed = ActivityLog::where('action', 'auth.failed')->sole();
        $this->assertSame(['guest', $admin->id, 'admin@polmind.test', 'Panel admin'], [$failed->actor_type, $failed->actor_id, $failed->actor_name, $failed->actor_role]);

        $this->post('/login', ['email' => $applicant->email, 'password' => 'rahasia123']);
        $this->assertSame($applicant->id, ActivityLog::where('action', 'auth.login')->where('actor_type', 'applicant')->value('actor_id'));
        $this->post(route('logout'));

        // Login As dan keluar dari Login As tercatat dengan nama admin.
        $this->actingAs($admin, 'web')->post(route('admin.login-as.store', $applicant->registration_number));
        $this->post(route('logout'));

        $this->assertSame('Bu Admin', ActivityLog::where('action', 'auth.login_as')->value('actor_name'));
        $this->assertSame('Bu Admin', ActivityLog::where('action', 'auth.login_as_end')->value('impersonator_name'));

        // Reset PIN oleh admin: password ditandai berubah tanpa menyimpan isinya.
        $applicant->update(['birth_date' => '2006-03-14']);
        $this->actingAs($admin, 'web')->patch(route('admin.applicants.reset-password', $applicant->registration_number));

        $reset = ActivityLog::where('action', 'applicant.updated')->where('actor_id', $admin->id)->latest('id')->first();
        $this->assertSame(['label' => 'Password/PIN', 'old' => '(disembunyikan)', 'new' => '(diubah)'], $reset->changes['password']);
        $this->assertStringNotContainsString('14032006', (string) ActivityLog::pluck('changes')->toJson());
        $this->assertStringNotContainsString('$2y$', (string) ActivityLog::pluck('changes')->toJson());
    }

    public function test_logs_cannot_be_changed_or_deleted(): void
    {
        $log = ActivityLog::create(['actor_type' => 'system', 'actor_name' => 'Sistem', 'action' => 'test', 'description' => 'Uji']);

        $this->assertThrows(fn () => $log->update(['description' => 'Diubah']), LogicException::class);
        $this->assertThrows(fn () => $log->delete(), LogicException::class);
        $this->assertSame('Uji', $log->fresh()->description);
    }
}
