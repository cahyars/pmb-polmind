<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Nilai camaba untuk satu komponen tes seleksi. Hanya untuk internal kampus.
 */
class SelectionScore extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Nilai seleksi';

    protected $fillable = [
        'selection_id',
        'selection_component_id',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function selection()
    {
        return $this->belongsTo(Selection::class);
    }

    public function component()
    {
        return $this->belongsTo(SelectionComponent::class, 'selection_component_id');
    }

    protected function activityParentApplicantId(): ?int
    {
        return $this->selection?->applicant_id;
    }

    protected function activitySubjectLabel(): ?string
    {
        return $this->component?->name;
    }
}
