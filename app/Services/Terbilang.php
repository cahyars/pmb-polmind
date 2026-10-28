<?php

namespace App\Services;

/**
 * Nominal rupiah dalam kata bahasa Indonesia untuk kwitansi,
 * contoh 1.250.000 => "satu juta dua ratus lima puluh ribu".
 */
class Terbilang
{
    private const WORDS = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function rupiah(float|int|string $amount): string
    {
        return ucfirst(trim(self::words((int) round((float) $amount)))) . ' rupiah';
    }

    private static function words(int $number): string
    {
        return match (true) {
            $number === 0 => 'nol',
            $number < 12 => self::WORDS[$number],
            $number < 20 => self::WORDS[$number - 10] . ' belas',
            $number < 100 => self::WORDS[intdiv($number, 10)] . ' puluh' . self::rest($number % 10),
            $number < 200 => 'seratus' . self::rest($number - 100),
            $number < 1000 => self::WORDS[intdiv($number, 100)] . ' ratus' . self::rest($number % 100),
            $number < 2000 => 'seribu' . self::rest($number - 1000),
            $number < 1000000 => self::words(intdiv($number, 1000)) . ' ribu' . self::rest($number % 1000),
            $number < 1000000000 => self::words(intdiv($number, 1000000)) . ' juta' . self::rest($number % 1000000),
            default => self::words(intdiv($number, 1000000000)) . ' miliar' . self::rest($number % 1000000000),
        };
    }

    private static function rest(int $number): string
    {
        return $number > 0 ? ' ' . self::words($number) : '';
    }
}
