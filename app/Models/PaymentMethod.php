<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tujuan pembayaran yang ditampilkan ke camaba (rekening kampus, VA, e-wallet, dll).
 */
class PaymentMethod extends Model
{
    public const TYPES = [
        'bank_transfer' => 'Transfer Bank',
        'virtual_account' => 'Virtual Account',
        'ewallet' => 'E-Wallet',
        'qris' => 'QRIS',
        'cash' => 'Tunai di Kampus',
    ];

    protected $fillable = [
        'type',
        'provider',
        'account_number',
        'account_name',
        'instructions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Label singkat, contoh: "Bank BCA - 1234567890".
     */
    public function getLabelAttribute(): string
    {
        return trim($this->provider . ($this->account_number ? ' - ' . $this->account_number : ''));
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
