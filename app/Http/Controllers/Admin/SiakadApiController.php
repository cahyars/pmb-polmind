<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiakadApiAccess;
use Illuminate\Http\Request;

/**
 * Integrasi SIAKAD > Token API (khusus Admin PMB): buat, ganti, cabut token, dan atur IP SIAKAD.
 */
class SiakadApiController extends Controller
{
    public function index()
    {
        return view('admin.integrations.api', [
            'enabled' => SiakadApiAccess::enabled(),
            'token' => SiakadApiAccess::details(),
            'allowedIps' => SiakadApiAccess::allowedIps(),
            'baseUrl' => url('/api/siakad'),
        ]);
    }

    public function generate()
    {
        $replaced = SiakadApiAccess::enabled();

        $token = SiakadApiAccess::generate($this->actorName());

        return redirect()->route('admin.integrations.api')
            ->with('generated_token', $token)
            ->with('success', $replaced
                ? 'Token baru dibuat. Token lama sudah tidak berlaku, kirim token baru ke tim SIAKAD.'
                : 'Token dibuat dan API SIAKAD aktif.');
    }

    public function revoke()
    {
        SiakadApiAccess::revoke();

        return back()->with('success', 'Token dicabut. API SIAKAD nonaktif sampai token baru dibuat.');
    }

    public function updateIps(Request $request)
    {
        $ips = array_values(array_filter(preg_split('/[\s,]+/', (string) $request->input('allowed_ips'))));

        $request->merge(['ips' => $ips])->validate([
            'ips' => ['array', 'max:20'],
            'ips.*' => ['ip'],
        ], [
            'ips.*.ip' => ':input bukan alamat IP yang valid.',
            'ips.max' => 'Maksimal 20 alamat IP.',
        ]);

        SiakadApiAccess::setAllowedIps($ips);

        return back()->with('success', $ips
            ? 'API SIAKAD hanya menerima request dari: ' . implode(', ', array_unique($ips)) . '.'
            : 'Pembatasan IP dimatikan. API menerima request dari IP mana pun yang membawa token benar.');
    }
}
