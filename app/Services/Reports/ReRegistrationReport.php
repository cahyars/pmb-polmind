<?php

namespace App\Services\Reports;

use App\Models\Applicant;
use App\Models\StudyProgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan Daftar Ulang: camaba diterima, pembayaran daftar ulang (lunas/cicilan),
 * NIM yang sudah terbit, keterisian kuota prodi, dan tren daftar ulang per bulan.
 */
class ReRegistrationReport
{
    public const STATUSES = [
        'lunas' => 'Lunas',
        'cicilan' => 'Cicilan',
        'belum_bayar' => 'Belum Bayar',
    ];

    public static function build(ReportFilter $filter): array
    {
        $rows = $filter->applicants()
            ->where('applicants.selection_status', 'diterima')
            ->with(['studyProgram', 'reRegistrationInvoice.payments'])
            ->get()
            ->map(fn (Applicant $applicant) => self::row($applicant));

        $paid = $rows->where('paid', '>', 0);

        return [
            'summary' => [
                'accepted' => $rows->count(),
                're_registered' => $paid->count(),
                'paid_off' => $rows->where('status', 'lunas')->count(),
                'installment' => $rows->where('status', 'cicilan')->count(),
                'unpaid' => $rows->where('status', 'belum_bayar')->count(),
                'nim' => $rows->filter(fn ($row) => filled($row['applicant']->nim))->count(),
                'synced' => $rows->filter(fn ($row) => $row['applicant']->sync_status === 'sudah_sinkron')->count(),
                'total_paid' => $rows->sum('paid'),
                'total_remaining' => $rows->sum('remaining'),
            ],
            'programs' => self::programs($rows),
            'monthly' => MonthlySeries::fill($paid->countBy(fn ($row) => $row['re_registered_at']->format('Y-m'))),
            'rows' => $rows
                ->sortBy([
                    fn ($a, $b) => strcmp((string) $a['applicant']->studyProgram?->code, (string) $b['applicant']->studyProgram?->code),
                    fn ($a, $b) => [$a['re_registered_at']?->timestamp ?? PHP_INT_MAX, $a['applicant']->id] <=> [$b['re_registered_at']?->timestamp ?? PHP_INT_MAX, $b['applicant']->id],
                ])
                ->values(),
        ];
    }

    private static function row(Applicant $applicant): array
    {
        $invoice = $applicant->reRegistrationInvoice;
        $validPayments = $invoice?->payments->where('status', 'valid') ?? collect();

        $total = (float) ($invoice?->total_amount ?? 0);
        $paid = (float) $validPayments->sum('amount');

        $firstPayment = $validPayments
            ->sortBy(fn ($payment) => [($payment->transfer_date ?? $payment->verified_at)?->format('Y-m-d') ?? '9999-12-31', $payment->id])
            ->first();

        return [
            'applicant' => $applicant,
            'invoice_number' => $invoice?->invoice_number,
            'total' => $total,
            'paid' => $paid,
            'remaining' => max(0, $total - $paid),
            'status' => match (true) {
                $paid > 0 && $paid >= $total => 'lunas',
                $paid > 0 => 'cicilan',
                default => 'belum_bayar',
            },
            're_registered_at' => $firstPayment ? Carbon::parse($firstPayment->transfer_date ?? $firstPayment->verified_at) : null,
        ];
    }

    private static function programs(Collection $rows): Collection
    {
        return StudyProgram::orderBy('code')->get()
            ->map(function ($program) use ($rows) {
                $group = $rows->filter(fn ($row) => $row['applicant']->study_program_id === $program->id);

                if (! $program->is_active && $group->isEmpty()) {
                    return null;
                }

                $reRegistered = $group->where('paid', '>', 0)->count();

                return [
                    'code' => $program->code,
                    'name' => $program->degree . ' ' . $program->name,
                    'quota' => $program->quota,
                    'accepted' => $group->count(),
                    're_registered' => $reRegistered,
                    'paid_off' => $group->where('status', 'lunas')->count(),
                    'installment' => $group->where('status', 'cicilan')->count(),
                    'unpaid' => $group->where('status', 'belum_bayar')->count(),
                    'nim' => $group->filter(fn ($row) => filled($row['applicant']->nim))->count(),
                    'total_paid' => $group->sum('paid'),
                    'quota_percent' => $program->quota > 0 ? (int) round($reRegistered / $program->quota * 100) : null,
                ];
            })
            ->filter()
            ->values();
    }

}
