<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'document_type_id',
        'file_name',
        'file_path',
        'file_extension',
        'file_size_kb',
        'status',
        'admin_note',
        'uploaded_at',
        'verified_at',
        'verified_by_name',
    ];

    protected $casts = [
        'file_size_kb' => 'integer',
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'diterima';
    }

    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }

    public function isWaitingVerification(): bool
    {
        return $this->status === 'menunggu_verifikasi';
    }
}