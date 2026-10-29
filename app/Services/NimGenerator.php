<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\PmbYear;
use App\Models\RegistrationSequence;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * NIM dibubuhkan oleh PMB setelah daftar ulang camaba valid (lunas, atau angsuran
 * ke-1 bagi camaba dengan cicilan), lalu data camaba siap ditarik SIAKAD.
 * Format default {PRODI}{TAHUN}{URUT}, contoh A20270001. Nomor urut mengikuti
 * tanggal daftar ulang pertama sampai terakhir dan dapat diatur admin PMB.
 */
class NimGenerator
{
    public const DEFAULT_FORMAT = '{PRODI}{TAHUN}{URUT}';

    public const DEFAULT_DIGITS = 4;

    public const SCOPES = [
        'prodi' => 'Per program studi (A20270001, B20270001, ...)',
        'tahun' => 'Berlanjut lintas prodi dalam satu tahun (A20270001, B20270002, ...)',
    ];

    public const TOKENS = [
        '{PRODI}' => 'Kode prodi pada NIM (Master Data Program Studi)',
        '{KELAS}' => 'Kode kelas pada nomor pendaftar, contoh A atau B',
        '{TAHUN}' => 'Tahun akademik 4 digit, contoh 2027',
        '{TAHUN2}' => 'Tahun akademik 2 digit, contoh 27',
        '{URUT}' => 'Nomor urut mahasiswa (wajib ada)',
    ];

    public static function format(): string
    {
        return (string) Setting::get('nim.format', self::DEFAULT_FORMAT);
    }

    public static function digits(): int
    {
        return (int) Setting::get('nim.digits', self::DEFAULT_DIGITS);
    }

    public static function scope(): string
    {
        $scope = (string) Setting::get('nim.scope', 'prodi');

        return array_key_exists($scope, self::SCOPES) ? $scope : 'prodi';
    }

    public static function render(string $format, int $digits, array $values): string
    {
        return strtr($format, [
            '{PRODI}' => $values['prodi'] ?? '',
            '{KELAS}' => $values['kelas'] ?? '',
            '{TAHUN}' => (string) $values['tahun'],
            '{TAHUN2}' => substr((string) $values['tahun'], -2),
            '{URUT}' => str_pad((string) $values['urut'], $digits, '0', STR_PAD_LEFT),
        ]);
    }

    public static function example(?string $format = null, ?int $digits = null, ?int $year = null): string
    {
        return self::render($format ?? self::format(), $digits ?? self::digits(), [
            'prodi' => 'A',
            'kelas' => 'A',
            'tahun' => $year ?? now()->year,
            'urut' => 1,
        ]);
    }

    /**
     * Camaba diterima yang daftar ulangnya sudah valid tetapi belum memiliki NIM,
     * diurutkan dari tanggal daftar ulang paling awal.
     *
     * @return Collection<int, array{applicant: Applicant, re_registered_at: \Illuminate\Support\Carbon|null}>
     */
    public static function eligible(PmbYear $pmbYear): Collection
    {
        return Applicant::query()
            ->where('pmb_year_id', $pmbYear->id)
            ->where('selection_status', 'diterima')
            ->whereNull('nim')
            ->where('re_registration_status', 'daftar_ulang_valid')
            ->with([
                'studyProgram',
                'acceptedStudyProgram',
                'classType',
                'reRegistrationInvoice.payments' => fn ($query) => $query->where('status', 'valid'),
            ])
            ->get()
            ->map(function (Applicant $applicant) {
                $firstPayment = self::firstValidPayment($applicant);

                return [
                    'applicant' => $applicant,
                    're_registered_at' => $firstPayment?->transfer_date ?? $firstPayment?->verified_at,
                    'sort_key' => [
                        ($firstPayment?->transfer_date ?? $firstPayment?->verified_at)?->format('Y-m-d') ?? '9999-12-31',
                        $firstPayment?->created_at?->format('Y-m-d H:i:s') ?? '',
                        $applicant->id,
                    ],
                ];
            })
            ->sortBy('sort_key')
            ->values();
    }

