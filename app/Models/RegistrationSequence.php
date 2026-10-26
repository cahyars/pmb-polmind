<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Penghitung nomor urut per Tahun PMB yang aman dari pendaftaran bersamaan.
 */
class RegistrationSequence extends Model
{
    protected $fillable = [
        'pmb_year_id',
        'key',
        'last_number',
    ];

    public static function next(int $pmbYearId, string $key): int
    {
        return DB::transaction(function () use ($pmbYearId, $key) {
            self::query()->insertOrIgnore([
                'pmb_year_id' => $pmbYearId,
                'key' => $key,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = self::query()
                ->where('pmb_year_id', $pmbYearId)
                ->where('key', $key)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            return $sequence->last_number;
        });
    }
}
