<?php

namespace App\Services;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nomor peserta seleksi "{gelombang}.{urutan}", contoh 3.001. Urutan diberikan kepada camaba yang sudah
 * bayar formulir (termasuk formulir gratis), berurutan dari yang paling awal mendaftar.
 * Pendaftar SMK Mitra Industri memakai range terpisah mulai ratusan berikutnya (contoh 3.101) dan
 * biasanya diberi nomor paling akhir. Nomor disimpan agar tetap sama setelah kartu peserta dicetak.
 */
class SelectionNumber
{
    /** Nomor mitra paling kecil, contoh 1.101. */
    public const PARTNER_START = 101;

    public static function format(AdmissionWave $wave, ?int $sequence): ?string
    {
        return $sequence ? RegistrationNumber::waveDigit($wave) . '.' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) : null;
    }

    /**
     * Camaba gelombang ini yang berhak mendapat nomor, urut dari yang paling awal mendaftar.
     * $partner true/false membatasi ke pendaftar mitra/umum; null semuanya.
     */
    public static function eligible(AdmissionWave $wave, ?bool $partner = null): Builder
    {
        $query = Applicant::query()
            ->where('admission_wave_id', $wave->id)
            ->where('payment_status', 'valid')
            ->orderBy('created_at')
            ->orderBy('id');

        return $partner === null ? $query : SelectionGroups::wherePartner($query, $partner);
    }

    /**
     * Camaba bernomor dari satu jenis pendaftar (termasuk yang pembayarannya tidak lagi valid).
     */
    public static function numbered(AdmissionWave $wave, bool $partner): Builder
    {
        return SelectionGroups::wherePartner(
            Applicant::query()->where('admission_wave_id', $wave->id)->whereNotNull('selection_sequence'),
            $partner
        );
    }

    /**
     * Nomor mitra pertama: nomor mitra yang sudah ada bila letaknya di atas nomor umum, atau
     * ratusan berikutnya setelah nomor umum terakhir (45 peserta umum -> 101, 120 -> 201).
     */
    public static function partnerStart(AdmissionWave $wave): int
    {
        $lastGeneral = (int) self::numbered($wave, false)->max('selection_sequence');
        $firstPartner = (int) self::numbered($wave, true)->min('selection_sequence');

        return $firstPartner > $lastGeneral
            ? $firstPartner
            : max(self::PARTNER_START, intdiv($lastGeneral, 100) * 100 + 101);
    }

    /**
     * Urutan berikutnya untuk satu jenis pendaftar, dipakai baris cadangan di daftar hadir.
     */
    public static function nextSequence(AdmissionWave $wave, bool $partner): int
    {
        $last = (int) self::numbered($wave, $partner)->max('selection_sequence');

        return $partner ? max($last + 1, self::partnerStart($wave)) : $last + 1;
    }

    /**
     * Beri nomor lanjutan kepada yang sudah bayar tetapi belum bernomor; nomor yang sudah ada tidak berubah.
     * Nomor yang sudah dipakai jenis pendaftar lain dilewati.
     */
    public static function assignMissing(AdmissionWave $wave, bool $partner = false): int
    {
        return DB::transaction(function () use ($wave, $partner) {
            self::releaseDeleted($wave);
            $taken = Applicant::where('admission_wave_id', $wave->id)->whereNotNull('selection_sequence')
                ->lockForUpdate()->pluck('selection_sequence')->flip()->all();
            $next = $partner
                ? max((int) self::numbered($wave, true)->max('selection_sequence'), self::partnerStart($wave) - 1)
                : (int) self::numbered($wave, false)->max('selection_sequence');
            $assigned = 0;

            foreach (self::eligible($wave, $partner)->whereNull('selection_sequence')->get() as $applicant) {
                do {
                    $next++;
                } while (isset($taken[$next]));

                $applicant->update(['selection_sequence' => $next]);
                $taken[$next] = true;
                $assigned++;
            }

            return $assigned;
        });
    }

    /**
     * Susun ulang nomor satu jenis pendaftar sesuai urutan mendaftar: umum dari 001, mitra dari $start
     * (default partnerStart). Camaba yang tidak lagi memenuhi syarat (contoh pembayarannya dibatalkan)
     * kehilangan nomornya.
     */
    public static function renumber(AdmissionWave $wave, bool $partner = false, ?int $start = null): int
    {
        return DB::transaction(function () use ($wave, $partner, $start) {
            self::releaseDeleted($wave);
            $start = $partner ? ($start ?? self::partnerStart($wave)) : 1;
            $numbered = self::numbered($wave, $partner)->lockForUpdate()->get();
            $eligible = self::eligible($wave, $partner)->get();
            $target = $eligible->values()->mapWithKeys(fn (Applicant $applicant, int $index) => [$applicant->id => $start + $index]);

            // Nomor jenis pendaftar lain tidak boleh tertimpa.
            $clash = Applicant::where('admission_wave_id', $wave->id)
                ->whereNotNull('selection_sequence')
                ->whereKeyNot([...$numbered->modelKeys(), ...$eligible->modelKeys()])
                ->whereIn('selection_sequence', $target->values())
                ->min('selection_sequence');

            if ($clash) {
                throw ValidationException::withMessages(['mulai' => 'Nomor ' . self::format($wave, $clash) . ' sudah dipakai pendaftar '
                    . ($partner ? 'umum. Pilih nomor mulai yang lebih besar.' : 'mitra. Susun ulang nomor mitra ke ratusan berikutnya terlebih dahulu.')]);
            }

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
