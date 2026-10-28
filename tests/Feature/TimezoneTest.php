<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_uses_wib_and_migration_shifts_old_utc_timestamps(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));

        DB::table('users')->insert([
            'name' => 'Admin Lama',
            'email' => 'lama@example.com',
            'password' => 'x',
            'email_verified_at' => null,
            'created_at' => '2026-10-26 20:30:00',
            'updated_at' => '2026-10-26 20:30:00',
        ]);

        $migration = require database_path('migrations/2026_10_28_000004_shift_timestamps_to_asia_jakarta.php');
        $migration->up();

        $user = DB::table('users')->where('email', 'lama@example.com')->first();

        $this->assertSame('2026-10-27 03:30:00', $user->created_at);
        $this->assertNull($user->email_verified_at);

        $migration->down();

        $this->assertSame('2026-10-26 20:30:00', DB::table('users')->where('email', 'lama@example.com')->value('created_at'));
    }
}
