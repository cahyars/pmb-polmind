<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Kelompok peserta tes seleksi dalam satu gelombang, contoh "Kelompok 1" atau "Kelompok Mitra".
 * Tes yang diikuti diatur per kelompok; kelompok mitra biasanya hanya MCU.
 */
class SelectionGroup extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Kelompok tes';

    protected $fillable = [
        'admission_wave_id',
        'name',
        'is_partner',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_partner' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function admissionWave()
    {
        return $this->belongsTo(AdmissionWave::class);
    }

    public function components()
    {
        return $this->belongsToMany(SelectionComponent::class)->orderBy('sort_order')->orderBy('name');
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
