<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'fee_component_id',
        'name',
        'amount',
        'quantity',
        'subtotal',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'sort_order' => 'integer',
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