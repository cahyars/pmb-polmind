<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'degree',
        'quota',
        'description',
        'is_active',
    ];

    protected $casts = [
        'quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }

    public function secondChoiceApplicants()
    {
        return $this->hasMany(Applicant::class, 'second_study_program_id');
    }
}