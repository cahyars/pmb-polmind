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
        'is_installable',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_installable' => 'boolean',
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

    /**
     * Nominal untuk jalur camaba; tanpa nominal khusus jalur dipakai nominal default.
     */
    public function amountFor(?string $pathCode): string
    {
        $pathId = RegistrationPath::forCode($pathCode)?->id;

        return $this->pathAmounts->firstWhere('registration_path_id', $pathId)?->amount ?? $this->amount;
    }

    public function pathAmounts()
    {
        return $this->hasMany(FeeComponentPathAmount::class);
    }

    public function installmentTemplates()
    {
        return $this->hasMany(FeeInstallmentTemplate::class)->orderBy('sequence');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}