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
        'registration_fee',
        'is_active',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function pmbYear()
    {
        return $this->belongsTo(PmbYear::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}