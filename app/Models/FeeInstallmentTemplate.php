<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris template skema cicilan komponen biaya untuk satu jalur.
 */
class FeeInstallmentTemplate extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Template cicilan';

    protected $fillable = [
        'fee_component_id',
        'registration_path_id',
        'sequence',
        'amount',
        'due_date',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];
}
