<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan langsung keluar dari panel. Dengan parameter "admin",
 * halaman hanya terbuka untuk Admin PMB (bukan operator).
 */
class AdminAccess
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user('web');

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors('Akun Anda dinonaktifkan. Hubungi Admin PMB.');
        }

        if ($role === 'admin' && ! $user?->isAdmin()) {
            abort(403, 'Halaman ini hanya dapat diakses Admin PMB.');
        }

        return $next($request);
    }
}