    /**
     * Bubuhkan NIM ke seluruh camaba yang memenuhi syarat sesuai urutan daftar ulang.
     *
     * @return array{generated: Collection<int, Applicant>, skipped: Collection<int, Applicant>}
     */
    public static function generate(PmbYear $pmbYear, ?int $onlyApplicantId = null): array
    {
        $format = self::format();
        $digits = self::digits();
        $scope = self::scope();
        $needsProdiCode = str_contains($format, '{PRODI}');

        $generated = collect();
        $skipped = collect();

        DB::transaction(function () use ($pmbYear, $format, $digits, $scope, $needsProdiCode, $onlyApplicantId, &$generated, &$skipped) {
            foreach (self::eligible($pmbYear) as $row) {
                $applicant = $row['applicant'];

                if ($onlyApplicantId && $applicant->id !== $onlyApplicantId) {
                    continue;
                }
                $prodiCode = strtoupper((string) $applicant->enrolled_study_program?->nim_code);

                if ($needsProdiCode && $prodiCode === '') {
                    $skipped->push($applicant);

                    continue;
                }

                // Kunci baris agar dua admin yang menekan tombol bersamaan tidak memberi NIM ganda.
                $locked = Applicant::query()
                    ->whereKey($applicant->id)
                    ->whereNull('nim')
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    continue;
                }

                $sequenceKey = $scope === 'prodi' ? 'nim-' . ($prodiCode ?: $applicant->enrolled_study_program?->id) : 'nim';

                do {
                    $nim = self::render($format, $digits, [
                        'prodi' => $prodiCode,
                        'kelas' => $applicant->classType ? RegistrationNumber::classCode($applicant->classType) : '',
                        'tahun' => $pmbYear->year,
                        'urut' => RegistrationSequence::next($pmbYear->id, $sequenceKey),
                    ]);
                } while (Applicant::withoutGlobalScopes()->withTrashed()->where('nim', $nim)->exists());

                self::assign($locked, $nim);

                $generated->push($locked);
            }
        });

        return compact('generated', 'skipped');
    }

    /**
     * Simpan NIM (otomatis atau manual) lalu tandai data siap ditarik SIAKAD.
     */
    public static function assign(Applicant $applicant, string $nim): void
    {
        $applicant->update([
            'nim' => $nim,
            'nim_generated_at' => now(),
            'sync_status' => in_array($applicant->sync_status, ['proses_sinkron', 'sudah_sinkron', 'gagal_sinkron'], true)
                ? $applicant->sync_status
                : 'siap_sinkron',
        ]);

        $applicant->reRegistration()->update(['ready_sync_at' => now()]);
    }

    /**
     * Lepas NIM yang belum ditarik SIAKAD; data kembali belum siap sinkron.
     */
    public static function release(Applicant $applicant): void
    {
        $applicant->update([
            'nim' => null,
            'nim_generated_at' => null,
            'sync_status' => 'belum_siap',
        ]);

        $applicant->reRegistration()->update(['ready_sync_at' => null]);

        if ($applicant->pmbYear) {
            SequenceRewinder::rewind($applicant->pmbYear);
        }
    }

    /**
     * Pola regex NIM untuk satu kunci nomor urut, dipakai memundurkan nomor urut.
     * Hanya mengenali NIM yang mengikuti format saat ini.
     */
    public static function pattern(string $sequenceKey, int $year): string
    {
        $prodi = str_starts_with($sequenceKey, 'nim-') ? preg_quote(substr($sequenceKey, 4), '#') : '[A-Z0-9]*';

        $regex = strtr(preg_quote(self::format(), '#'), [
            preg_quote('{PRODI}', '#') => $prodi,
            preg_quote('{KELAS}', '#') => '[A-Z]?',
            preg_quote('{TAHUN}', '#') => (string) $year,
            preg_quote('{TAHUN2}', '#') => substr((string) $year, -2),
            preg_quote('{URUT}', '#') => '(\d+)',
        ]);

        return '#^' . $regex . '$#';
    }

    private static function firstValidPayment(Applicant $applicant): ?Payment
    {
        return $applicant->reRegistrationInvoice?->payments
            ->sortBy(fn (Payment $payment) => [
                ($payment->transfer_date ?? $payment->verified_at)?->format('Y-m-d') ?? '9999-12-31',
                $payment->created_at?->format('Y-m-d H:i:s') ?? '',
                $payment->id,
            ])
            ->first();
    }
}
