<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'registration_code',
        'name',
        'description',
        'schedule',
        'scheme',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Poin skema kelas, satu poin per baris isian admin.
     *
     * @return list<string>
     */
    public function getSchemePointsAttribute(): array
    {
        return collect(preg_split('/\R/', (string) $this->scheme))
            ->map(fn (string $line) => trim(ltrim(trim($line), '-•*')))
            ->filter()
            ->values()
            ->all();
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}