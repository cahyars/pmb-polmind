<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'amount',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public const REGISTRATION_CODE = 'PENDAFTARAN';

    /**
     * Komponen biaya formulir pendaftaran yang aktif.
     */
    public static function registrationFee(): ?self
    {
        return self::where('code', self::REGISTRATION_CODE)
            ->where('is_active', true)
            ->first();
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}