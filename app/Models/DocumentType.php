<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory, LogsActivity;

    protected string $activityLabel = 'Jenis berkas';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_required',
        'allowed_extensions',
        'max_size_kb',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'allowed_extensions' => 'array',
        'max_size_kb' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function applicantDocuments()
    {
        return $this->hasMany(ApplicantDocument::class);
    }
}