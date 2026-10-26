<?php

namespace App\Services;

use App\Models\RefRegion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daftar SMA/SMK per kabupaten/kota dari API Sekolah Indonesia (data Dapodik).
 * Kode wilayahnya sama dengan id_wilayah SIAKAD, sehingga bisa difilter per kecamatan.
 * Madrasah (MA) dan Paket C tidak tersedia di sumber ini, camaba mengisinya manual.
 */
class SchoolDirectory
{
    private const BASE_URL = 'https://api-sekolah-indonesia.vercel.app/sekolah';

    private const LEVELS = ['sma', 'smk'];

    private const CACHE_DAYS = 7;

    /**
     * @return Collection<int, array{npsn: string, name: string, type: string, status: string, district_id: string, address: ?string}>|null
     *         null jika sumber data tidak dapat dihubungi.
     */
    public static function forRegency(string $regencyId): ?Collection
    {
        if (! RefRegion::where('id', $regencyId)->where('level', RefRegion::LEVEL_REGENCY)->exists()) {
            return collect();
        }

        $cacheKey = 'schools.regency.' . $regencyId;

        if ($cached = Cache::get($cacheKey)) {
            return collect($cached);
        }

        try {
            $schools = collect(self::LEVELS)->flatMap(function (string $level) use ($regencyId) {
                $response = Http::timeout(8)
                    ->retry(2, 300, throw: false)
                    ->acceptJson()
                    ->get(self::BASE_URL . '/' . $level, [
                        'kab_kota' => $regencyId,
                        'page' => 1,
                        'perPage' => 1000,
                    ]);

                if (! $response->successful()) {
                    throw new \RuntimeException('HTTP ' . $response->status());
                }

                // API mengembalikan status "failed" jika wilayah tidak punya sekolah.
                return collect($response->json('dataSekolah') ?? [])->map(fn (array $school) => [
                    'npsn' => trim((string) ($school['npsn'] ?? '')),
                    'name' => trim((string) ($school['sekolah'] ?? '')),
                    'type' => strtoupper(trim((string) ($school['bentuk'] ?? strtoupper($level)))),
                    'status' => ($school['status'] ?? '') === 'N' ? 'Negeri' : 'Swasta',
                    'district_id' => trim((string) ($school['kode_kec'] ?? '')),
                    'address' => trim((string) ($school['alamat_jalan'] ?? '')) ?: null,
                ]);
            })
                ->filter(fn (array $school) => $school['npsn'] !== '' && $school['name'] !== '')
                ->unique('npsn')
                ->sortBy('name')
                ->values();
        } catch (Throwable $exception) {
            Log::warning('Gagal mengambil data sekolah', [
                'regency' => $regencyId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        Cache::put($cacheKey, $schools->all(), now()->addDays(self::CACHE_DAYS));

        return $schools;
    }
}
