<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
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
}