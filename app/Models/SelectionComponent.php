<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Komponen tes seleksi PMB, contoh: Tes Tertulis, Wawancara.
 */
class SelectionComponent extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Komponen seleksi';

    protected $fillable = [
        'code',
        'name',
        'description',
        'weight',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(SelectionSchedule::class);
    }
}
