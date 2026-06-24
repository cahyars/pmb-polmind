<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionWave extends Model
{
    use HasFactory;

    protected $fillable = [
        'pmb_year_id',
        'code',
        'name',
        'start_date',
        'end_date',
        'registration_fee',
        'is_active',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function pmbYear()
    {
        return $this->belongsTo(PmbYear::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}