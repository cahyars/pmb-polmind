<?php

namespace App\Services;

/**
 * Tautan chat WhatsApp ke admin PMB (nomor diatur di config/pmb.php).
 */
class AdminWhatsApp
{
    public const DEFAULT_MESSAGE = 'Halo Admin PMB Polmind, saya ingin bertanya tentang pendaftaran mahasiswa baru.';

    public static function url(?string $message = self::DEFAULT_MESSAGE): string
    {
        $url = 'https://wa.me/' . config('pmb.admin_whatsapp.number');

        return filled($message) ? $url . '?text=' . rawurlencode($message) : $url;
    }

    public static function display(): string
    {
        return config('pmb.admin_whatsapp.display');
    }
}
