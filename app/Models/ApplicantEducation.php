<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ApplicantEducation extends Model
{
    use HasFactory;

    public const SCHOOL_TYPES = ['SMA', 'SMK', 'MA', 'MAK', 'Paket C', 'Lainnya'];

    protected $table = 'applicant_educations';

    protected $fillable = [
        'applicant_id',

        'school_npsn',
        'school_name',
        'school_type',
        'school_status',
        'school_region_id',
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

    /**
     * Aturan validasi sekolah asal, dipakai di registrasi dan wizard biodata.
     */
    public static function schoolRules(bool $manual): array
    {
        return [
            'school_region_id' => ['required', Rule::exists('ref_regions', 'id')->where('level', RefRegion::LEVEL_DISTRICT)],
            'is_manual_school' => ['required', 'boolean'],
            'school_npsn' => [$manual ? 'nullable' : 'required', 'digits:8'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', Rule::in(self::SCHOOL_TYPES)],
            'school_status' => ['nullable', 'in:Negeri,Swasta'],
        ];
    }

    public static function schoolMessages(): array
    {
        return [
            'school_region_id.required' => 'Pilih provinsi, kabupaten/kota, dan kecamatan lokasi sekolah.',
            'school_npsn.required' => 'Pilih sekolah dari daftar, atau centang "Sekolah saya tidak ada di daftar".',
            'school_name.required' => 'Pilih sekolah asal dari daftar, atau isi manual.',
            'school_type.required' => 'Pilih jenis sekolah asal.',
        ];
    }

    /**
     * Kolom sekolah yang disimpan dari hasil validasi schoolRules().
     */
    public static function schoolAttributes(array $validated): array
    {
        return [
            'school_region_id' => $validated['school_region_id'],
            'is_manual_school' => (bool) $validated['is_manual_school'],
            'school_npsn' => filled($validated['school_npsn'] ?? null) ? $validated['school_npsn'] : null,
            'school_name' => mb_strtoupper($validated['school_name']),
            'school_type' => $validated['school_type'],
            'school_status' => filled($validated['school_status'] ?? null) ? $validated['school_status'] : null,
        ];
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}