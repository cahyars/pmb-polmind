<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Selection extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'test_score',
        'interview_score',
        'final_score',
        'status',
        'note',
        'decided_at',
        'decided_by_name',
    ];

    protected $casts = [
        'test_score' => 'decimal:2',
        'interview_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'diterima';
    }

    public function isReserve(): bool
    {
        return $this->status === 'cadangan';
    }

    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }
}