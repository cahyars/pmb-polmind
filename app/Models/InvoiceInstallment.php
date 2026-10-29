<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu angsuran pada jadwal pembayaran invoice daftar ulang.
 */
class InvoiceInstallment extends Model
{
    protected $fillable = [
        'invoice_id',
        'fee_component_id',
        'name',
        'sequence',
        'amount',
        'due_date',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeComponent()
    {
        return $this->belongsTo(FeeComponent::class);
    }
}
