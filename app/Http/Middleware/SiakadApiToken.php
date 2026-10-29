<?php

namespace App\Http\Middleware;

use App\Services\SiakadApiAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akses API untuk SIAKAD: header "Authorization: Bearer <token>". Token dan daftar IP
 * dikelola Admin PMB di menu Integrasi SIAKAD > Token API. Tanpa token, API mati.
 */
class SiakadApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiakadApiAccess::enabled()) {
            return response()->json(['message' => 'API SIAKAD belum diaktifkan.'], 503);
        }

        if (! SiakadApiAccess::matches($request->bearerToken())) {
            return response()->json(['message' => 'Token API tidak valid.'], 401);
        }

        $allowedIps = SiakadApiAccess::allowedIps();

        if ($allowedIps && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json(['message' => 'Alamat IP tidak diizinkan.'], 403);
        }

        return $next($request);
    }
}
