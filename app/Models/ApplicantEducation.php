<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantEducation extends Model
{
    use HasFactory;

    protected $table = 'applicant_educations';

    protected $fillable = [
        'applicant_id',

        'school_npsn',
        'school_name',
        'school_type',
        'school_status',
        'school_address',

        'major',
        'graduation_year',
        'average_score',

        'is_manual_school',
        'manual_school_note',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'average_score' => 'decimal:2',
        'is_manual_school' => 'boolean',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}