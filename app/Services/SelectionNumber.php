<?php

namespace App\Services;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Nomor peserta seleksi "{gelombang}.{urutan}", contoh 3.001. Urutan diberikan kepada camaba yang sudah
 * bayar formulir (termasuk formulir gratis), berurutan dari yang paling awal mendaftar.
 * Nomor disimpan agar tetap sama setelah kartu peserta dicetak.
 */
class SelectionNumber
{
    public static function format(AdmissionWave $wave, ?int $sequence): ?string
    {
        return $sequence ? RegistrationNumber::waveDigit($wave) . '.' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) : null;
    }

    /**
     * Camaba gelombang ini yang berhak mendapat nomor, urut dari yang paling awal mendaftar.
     */
    public static function eligible(AdmissionWave $wave): Builder
    {
        return Applicant::query()
            ->where('admission_wave_id', $wave->id)
            ->where('payment_status', 'valid')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Beri nomor lanjutan kepada yang sudah bayar tetapi belum bernomor; nomor yang sudah ada tidak berubah.
     */
    public static function assignMissing(AdmissionWave $wave): int
    {
        return DB::transaction(function () use ($wave) {
            self::releaseDeleted($wave);
            $next = (int) Applicant::where('admission_wave_id', $wave->id)->lockForUpdate()->max('selection_sequence');
            $assigned = 0;

            foreach (self::eligible($wave)->whereNull('selection_sequence')->get() as $applicant) {
                $applicant->update(['selection_sequence' => ++$next]);
                $assigned++;
            }

            return $assigned;
        });
    }

    /**
     * Susun ulang seluruh nomor gelombang dari 001 sesuai urutan mendaftar. Camaba yang tidak lagi
     * memenuhi syarat (contoh pembayarannya dibatalkan) kehilangan nomornya.
     */
    public static function renumber(AdmissionWave $wave): int
    {
        return DB::transaction(function () use ($wave) {
            self::releaseDeleted($wave);
            $numbered = Applicant::where('admission_wave_id', $wave->id)->whereNotNull('selection_sequence')->lockForUpdate()->get();
            $eligible = self::eligible($wave)->get();
            $target = $eligible->values()->mapWithKeys(fn (Applicant $applicant, int $index) => [$applicant->id => $index + 1]);

            // Hanya nomor yang berubah yang disentuh. Dikosongkan dulu tanpa event agar tidak bentrok dengan
            // indeks unik, lalu diisi per model sehingga Log Aktivitas mencatat nomor lama -> nomor baru.
            $changing = $numbered->filter(fn (Applicant $applicant) => ($target[$applicant->id] ?? null) !== $applicant->selection_sequence);
            Applicant::whereKey($changing->modelKeys())->update(['selection_sequence' => null]);

            foreach ($changing->filter(fn (Applicant $applicant) => ! isset($target[$applicant->id])) as $applicant) {
                $applicant->update(['selection_sequence' => null]);
            }

            $current = $numbered->keyBy('id');
            foreach ($eligible as $applicant) {
                $model = $current[$applicant->id] ?? $applicant;

                if ($model->selection_sequence !== $target[$applicant->id]) {
                    $model->update(['selection_sequence' => $target[$applicant->id]]);
                }
            }

            return $eligible->count();
        });
    }

    /**
     * Camaba yang sudah dihapus tidak memerlukan nomor; nomornya dilepas agar tidak bentrok dengan indeks unik.
     */
    private static function releaseDeleted(AdmissionWave $wave): void
    {
        Applicant::onlyTrashed()->where('admission_wave_id', $wave->id)->whereNotNull('selection_sequence')->update(['selection_sequence' => null]);
    }
}
