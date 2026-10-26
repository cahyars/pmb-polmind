<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Biodata, berkas, dan tahapan berikutnya baru terbuka setelah
 * pembayaran formulir diverifikasi admin.
 */
class EnsureRegistrationPaid
{
    public function handle(Request $request, Closure $next): Response
    {
        $applicant = Auth::guard('applicant')->user();

        if ($applicant && $applicant->payment_status !== 'valid') {
            return redirect()
                ->route('camaba.dashboard')
                ->with('payment_required', true);
        }

        return $next($request);
    }
}
