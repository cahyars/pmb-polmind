<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeComponent extends Model
{
    use HasFactory, LogsActivity;

    protected string $activityLabel = 'Komponen biaya';

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

    // Komponen SPI: nominalnya bisa per gelombang, per jalur, dan per kategori prestasi.
    public const SPI_CODE = 'SPI';

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
     * Nominal komponen untuk jalur dan gelombang: nominal khusus jalur, lalu nominal
     * khusus gelombang, lalu nominal default.
     */
    public function amountFor(?string $pathCode, ?int $waveId = null): string
    {
        $pathId = RegistrationPath::forCode($pathCode)?->id;

        return $this->pathAmounts->firstWhere('registration_path_id', $pathId)?->amount
            ?? $this->waveAmounts->firstWhere('admission_wave_id', $waveId)?->amount
            ?? $this->amount;
    }

    /**
     * Nominal tagihan daftar ulang satu camaba beserta nama baris invoice yang menjelaskan asal nominalnya.
     * SPI camaba jalur Prestasi yang sudah diberi kategori memakai nominal kategori tersebut.
     *
     * @return array{amount: string, name: string}
     */
    public function priceFor(Applicant $applicant): array
    {
        $level = $this->code === self::SPI_CODE ? $applicant->achievementLevel : null;

        if ($level) {
            return ['amount' => $level->spi_amount, 'name' => $this->name . ' (' . $level->name . ')'];
        }

        $pathId = RegistrationPath::forCode($applicant->registration_path)?->id;

        if ($pathAmount = $this->pathAmounts->firstWhere('registration_path_id', $pathId)) {
            return ['amount' => $pathAmount->amount, 'name' => $this->name . ' (Jalur ' . $applicant->registration_path_label . ')'];
        }

        if ($waveAmount = $this->waveAmounts->firstWhere('admission_wave_id', $applicant->admission_wave_id)) {
            return ['amount' => $waveAmount->amount, 'name' => $this->name . ' (' . $applicant->admissionWave?->name . ')'];
        }

        return ['amount' => $this->amount, 'name' => $this->name];
    }

    public function pathAmounts()
    {
        return $this->hasMany(FeeComponentPathAmount::class);
    }

    public function waveAmounts()
    {
        return $this->hasMany(FeeComponentWaveAmount::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}