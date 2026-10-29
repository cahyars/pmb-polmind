<?php

namespace App\Services;

use App\Models\Applicant;
use Illuminate\Support\Facades\Hash;

/**
 * Pesan WhatsApp "Informasi Pendaftar" berisi akses masuk camaba yang didaftarkan admin.
 * PIN = password awal (tanggal lahir ddmmyyyy), hanya dikirim selama camaba belum menggantinya.
 */
class LoginInfoMessage
{
    public static function defaultPin(Applicant $applicant): ?string
    {
        return $applicant->birth_date?->format('dmY');
    }

    /**
     * PIN yang masih berlaku, atau null bila camaba sudah memakai password sendiri.
     */
    public static function activePin(Applicant $applicant): ?string
    {
        $pin = self::defaultPin($applicant);

        return $pin && Hash::check($pin, $applicant->password) ? $pin : null;
    }

    public static function text(Applicant $applicant, string $pin): string
    {
        $applicant->loadMissing('pmbYear');

        return implode("\n", [
            'Informasi Pendaftar PMB Politeknik Mitra Industri TA ' . $applicant->pmbYear?->year,
            '',
            'Halo, selamat datang di Politeknik Mitra Industri 👋',
            'Mohon segera melengkapi profil pendaftar pada portal berikut:',
            '🔗 ' . route('login'),
            '',
            '*Akses Masuk*',
            '> Nama Pendaftar : ' . $applicant->full_name,
            '> ID Pendaftar : *' . $applicant->registration_number . '*',
            '> PIN : *' . $pin . '*',
            '',
            '👉 Lengkapi data diri Anda agar proses seleksi dan administrasi berjalan lancar.',
            '',
            'Terima kasih atas kerja samanya 🙏',
            '*Politeknik Mitra Industri*',
        ]);
    }

    /**
     * Nomor WhatsApp format internasional (62...), atau null bila nomor HP kosong.
     */
    public static function whatsappNumber(Applicant $applicant): ?string
    {
        $number = preg_replace('/\D/', '', (string) $applicant->phone);

        if ($number === '') {
            return null;
        }

        return match (true) {
            str_starts_with($number, '0') => '62' . substr($number, 1),
            str_starts_with($number, '8') => '62' . $number,
            default => $number,
        };
    }

    public static function whatsappUrl(Applicant $applicant, string $text): ?string
    {
        $number = self::whatsappNumber($applicant);

        return $number ? 'https://wa.me/' . $number . '?text=' . rawurlencode($text) : null;
    }
}
