<?php

namespace App\Services\Reports;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Report Format Mitra: tiga sheet laporan sore (docs/report pmb.xlsx) yang dulu diisi manual,
 * yaitu Rekap Akumulasi Pembayaran Registrasi, Data Pengisian Formulir, dan grafiknya.
 *
 * Satu baris = satu hari rekap per gelombang, ditutup pukul 18.00 WIB seperti Rekap Harian
 * WhatsApp (DailyRecap), jadi angkanya sama dengan teks yang dikirim bersamaan.
 * Baris yang ditampilkan default minggu berjalan (Minggu - Sabtu); baris Jumlah/Total tetap
 * akumulasi sejak awal gelombang, sama seperti tangkapan layar spreadsheet yang dipotong.
 */
class MitraFormatReport
{
    public static function build(?PmbYear $pmbYear, AdmissionWave $wave, Carbon $date, bool $wholeWave = false): array
    {
        $date = $date->copy()->startOfDay();
        $firstDay = $wave->start_date->copy()->startOfDay();

        $applicants = Applicant::query()
            ->with(['education', 'registrationInvoice.payments'])
            ->where('admission_wave_id', $wave->id)
            ->where('created_at', '<', $date->copy()->setTime(DailyRecap::CUTOFF_HOUR, 0))
            ->get();

        // Pendaftar malam (>= 18.00) masuk hari berikutnya; pendaftar sebelum gelombang dibuka masuk hari pertama.
        $dayOf = function (Applicant $applicant) use ($firstDay): string {
            $day = $applicant->created_at->copy();

            if ($day->hour >= DailyRecap::CUTOFF_HOUR) {
                $day->addDay();
            }

            return $day->startOfDay()->max($firstDay)->toDateString();
        };

        $byDay = $applicants->groupBy($dayOf);

        // Hari terakhir: hari H, tetapi tidak melewati akhir gelombang kecuali masih ada pendaftar susulan.
        $lastDay = $date->copy();
        if ($wave->end_date && $wave->end_date->lt($lastDay)) {
            $lastDay = $wave->end_date->copy()->max(Carbon::parse($byDay->keys()->max() ?? $wave->end_date));
        }

        $programs = self::programs($applicants);

        $rows = collect();
        for ($day = $firstDay->copy(), $no = 1; $day->lte($lastDay); $day->addDay(), $no++) {
            $dayApplicants = $byDay->get($day->toDateString(), collect());
            $rows->push(['no' => $no, 'date' => $day->copy()] + self::counts($dayApplicants, $programs));
        }

        $weekStart = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $verified = $applicants->where('payment_status', 'valid')->count();

        return [
            'pmbYear' => $pmbYear,
            'academicYear' => $pmbYear ? $pmbYear->year . '/' . ((int) $pmbYear->year + 1) : null,
            'wave' => $wave,
            'date' => $date,
            'weekStart' => $weekStart,
            'wholeWave' => $wholeWave,
            'programs' => $programs,
            'rows' => $wholeWave ? $rows : $rows->filter(fn (array $row) => $row['date']->gte($weekStart))->values(),
            'totals' => self::counts($applicants, $programs),
            'verified' => $verified,
            'unverified' => $applicants->count() - $verified,
        ];
    }

    /**
     * Kode prodi aktif urut kode (kolom sheet Pengisian Formulir), ditambah prodi nonaktif yang masih punya pendaftar.
     *
     * @return array<int, string> id => kode
     */
    private static function programs(Collection $applicants): array
    {
        $used = $applicants->pluck('study_program_id')->filter()->unique();

        return StudyProgram::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $used))
            ->orderBy('code')
            ->pluck('code', 'id')
            ->all();
    }

    private static function counts(Collection $applicants, array $programs): array
    {
        $partner = $applicants->filter(fn (Applicant $applicant) => DailyRecap::isPartnerSchool($applicant->education?->school_name));
        $general = $applicants->diffKeys($partner);
        $offline = fn (Collection $group) => $group->where('registered_via', 'kampus')->count();

        return [
            'partner_offline' => $offline($partner),
            'partner_online' => $partner->count() - $offline($partner),
            'general_offline' => $offline($general),
            'general_online' => $general->count() - $offline($general),
            'total' => $applicants->count(),
            // Hanya pendaftar umum yang dikalkulasi: pendaftar mitra gratis, cukup dicatat jumlahnya.
            // Nilainya uang formulir yang benar-benar masuk (pembayaran valid).
            'money' => (float) $general->sum(fn (Applicant $applicant) => $applicant->registrationInvoice?->payments->where('status', 'valid')->sum('amount') ?? 0),
            'programs' => collect($programs)->map(fn ($code, $id) => $applicants->where('study_program_id', $id)->count())->all(),
        ];
    }
}
