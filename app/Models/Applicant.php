<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Applicant extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'pmb_year_id',
        'admission_wave_id',
        'study_program_id',
        'second_study_program_id',
        'class_type_id',

        'registration_number',
        'full_name',
        'email',
        'password',
        'phone',

        'nik',
        'nisn',
        'gender',
        'birth_place',
        'birth_date',
        'religion',

        'source_information',

        'registration_status',
        'document_status',
        'payment_status',
        'selection_status',
        're_registration_status',
        'sync_status',

        'nim',
        'synced_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'synced_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function pmbYear()
    {
        return $this->belongsTo(PmbYear::class);
    }

    public function admissionWave()
    {
        return $this->belongsTo(AdmissionWave::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function secondStudyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'second_study_program_id');
    }

    public function classType()
    {
        return $this->belongsTo(ClassType::class);
    }

    public function address()
    {
        return $this->hasOne(ApplicantAddress::class);
    }

    public function education()
    {
        return $this->hasOne(ApplicantEducation::class);
    }

    public function parentData()
    {
        return $this->hasOne(ApplicantParent::class);
    }
}