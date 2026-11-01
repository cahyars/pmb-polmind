<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantAddress extends Model
{
    use HasFactory, LogsActivity;

    protected string $activityLabel = 'Alamat camaba';

    protected $fillable = [
        'applicant_id',
        'address',
        'hamlet',
        'region_id',
        'residence_type_id',

        'province_code',
        'province_name',

        'regency_code',
        'regency_name',

        'district_code',
        'district_name',

        'village_code',
        'village_name',

        'postal_code',
        'rt',
        'rw',
    ];

    protected $casts = [
        'residence_type_id' => 'integer',
    ];

    public function region()
    {
        return $this->belongsTo(RefRegion::class, 'region_id');
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}