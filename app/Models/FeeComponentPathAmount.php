<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nominal komponen biaya khusus untuk satu jalur pendaftaran.
 */
class FeeComponentPathAmount extends Model
{
    protected $fillable = [
        'fee_component_id',
        'registration_path_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
