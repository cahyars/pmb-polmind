<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan key-value yang dikelola admin PMB (format NIM, nomor kwitansi, dll).
 */
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    private static ?array $cache = null;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        self::$cache ??= Cache::remember(
            self::CACHE_KEY,
            now()->addDays(7),
            fn () => self::query()->pluck('value', 'key')->all()
        );

        $value = self::$cache[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::flushCache();
    }

    public static function flushCache(): void
    {
        self::$cache = null;
        Cache::forget(self::CACHE_KEY);
    }
}
