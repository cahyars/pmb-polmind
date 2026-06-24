<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmbYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'year',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function admissionWaves()
    {
        return $this->hasMany(AdmissionWave::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}