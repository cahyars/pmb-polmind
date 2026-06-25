<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'invoice_id',
        'payment_number',
        'transfer_date',
        'sender_name',
        'sender_bank',
        'amount',
        'proof_file_name',
        'proof_file_path',
        'status',
        'admin_note',
        'verified_at',
        'verified_by_name',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'verified_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}