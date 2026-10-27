<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jadwal satu komponen tes pada satu gelombang, beserta PIC (nama dosen).
 */
class SelectionSchedule extends Model
{
    protected $fillable = [
        'admission_wave_id',
        'selection_component_id',
        'test_date',
        'start_time',
        'end_time',
        'location',
        'pic_name',
        'notes',
    ];

    protected $casts = [
        'test_date' => 'date',
    ];

    public function admissionWave()
    {
        return $this->belongsTo(AdmissionWave::class);
    }

    public function component()
    {
        return $this->belongsTo(SelectionComponent::class, 'selection_component_id');
    }

    /**
     * Rentang jam tes, contoh "08.00 - 10.00 WIB".
     */
    public function getTimeRangeAttribute(): ?string
    {
        $format = fn (?string $time) => $time ? str_replace(':', '.', substr($time, 0, 5)) : null;

        $start = $format($this->start_time);
        $end = $format($this->end_time);

        return match (true) {
            $start && $end => "{$start} - {$end} WIB",
            (bool) $start => "Mulai {$start} WIB",
            default => null,
        };
    }
}
