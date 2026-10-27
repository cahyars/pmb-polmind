<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionWave extends Model
{
    use HasFactory;

    protected $fillable = [
        'pmb_year_id',
        'code',
        'name',
        'start_date',
        'end_date',
        'announcement_date',
        're_registration_start_date',
        're_registration_end_date',
        'registration_fee',
        'is_active',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'announcement_date' => 'date',
        're_registration_start_date' => 'date',
        're_registration_end_date' => 'date',
        'registration_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Gelombang yang sedang menerima pendaftaran pada Tahun PMB aktif:
     * gelombang yang diaktifkan admin, atau jika tidak ada, gelombang
     * yang jadwalnya mencakup hari ini dan belum ditutup.
     */
    public static function current(?PmbYear $pmbYear = null): ?self
    {
        $pmbYear ??= PmbYear::where('is_active', true)->first();

        if (! $pmbYear) {
            return null;
        }

        $waves = self::query()->where('pmb_year_id', $pmbYear->id);

        $today = now()->toDateString();

        return (clone $waves)->where('is_active', true)->first()
            ?? (clone $waves)
                ->where('status', '!=', 'closed')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->orderBy('start_date')
                ->first();
    }

    /**
     * Status tampilan untuk camaba berdasarkan status admin dan tanggal hari ini.
     *
     * @return 'open'|'upcoming'|'closed'
     */
    public function publicStatus(?self $currentWave = null): string
    {
        $today = now()->startOfDay();

        return match (true) {
            $currentWave?->is($this) => 'open',
            $this->status === 'closed', $this->end_date?->lt($today) => 'closed',
            default => 'upcoming',
        };
    }

    /**
     * Jadwal ringkas untuk camaba (landing page & registrasi), tanpa rincian per tes.
     * Tanggal seleksi diambil dari rentang jadwal tes gelombang ini.
     *
     * @return list<array{label: string, value: string}>
     */
    public function timeline(): array
    {
        $testDates = $this->selectionSchedules->pluck('test_date')->filter()->sort()->values();

        return [
            ['label' => 'Pendaftaran', 'value' => self::formatDateRange($this->start_date, $this->end_date)],
            ['label' => 'Seleksi', 'value' => self::formatDateRange($testDates->first(), $testDates->last())],
            ['label' => 'Pengumuman Seleksi', 'value' => self::formatDateRange($this->announcement_date, null)],
            ['label' => 'Daftar Ulang', 'value' => self::formatDateRange($this->re_registration_start_date, $this->re_registration_end_date)],
        ];
    }

    /**
     * Contoh: "30 Jan 2027", "2 – 12 Feb 2027", "1 Feb – 2 Apr 2027", "2 Nov 2026 – 29 Jan 2027".
     */
    public static function formatDateRange($start, $end): string
    {
        $end = $end && $start && $end->isSameDay($start) ? null : $end;

        return match (true) {
            ! $start && ! $end => 'Menyusul',
            ! $end => $start->translatedFormat('j M Y'),
            ! $start => $end->translatedFormat('j M Y'),
            $start->isSameMonth($end) => $start->format('j') . ' – ' . $end->translatedFormat('j M Y'),
            $start->isSameYear($end) => $start->translatedFormat('j M') . ' – ' . $end->translatedFormat('j M Y'),
            default => $start->translatedFormat('j M Y') . ' – ' . $end->translatedFormat('j M Y'),
        };
    }

    public function pmbYear()
    {
        return $this->belongsTo(PmbYear::class);
    }

    public function selectionSchedules()
    {
        return $this->hasMany(SelectionSchedule::class)->orderBy('test_date')->orderBy('start_time');
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}