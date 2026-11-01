<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Brosur PMB (PDF atau gambar) yang disimpan privat, dilihat di halaman preview publik,
 * dan dapat diunduh dari sana.
 */
class Brochure extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Brosur';

    protected array $activityIgnore = ['view_count', 'download_count'];

    public const FOLDER = 'brochures';

    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public const MAX_KB = 10240;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'sort_order',
        'is_active',
        'view_count',
        'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function isPdf(): bool
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    public function getSizeLabelAttribute(): string
    {
        return $this->file_size >= 1048576
            ? number_format($this->file_size / 1048576, 1, ',', '.') . ' MB'
            : max(1, (int) round($this->file_size / 1024)) . ' KB';
    }
}
