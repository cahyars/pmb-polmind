<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Nama akun admin/operator yang sedang login, untuk jejak verifikasi.
     */
    protected function actorName(): string
    {
        return auth('web')->user()?->name ?: 'Admin PMB';
    }
}
