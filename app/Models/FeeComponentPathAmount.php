<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Nominal komponen biaya khusus untuk satu jalur pendaftaran.
 */
class FeeComponentPathAmount extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Biaya per jalur';

    protected $fillable = [
        'fee_component_id',
        'registration_path_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
