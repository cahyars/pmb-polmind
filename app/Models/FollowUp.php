<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'status',
        'priority',
        'contact_method',
        'contacted_at',
        'next_follow_up_date',
        'note',
        'officer_name',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
        'next_follow_up_date' => 'date',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function isHighPriority(): bool
    {
        return $this->priority === 'tinggi';
    }
}