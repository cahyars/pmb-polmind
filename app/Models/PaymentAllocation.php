<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bagian satu pembayaran daftar ulang untuk satu komponen biaya (mis. UKT atau SPI).
 */
class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'fee_component_id',
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function feeComponent()
    {
        return $this->belongsTo(FeeComponent::class);
    }
}
