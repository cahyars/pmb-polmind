<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Nominal komponen biaya khusus untuk satu gelombang pendaftaran.
 */
class FeeComponentWaveAmount extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Biaya per gelombang';

    protected $fillable = [
        'fee_component_id',
        'admission_wave_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
