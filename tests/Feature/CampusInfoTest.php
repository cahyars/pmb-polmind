<?php

namespace Tests\Feature;

use App\Models\RefOption;
use App\Models\User;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusInfoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);
    }

    public function test_landing_page_shows_campus_address_and_admin_login_shortcut(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tower Vasanta Innopark P1-P2, Kawasan Industri MM2100 Jl. Kalimantan Blok CB-2')
            ->assertSee('Kab. Bekasi, Jawa Barat 17530')
            ->assertSee('aria-label="Login Admin PMB"', false)
            ->assertSee('href="' . route('admin.login') . '"', false);
    }

    public function test_dashboard_target_counts_new_students(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Target 250 mahasiswa baru (daftar ulang valid)');
    }
}
