<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akses API untuk SIAKAD: header "Authorization: Bearer <SIAKAD_API_TOKEN>".
 * API mati selama token belum diisi di .env. Daftar IP bersifat opsional.
 */
class SiakadApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.siakad.token');

        if ($token === '') {
            return response()->json(['message' => 'API SIAKAD belum diaktifkan.'], 503);
        }

        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Token API tidak valid.'], 401);
        }

        $allowedIps = array_filter(array_map('trim', explode(',', (string) config('services.siakad.allowed_ips'))));

        if ($allowedIps && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json(['message' => 'Alamat IP tidak diizinkan.'], 403);
        }

        return $next($request);
    }
}
