<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Jalur pendaftaran (Umum, Prestasi, dst.) yang dikelola admin di Master Data.
 * Camaba menyimpan kodenya di applicants.registration_path.
 */
class RegistrationPath extends Model
{
    /**
     * Warna badge dan angka statistik, bergiliran mengikuti urutan jalur.
     */
    private const COLORS = [
        ['badge' => 'bg-blue-50 text-polmind-blue', 'text' => 'text-polmind-blue'],
        ['badge' => 'bg-green-100 text-green-700', 'text' => 'text-green-700'],
        ['badge' => 'bg-purple-100 text-purple-700', 'text' => 'text-purple-700'],
        ['badge' => 'bg-amber-100 text-amber-700', 'text' => 'text-amber-700'],
        ['badge' => 'bg-rose-100 text-rose-700', 'text' => 'text-rose-700'],
        ['badge' => 'bg-teal-100 text-teal-700', 'text' => 'text-teal-700'],
    ];

    private static ?Collection $cache = null;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    /**
     * Semua jalur sesuai urutan, di-cache per request dan dikunci dengan kode.
     */
    public static function ordered(): Collection
    {
        return self::$cache ??= self::query()->orderBy('sort_order')->orderBy('id')->get()->keyBy('code');
    }

    public static function active(): Collection
    {
        return self::ordered()->where('is_active', true);
    }

    public static function forCode(?string $code): ?self
    {
        return self::ordered()->get((string) $code);
    }

    public static function labelFor(?string $code): string
    {
        return self::forCode($code)?->name ?? ($code ? Str::headline($code) : '-');
    }

    public static function badgeClass(?string $code): string
    {
        return self::color($code)['badge'] ?? 'bg-slate-100 text-slate-600';
    }

    public static function textClass(?string $code): string
    {
        return self::color($code)['text'] ?? 'text-slate-700';
    }

    public static function flushCache(): void
    {
        self::$cache = null;
    }

    /**
     * Kode unik dari nama jalur, contoh "Kerja Sama Industri" => kerja_sama_industri.
     */
    public static function makeCode(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 40, '') ?: 'jalur';
        $code = $base;

        for ($i = 2; self::query()->where('code', $code)->exists(); $i++) {
            $code = $base . '_' . $i;
        }

        return $code;
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'registration_path', 'code');
    }

    private static function color(?string $code): ?array
    {
        $position = self::ordered()->keys()->search((string) $code);

        return $position === false ? null : self::COLORS[$position % count(self::COLORS)];
    }
}
