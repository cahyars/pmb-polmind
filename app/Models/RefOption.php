<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Referensi SIAKAD (kode Neo Feeder PDDikti) yang dipakai biodata camaba.
 */
class RefOption extends Model
{
    public const AGAMA = 'agama';
    public const PENDIDIKAN = 'pendidikan';
    public const PEKERJAAN = 'pekerjaan';
    public const PENGHASILAN = 'penghasilan';
    public const JENIS_TINGGAL = 'jenis_tinggal';
    public const ALAT_TRANSPORTASI = 'alat_transportasi';
    public const KEBUTUHAN_KHUSUS = 'kebutuhan_khusus';
    public const KEWARGANEGARAAN = 'kewarganegaraan';

    protected $fillable = [
        'group',
        'code',
        'name',
        'sort_order',
    ];

    /**
     * @var array<string, Collection<string, string>>
     */
    private static array $cache = [];

    /**
     * Daftar pilihan satu grup: [code => name], urut sesuai sort_order.
     *
     * @return Collection<string, string>
     */
    public static function optionsFor(string $group): Collection
    {
        return self::$cache[$group] ??= self::query()
            ->where('group', $group)
            ->orderBy('sort_order')
            ->pluck('name', 'code');
    }

    /**
     * @return list<string>
     */
    public static function codesFor(string $group): array
    {
        return self::optionsFor($group)->keys()->map(fn ($code) => (string) $code)->all();
    }

    public static function nameOf(string $group, int|string|null $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::optionsFor($group)->get((string) $code);
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
