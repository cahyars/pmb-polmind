<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun admin awal (config/pmb.php). Atur PMB_ADMIN_EMAIL dan PMB_ADMIN_PASSWORD di .env;
 * jika password kosong, password acak dibuat dan ditampilkan sekali di terminal.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('pmb.admin.email');

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Akun admin {$email} sudah ada, tidak diubah.");

            return;
        }

        $password = config('pmb.admin.password') ?: Str::password(16, symbols: false);

        User::create([
            'name' => config('pmb.admin.name'),
            'email' => $email,
            'password' => $password,
        ]);

        $this->command?->warn("Akun admin dibuat: {$email}");

        if (! config('pmb.admin.password')) {
            $this->command?->warn("Password awal: {$password} (catat dan segera ganti)");
        }
    }
}
