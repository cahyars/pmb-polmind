<?php

namespace App\Services;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\PmbYear;
use App\Models\RegistrationSequence;

/**
 * Nomor pendaftar final: [kode kelas][kode gelombang][tahun PMB][urut 4 digit],
 * contoh A120270001. Nomor urut berjalan per kelas dalam satu Tahun PMB.
 * Sebelum kelas dipilih, camaba memakai nomor sementara, contoh TMP20270001.
 */
class RegistrationNumber
{
    public const TEMPORARY_PREFIX = 'TMP';

    public static function temporary(PmbYear $pmbYear): string
    {
        $number = RegistrationSequence::next($pmbYear->id, 'sementara');

        return self::TEMPORARY_PREFIX . $pmbYear->year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public static function isTemporary(?string $registrationNumber): bool
    {
        return ! self::isFinal($registrationNumber);
    }

    /**
     * Nomor final: 1 huruf kelas + 1 digit gelombang + 4 digit tahun + nomor urut.
     */
    public static function isFinal(?string $registrationNumber): bool
    {
        return (bool) preg_match('/^[A-Z][0-9]\d{4}\d{4,}$/', (string) $registrationNumber);
    }

    public static function final(Applicant $applicant, ClassType $classType): string
    {
        $applicant->loadMissing(['pmbYear', 'admissionWave']);

        $classCode = self::classCode($classType);

        $number = RegistrationSequence::next($applicant->pmb_year_id, 'kelas-' . $classCode);

        return $classCode
            . self::waveCode($applicant)
            . $applicant->pmbYear->year
            . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public static function classCode(ClassType $classType): string
    {
        $code = $classType->registration_code
            ?: substr((string) preg_replace('/[^A-Z]/', '', strtoupper(str_replace('REG', '', $classType->code))), -1);

        return $code !== '' ? strtoupper($code) : 'X';
    }

    /**
     * Kode gelombang diambil dari angka pada kode gelombang (GEL1 => 1).
     * Jika kode tanpa angka, dipakai urutan gelombang dalam Tahun PMB.
     */
    public static function waveCode(Applicant $applicant): string
    {
        $wave = $applicant->admissionWave;

        if (! $wave) {
            return '0';
        }

        $digits = preg_replace('/\D/', '', (string) $wave->code);

        if ($digits !== '') {
            return substr($digits, -1);
        }

        $position = AdmissionWave::query()
            ->where('pmb_year_id', $wave->pmb_year_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->pluck('id')
            ->search($wave->id);

        return $position === false ? '0' : (string) (($position + 1) % 10);
    }
}
