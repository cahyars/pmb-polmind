<?php

namespace App\Services\Reports;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap Pembayaran (template keuangan "Pembayaran Mahasiswa"): satu baris per camaba,
 * pemasukan valid per minggu (dikelompokkan per bulan, minggu dimulai Senin), pemasukan
 * per komponen (formulir dan tiap komponen daftar ulang seperti UKT/SPI), total, dan outstanding.
 * Tanggal pembayaran = tanggal transfer/bayar (bila kosong, tanggal validasi).
 * Formulir yang dibebaskan tampil Rp0 dengan keterangan "Formulir gratis".
 */
class PaymentRecap
{
    public const FORM_COMPONENT = 'Formulir';

    public static function build(ReportFilter $filter): array
    {
        [$from, $until] = [
            isset($filter->values['start_date']) ? Carbon::parse($filter->values['start_date'])->startOfDay() : null,
            isset($filter->values['end_date']) ? Carbon::parse($filter->values['end_date'])->endOfDay() : null,
        ];

        // Tanggal pada filter berlaku untuk tanggal bayar, bukan tanggal daftar.
        $applicantFilter = new ReportFilter(array_diff_key($filter->values, array_flip(['start_date', 'end_date'])));

        $applicants = $applicantFilter->applicants()
            ->with([
                'admissionWave', 'studyProgram', 'acceptedStudyProgram', 'classType',
                'registrationInvoice.payments', 'registrationInvoice.items',
                'reRegistrationInvoice.payments', 'reRegistrationInvoice.items.feeComponent', 'reRegistrationInvoice.installments',
            ])
            ->get();

        $rows = $applicants
            ->map(fn (Applicant $applicant) => self::row($applicant, $from, $until))
            // Camaba dengan formulir gratis tetap muncul walau belum ada pembayaran.
            ->filter(fn (array $row) => $row['payments']->isNotEmpty() || $row['form_waived'])
            ->sortBy([
                fn ($a, $b) => [$a['applicant']->admissionWave?->start_date?->timestamp ?? 0, $a['first_paid_at']->timestamp]
                    <=> [$b['applicant']->admissionWave?->start_date?->timestamp ?? 0, $b['first_paid_at']->timestamp],
            ])
            ->values();

        // Kolom komponen: Formulir, lalu komponen daftar ulang sesuai urutan di invoice (UKT, SPI, ...).
        $components = collect([self::FORM_COMPONENT])
            ->merge($applicants->flatMap(fn (Applicant $applicant) => $applicant->reRegistrationInvoice?->items->sortBy('sort_order')->pluck('name') ?? []))
            ->merge($rows->flatMap(fn ($row) => array_keys($row['components'])))
            ->unique()
            ->values();

        $weeks = self::weeks($rows->flatMap(fn ($row) => $row['payments']->pluck('paid_on')));

        $rows = $rows->map(function (array $row) use ($weeks) {
            $row['weekly'] = $weeks->mapWithKeys(fn ($week) => [
                $week['key'] => (float) $row['payments']->filter(fn ($payment) => self::weekKey($payment['paid_on']) === $week['key'])->sum('amount'),
            ])->all();

            return $row;
        });

        return [
            'weeks' => $weeks,
            'months' => $weeks->groupBy('month_key')->map(fn ($group) => ['label' => $group->first()['month_label'], 'span' => $group->count()])->values(),
            'components' => $components,
            'rows' => $rows,
            'totals' => [
                'weekly' => $weeks->mapWithKeys(fn ($week) => [$week['key'] => (float) $rows->sum(fn ($row) => $row['weekly'][$week['key']])])->all(),
                'components' => $components->mapWithKeys(fn ($name) => [$name => (float) $rows->sum(fn ($row) => $row['components'][$name] ?? 0)])->all(),
                'paid' => (float) $rows->sum('paid'),
                'outstanding' => (float) $rows->sum('outstanding'),
                'applicants' => $rows->count(),
                'waived' => $rows->filter(fn ($row) => $row['note'])->count(),
            ],
        ];
    }

    /**
     * Minggu ke-n dalam bulan, minggu dimulai Senin (1 Maret 2026 hari Minggu = minggu ke-1).
     */
    public static function weekOfMonth(Carbon $date): int
    {
        $firstWeekday = $date->copy()->startOfMonth()->dayOfWeekIso; // 1 = Senin

        return intdiv($date->day + $firstWeekday - 2, 7) + 1;
    }

    private static function weekKey(Carbon $date): string
    {
        return $date->format('Y-m') . '-' . self::weekOfMonth($date);
    }

