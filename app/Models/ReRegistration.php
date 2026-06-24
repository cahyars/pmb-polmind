<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'invoice_id',
        'status',
        'deadline_date',
        'validated_at',
        'validated_by_name',
        'ready_sync_at',
        'admin_note',
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'validated_at' => 'datetime',
        'ready_sync_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isWaitingVerification(): bool
    {
        return $this->status === 'menunggu_verifikasi';
    }

    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }
}