<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan langsung keluar dari panel. Dengan parameter "admin",
 * halaman hanya terbuka untuk Admin PMB (bukan operator); dengan "spmb", untuk
 * Admin PMB dan Ketua SPMB. Ketua SPMB hanya dapat membuka menu Seleksi.
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

        if ($user?->isSelectionChair() && ! $request->routeIs(...User::SELECTION_CHAIR_ROUTES)) {
            if ($request->routeIs('admin.dashboard')) {
                return redirect()->route('admin.spmb.groups.index');
            }

            abort(403, 'Akun Ketua SPMB hanya dapat membuka menu Seleksi.');
        }

        if ($role === 'admin' && ! $user?->isAdmin()) {
            abort(403, 'Halaman ini hanya dapat diakses Admin PMB.');
        }

        if ($role === 'spmb' && ! $user?->isAdmin() && ! $user?->isSelectionChair()) {
            abort(403, 'Halaman ini hanya dapat diakses Admin PMB dan Ketua SPMB.');
        }

        return $next($request);
    }
}