    /**
     * Semua minggu berturut-turut dari pembayaran pertama sampai terakhir, supaya kolom tidak loncat.
     * Minggu yang melintasi akhir bulan dipecah per bulan, sama seperti kolom template.
     */
    private static function weeks(Collection $dates): Collection
    {
        if ($dates->isEmpty()) {
            return collect();
        }

        $weeks = collect();

        for ($day = $dates->min()->copy(); $day->lte($dates->max()); $day->addDay()) {
            $weeks->put(self::weekKey($day), [
                'key' => self::weekKey($day),
                'month_key' => $day->format('Y-m'),
                'month_label' => $day->translatedFormat('F Y'),
                'week' => self::weekOfMonth($day),
            ]);
        }

        return $weeks->values();
    }

    private static function row(Applicant $applicant, ?Carbon $from, ?Carbon $until): array
    {
        $invoices = collect([$applicant->registrationInvoice, $applicant->reRegistrationInvoice])->filter();

        // Semua pembayaran valid dibagi ke komponen dulu (urut tanggal), baru dipotong sesuai filter tanggal.
        $payments = $invoices
            ->flatMap(fn (Invoice $invoice) => self::allocate($invoice))
            ->filter(fn ($payment) => (! $from || $payment['paid_on']->gte($from)) && (! $until || $payment['paid_on']->lte($until)))
            ->sortBy('paid_on')
            ->values();

        $billed = (float) $invoices->sum(fn (Invoice $invoice) => $invoice->isWaived() ? 0 : $invoice->total_amount);
        $paidAll = (float) $invoices->sum(fn (Invoice $invoice) => $invoice->validPaidAmount());

        $components = [self::FORM_COMPONENT => 0.0];

        $waivedAt = $applicant->registrationInvoice?->waived_at;
        $formWaived = $waivedAt !== null
            && (! $from || $waivedAt->gte($from))
            && (! $until || $waivedAt->lte($until));

        foreach ($payments as $payment) {
            foreach ($payment['components'] as $name => $amount) {
                $components[$name] = ($components[$name] ?? 0) + $amount;
            }
        }

        return [
            'applicant' => $applicant,
            'payments' => $payments,
            'first_paid_at' => $payments->first()['paid_on'] ?? $waivedAt?->copy()->startOfDay() ?? now(),
            'components' => $components,
            'paid' => (float) $payments->sum('amount'),
            // Outstanding = semua tagihan yang sudah terbit dikurangi semua pembayaran valid (tanpa filter tanggal).
            'outstanding' => max(0, $billed - $paidAll),
            'form_waived' => $formWaived,
            'note' => $waivedAt ? 'Formulir gratis' . (filled($applicant->registrationInvoice->waiver_reason) ? ': ' . $applicant->registrationInvoice->waiver_reason : '') : null,
        ];
    }

    /**
     * Pembayaran valid sebuah invoice, masing-masing dengan pembagian ke komponennya. Formulir masuk
     * komponen "Formulir"; daftar ulang mengisi komponen sesuai jadwal cicilan (angsuran ke-1 dulu)
     * atau urutan komponen pada invoice.
     *
     * @return Collection<int, array{payment: Payment, amount: float, paid_on: Carbon, components: array<string, float>}>
     */
    private static function allocate(Invoice $invoice): Collection
    {
        $payments = $invoice->payments
            ->where('status', 'valid')
            ->map(fn (Payment $payment) => [
                'payment' => $payment,
                'amount' => (float) $payment->amount,
                'paid_on' => ($payment->transfer_date ?? $payment->verified_at ?? $payment->created_at)->copy()->startOfDay(),
            ])
            ->sortBy(fn ($payment) => [$payment['paid_on']->timestamp, $payment['payment']->id])
            ->values();

        if ($invoice->type === 'registration') {
            return $payments->map(fn ($payment) => $payment + ['components' => [self::FORM_COMPONENT => $payment['amount']]]);
        }

        $slots = ($invoice->installments->isNotEmpty()
            ? $invoice->installments->sortBy([['sequence', 'asc'], ['due_date', 'asc']])->map(fn ($installment) => [
                'name' => $invoice->items->firstWhere('fee_component_id', $installment->fee_component_id)?->name ?? $installment->name,
                'remaining' => (float) $installment->amount,
            ])
            : $invoice->items->sortBy('sort_order')->map(fn ($item) => ['name' => $item->name, 'remaining' => (float) $item->subtotal])
        )->values()->all();

        return $payments->map(function ($payment) use (&$slots) {
            $left = $payment['amount'];
            $components = [];

            foreach ($slots as &$slot) {
                if ($left <= 0) {
                    break;
                }

                $take = min($slot['remaining'], $left);

                if ($take > 0) {
                    $components[$slot['name']] = ($components[$slot['name']] ?? 0) + $take;
                    $slot['remaining'] -= $take;
                    $left -= $take;
                }
            }
            unset($slot);

            // Kelebihan bayar (jarang) tetap tercatat agar total per komponen sama dengan total bayar.
            if ($left > 0) {
                $components['Lainnya'] = ($components['Lainnya'] ?? 0) + $left;
            }

            return $payment + ['components' => $components];
        });
    }
}
