<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Selection extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'final_score',
        'status',
        'note',
        'decided_at',
        'decided_by_name',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Nilai per komponen tes. Hanya untuk internal kampus, tidak ditampilkan ke camaba.
     */
    public function scores()
    {
        return $this->hasMany(SelectionScore::class);
    }

    /**
     * Rata-rata berbobot dari komponen yang sudah dinilai; null jika belum ada nilai.
     */
    public static function weightedScore(iterable $scores): ?float
    {
        $totalWeight = 0;
        $total = 0;

        foreach ($scores as $score) {
            $weight = max((int) $score->component?->weight, 0);
            $totalWeight += $weight;
            $total += (float) $score->score * $weight;
        }

        return $totalWeight > 0 ? round($total / $totalWeight, 2) : null;
    }

    public function isAccepted(): bool
    {
        return $this->status === 'diterima';
    }

    public function isReserve(): bool
    {
        return $this->status === 'cadangan';
    }

    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }
}