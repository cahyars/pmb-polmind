<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kategori prestasi jalur Prestasi. Admin menetapkannya per camaba saat seleksi,
 * dan nominal SPI-nya menggantikan nominal SPI gelombang/jalur.
 */
class AchievementLevel extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Kategori prestasi';

    protected $fillable = [
        'name',
        'description',
        'spi_amount',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'spi_amount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
