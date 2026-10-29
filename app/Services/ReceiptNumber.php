<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RegistrationSequence;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Nomor kwitansi untuk setiap pembayaran yang sudah divalidasi admin.
 * Format default KW/{JENIS}/{TAHUN}/{URUT}, contoh KW/PMB/2027/00001.
 * Nomor urut berjalan per Tahun PMB dan formatnya dapat diatur admin PMB.
 */
class ReceiptNumber
{
    public const DEFAULT_FORMAT = 'KW/{JENIS}/{TAHUN}/{URUT}';

    public const DEFAULT_DIGITS = 5;

    public const DEFAULT_SIGNER_TITLE = 'Bagian Keuangan PMB';

    public const TOKENS = [
        '{JENIS}' => 'Jenis pembayaran: PMB (formulir) atau DU (daftar ulang)',
        '{TAHUN}' => 'Tahun PMB 4 digit, contoh 2027',
        '{BULAN}' => 'Bulan validasi 2 digit, contoh 04',
        '{ROMAWI}' => 'Bulan validasi angka Romawi, contoh IV',
        '{URUT}' => 'Nomor urut kwitansi dalam satu Tahun PMB (wajib ada)',
    ];

    private const ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public static function format(): string
    {
        return (string) Setting::get('receipt.format', self::DEFAULT_FORMAT);
    }

    public static function digits(): int
    {
        return (int) Setting::get('receipt.digits', self::DEFAULT_DIGITS);
    }

    /**
     * Penandatangan kwitansi yang diatur admin PMB. Nama kosong berarti
     * tanda tangan ditulis tangan di atas garis.
     */
    public static function signer(): array
    {
        return [
            'name' => Setting::get('receipt.signer_name'),
            'title' => Setting::get('receipt.signer_title', self::DEFAULT_SIGNER_TITLE),
            'signature' => self::imageData(Setting::get('receipt.signature_path')),
            'stamp' => self::imageData(Setting::get('receipt.stamp_path')),
        ];
    }

    /**
     * Gambar tanda tangan/stempel disimpan privat, lalu disematkan ke halaman cetak sebagai data URI.
     */
    private static function imageData(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $disk = Storage::disk('local');

        return 'data:' . $disk->mimeType($path) . ';base64,' . base64_encode($disk->get($path));
    }

    public static function render(string $format, int $digits, array $values): string
    {
        $month = (int) $values['bulan'];

        return strtr($format, [
            '{JENIS}' => $values['jenis'],
            '{TAHUN}' => (string) $values['tahun'],
            '{BULAN}' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            '{ROMAWI}' => self::ROMAN_MONTHS[$month - 1] ?? '',
            '{URUT}' => str_pad((string) $values['urut'], $digits, '0', STR_PAD_LEFT),
        ]);
    }

    public static function example(?int $year = null): string
    {
        return self::render(self::format(), self::digits(), [
            'jenis' => 'PMB',
            'tahun' => $year ?? now()->year,
            'bulan' => now()->month,
            'urut' => 1,
        ]);
    }

    /**
     * Beri nomor kwitansi pada pembayaran valid yang belum memilikinya.
     */
    public static function assign(Payment $payment): ?string
    {
        if ($payment->status !== 'valid') {
            return null;
        }

        if ($payment->receipt_number) {
            return $payment->receipt_number;
        }

        return DB::transaction(function () use ($payment) {
            $locked = Payment::withoutGlobalScopes()->whereKey($payment->id)->lockForUpdate()->first();

            if ($locked->receipt_number) {
                return $payment->receipt_number = $locked->receipt_number;
            }

            $locked->load(['invoice', 'applicant.pmbYear']);
            $pmbYear = $locked->applicant->pmbYear;
            $validatedAt = $locked->verified_at ?? now();

            do {
                $number = self::render(self::format(), self::digits(), [
                    'jenis' => $locked->invoice?->type === 're_registration' ? 'DU' : 'PMB',
                    'tahun' => $pmbYear->year,
                    'bulan' => $validatedAt->month,
                    'urut' => RegistrationSequence::next($pmbYear->id, 'kwitansi'),
                ]);
            } while (Payment::withoutGlobalScopes()->where('receipt_number', $number)->exists());

            $locked->update(['receipt_number' => $number]);

            return $payment->receipt_number = $number;
        });
    }
}
