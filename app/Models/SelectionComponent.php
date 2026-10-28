<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Komponen tes seleksi PMB, contoh: Tes Tertulis, Wawancara.
 */
class SelectionComponent extends Model
{
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
