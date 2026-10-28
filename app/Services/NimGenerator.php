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
 * NIM dibubuhkan oleh PMB setelah camaba resmi menjadi mahasiswa, yaitu sudah
 * memiliki minimal satu pembayaran daftar ulang yang valid (UKT, SPI, atau keduanya).
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
     * Camaba diterima yang sudah membayar daftar ulang (minimal satu pembayaran valid)
     * tetapi belum memiliki NIM, diurutkan dari tanggal daftar ulang paling awal.
     *
     * @return Collection<int, array{applicant: Applicant, re_registered_at: \Illuminate\Support\Carbon|null}>
     */
    public static function eligible(PmbYear $pmbYear): Collection
    {
        return Applicant::query()
            ->where('pmb_year_id', $pmbYear->id)
            ->where('selection_status', 'diterima')
            ->whereNull('nim')
            ->whereHas('reRegistrationInvoice.payments', fn ($query) => $query->where('status', 'valid'))
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
    public static function generate(PmbYear $pmbYear): array
    {
        $format = self::format();
        $digits = self::digits();
        $scope = self::scope();
        $needsProdiCode = str_contains($format, '{PRODI}');

        $generated = collect();
        $skipped = collect();

        DB::transaction(function () use ($pmbYear, $format, $digits, $scope, $needsProdiCode, &$generated, &$skipped) {
            foreach (self::eligible($pmbYear) as $row) {
                $applicant = $row['applicant'];
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

                $locked->update([
                    'nim' => $nim,
                    'nim_generated_at' => now(),
                ]);

                $generated->push($locked);
            }
        });

        return compact('generated', 'skipped');
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
