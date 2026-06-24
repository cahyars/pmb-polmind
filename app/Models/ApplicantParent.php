<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantParent extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',

        'father_name',
        'father_job',
        'father_phone',

        'mother_name',
        'mother_job',
        'mother_phone',

        'guardian_name',
        'guardian_job',
        'guardian_phone',
        'guardian_relation',

        'parent_income_range',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}