<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** Pilihan jumlah data per halaman pada daftar admin. */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Jumlah data per halaman dari ?per_page=. Pilihan diingat di session per halaman
     * agar tetap terbawa saat filter diterapkan ulang atau admin kembali ke daftar.
     */
    protected function perPage(Request $request, int $default = 10): int
    {
        $key = 'per_page.' . ($request->route()?->getName() ?? $request->path());
        $requested = (int) $request->query('per_page');

        if (in_array($requested, self::PER_PAGE_OPTIONS, true)) {
            $request->session()->put($key, $requested);

            return $requested;
        }

        return $request->session()->get($key, $default);
    }

    /**
     * Nama akun admin/operator yang sedang login, untuk jejak verifikasi.
     */
    protected function actorName(): string
    {
        return auth('web')->user()?->name ?: 'Admin PMB';
    }
}
