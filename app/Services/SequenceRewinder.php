<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PmbYear;
use App\Models\RegistrationSequence;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Memundurkan penghitung nomor urut setelah data dihapus permanen, supaya nomor
 * milik data yang dihapus dipakai lagi. Penghitung hanya mundur sampai nomor
 * tertinggi yang masih terpakai, jadi nomor kosong di tengah tidak diisi ulang.
 * Kwitansi tidak ikut karena hanya terbit untuk pembayaran valid, yang tidak bisa dihapus.
 */
class SequenceRewinder
{
    public static function rewind(PmbYear $pmbYear): void
    {
        $year = preg_quote((string) $pmbYear->year, '#');

        $classKeys = RegistrationSequence::where('pmb_year_id', $pmbYear->id)
            ->where('key', 'like', 'kelas-%')
            ->pluck('key');

        foreach ($classKeys as $key) {
            $classCode = preg_quote(substr($key, strlen('kelas-')), '#');

            self::rewindKey($pmbYear->id, $key, fn () => self::highest(
                self::registrationNumbers($pmbYear),
                '#^' . $classCode . '\d' . $year . '(\d+)$#'
            ));
        }

        self::rewindKey($pmbYear->id, 'sementara', fn () => self::highest(
            self::registrationNumbers($pmbYear),
            '#^' . RegistrationNumber::TEMPORARY_PREFIX . $year . '(\d+)$#'
        ));

        self::rewindKey($pmbYear->id, 'invoice-pendaftaran', fn () => self::highest(
            Invoice::where('invoice_number', 'like', 'INV/PMB/' . $pmbYear->year . '/%')->pluck('invoice_number'),
            '#^INV/PMB/' . $year . '/(\d+)$#'
        ));

        foreach (['pembayaran-pmb' => 'PAY/PMB', 'pembayaran-du' => 'PAY/DU'] as $key => $prefix) {
            self::rewindKey($pmbYear->id, $key, fn () => self::highest(
                Payment::where('payment_number', 'like', $prefix . '/' . $pmbYear->year . '/%')->pluck('payment_number'),
                '#^' . preg_quote($prefix, '#') . '/' . $year . '/(\d+)$#'
            ));
        }
    }

    /**
     * Baris penghitung dikunci dulu agar pendaftaran yang sedang berjalan selesai
     * sebelum nomor tertinggi dihitung.
     */
    private static function rewindKey(int $pmbYearId, string $key, Closure $highestInUse): void
    {
        DB::transaction(function () use ($pmbYearId, $key, $highestInUse) {
            $sequence = RegistrationSequence::where('pmb_year_id', $pmbYearId)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                return;
            }

            $highest = $highestInUse();

            if ($sequence->last_number > $highest) {
                $sequence->update(['last_number' => $highest]);
            }
        });
    }

    private static function registrationNumbers(PmbYear $pmbYear)
    {
        return Applicant::withTrashed()->where('pmb_year_id', $pmbYear->id)->pluck('registration_number');
    }

    private static function highest(iterable $numbers, string $pattern): int
    {
        $highest = 0;

        foreach ($numbers as $number) {
            if (preg_match($pattern, (string) $number, $match)) {
                $highest = max($highest, (int) $match[1]);
            }
        }

        return $highest;
    }
}
