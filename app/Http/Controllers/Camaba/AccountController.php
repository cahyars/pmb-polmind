<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Services\LoginInfoMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ganti password akun camaba, terutama untuk mengganti PIN awal (tanggal lahir)
 * dari admin yang mudah ditebak.
 */
class AccountController extends Controller
{
    public function edit()
    {
        $applicant = Auth::guard('applicant')->user();

        return view('camaba.akun', [
            'applicant' => $applicant,
            'usingDefaultPin' => LoginInfoMessage::activePin($applicant) !== null,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $applicant = Auth::guard('applicant')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:applicant'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Password/PIN saat ini tidak sesuai.',
            'password.different' => 'Password baru harus berbeda dari password/PIN saat ini.',
        ], [
            'current_password' => 'password/PIN saat ini',
            'password' => 'password baru',
        ]);

        if ($validated['password'] === LoginInfoMessage::defaultPin($applicant)) {
            return back()->withErrors(['password' => 'Jangan memakai tanggal lahir sebagai password karena mudah ditebak orang lain.']);
        }

        // Kolom password di-hash otomatis oleh cast model; token "ingat saya" lama dibatalkan.
        $applicant->password = $validated['password'];
        $applicant->setRememberToken(null);
        $applicant->save();

        $request->session()->regenerate();

        return back()->with('success', 'Password berhasil diganti. Gunakan password baru ini untuk login berikutnya.');
    }
}
