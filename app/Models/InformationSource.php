<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pilihan "Sumber Informasi" di form registrasi online dan form pendaftar di kampus.
 * Camaba menyimpan namanya (applicants.source_information), jadi ganti nama ikut memperbarui camaba.
 */
class InformationSource extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Sumber informasi';

    /**
     * Nilai filter untuk camaba yang tidak mengisi sumber informasi.
     */
    public const NOT_FILLED_KEY = 'tidak-diisi';

    public const NOT_FILLED_LABEL = 'Tidak diisi';

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Nama pilihan yang tampil di form registrasi.
     *
     * @return list<string>
     */
    public static function activeNames(): array
    {
        return self::query()->where('is_active', true)->ordered()->pluck('name')->all();
    }

    /**
     * Saring camaba per sumber informasi; NOT_FILLED_KEY untuk yang kosong.
     */
    public static function filterApplicants(Builder $query, string $source): Builder
    {
        $column = $query->qualifyColumn('source_information');

        return $source === self::NOT_FILLED_KEY
            ? $query->where(fn ($q) => $q->whereNull($column)->orWhere($column, ''))
            : $query->where($column, $source);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'source_information', 'name');
    }

    protected function activitySubjectLabel(): ?string
    {
        return $this->name;
    }
}
