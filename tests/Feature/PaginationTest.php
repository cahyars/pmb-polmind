<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_per_page_is_selectable_and_remembered_per_list(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Utama']);
        User::factory()->operator()->count(29)->create();
        User::factory()->operator()->create(['is_active' => false]);

        $response = $this->actingAs($admin, 'web')->get('/admin/users')->assertOk();
        $this->assertSame(10, $response->viewData('users')->perPage());
        $this->assertSame(31, $response->viewData('users')->total());
        $response->assertSee('per halaman')
            ->assertSee('29 akun aktif');

        $response = $this->actingAs($admin, 'web')->get('/admin/users?per_page=25')->assertOk();
        $this->assertSame(25, $response->viewData('users')->perPage());
        $this->assertStringContainsString('per_page=25', $response->viewData('users')->nextPageUrl());

        // Tanpa ?per_page= (mis. setelah menerapkan filter) pilihan terakhir tetap dipakai.
        $this->assertSame(25, $this->actingAs($admin, 'web')->get('/admin/users')->viewData('users')->perPage());

        // Nilai di luar pilihan diabaikan.
        $this->assertSame(25, $this->actingAs($admin, 'web')->get('/admin/users?per_page=7')->viewData('users')->perPage());

        // Pilihan tiap daftar terpisah: Log Aktivitas tetap memakai bawaannya.
        $this->assertSame(50, $this->actingAs($admin, 'web')->get('/admin/users/log-aktivitas')->viewData('logs')->perPage());
        $this->assertSame(100, $this->actingAs($admin, 'web')->get('/admin/applicants?per_page=100')->viewData('applicants')->perPage());
    }

    public function test_per_page_selector_is_hidden_for_short_lists(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')->get('/admin/users')
            ->assertOk()
            ->assertDontSee('per halaman');
    }
}
