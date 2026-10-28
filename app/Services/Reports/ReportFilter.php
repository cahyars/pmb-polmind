<?php

namespace App\Services\Reports;

use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filter bersama untuk semua laporan. Tahun PMB sudah dibatasi oleh
 * middleware SelectAdminPmbYear, jadi di sini hanya filter tambahan.
 */
class ReportFilter
{
    public const KEYS = [
        'start_date',
        'end_date',
        'admission_wave',
        'study_program',
        'class_type',
        'registration_path',
    ];

    public function __construct(public readonly array $values = [])
    {
    }

    public static function fromRequest(Request $request): self
    {
        $values = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'admission_wave' => ['nullable', 'integer'],
            'study_program' => ['nullable', 'integer'],
            'class_type' => ['nullable', 'integer'],
            'registration_path' => ['nullable', 'string', 'in:' . implode(',', array_keys(Applicant::REGISTRATION_PATHS))],
        ]);

        return new self(array_filter($values, fn ($value) => filled($value)));
    }

    public function applicants(): Builder
    {
        return Applicant::query()
            ->when($this->values['start_date'] ?? null, fn ($query, $date) => $query->whereDate('applicants.created_at', '>=', $date))
            ->when($this->values['end_date'] ?? null, fn ($query, $date) => $query->whereDate('applicants.created_at', '<=', $date))
            ->when($this->values['admission_wave'] ?? null, fn ($query, $id) => $query->where('applicants.admission_wave_id', $id))
            ->when($this->values['study_program'] ?? null, fn ($query, $id) => $query->where('applicants.study_program_id', $id))
            ->when($this->values['class_type'] ?? null, fn ($query, $id) => $query->where('applicants.class_type_id', $id))
            ->when($this->values['registration_path'] ?? null, fn ($query, $path) => $query->where('applicants.registration_path', $path));
    }
}
