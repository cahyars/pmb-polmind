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
        'father_nik',
        'father_birth_date',
        'father_education_id',
        'father_job_id',
        'father_income_id',
        'father_special_need_id',
        'father_phone',
        'mother_name',
        'mother_nik',
        'mother_birth_date',
        'mother_education_id',
        'mother_job_id',
        'mother_income_id',
        'mother_special_need_id',
        'mother_phone',
        'guardian_name',
        'guardian_relation',
        'guardian_nik',
        'guardian_birth_date',
        'guardian_education_id',
        'guardian_job_id',
        'guardian_income_id',
        'guardian_phone',
    ];

    protected $casts = [
        'father_birth_date' => 'date',
        'father_education_id' => 'integer',
        'father_job_id' => 'integer',
        'father_income_id' => 'integer',
        'father_special_need_id' => 'integer',
        'mother_birth_date' => 'date',
        'mother_education_id' => 'integer',
        'mother_job_id' => 'integer',
        'mother_income_id' => 'integer',
        'mother_special_need_id' => 'integer',
        'guardian_birth_date' => 'date',
        'guardian_education_id' => 'integer',
        'guardian_job_id' => 'integer',
        'guardian_income_id' => 'integer',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}