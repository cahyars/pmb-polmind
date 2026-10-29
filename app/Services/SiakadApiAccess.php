<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Token API untuk SIAKAD, dikelola Admin PMB dari panel. Yang disimpan hanya hash-nya,
 * jadi token utuh hanya terlihat sekali saat dibuat. Membuat token baru membatalkan yang lama.
 */
class SiakadApiAccess
{
    private const HASH = 'siakad_api_token_hash';

    private const HINT = 'siakad_api_token_hint';

    private const CREATED_AT = 'siakad_api_token_created_at';

    private const CREATED_BY = 'siakad_api_token_created_by';

    private const ALLOWED_IPS = 'siakad_api_allowed_ips';

    public static function generate(string $actorName): string
    {
        $token = bin2hex(random_bytes(32));

        Setting::put([
            self::HASH => hash('sha256', $token),
            self::HINT => substr($token, -4),
            self::CREATED_AT => now()->toDateTimeString(),
            self::CREATED_BY => $actorName,
        ]);

        return $token;
    }

    public static function revoke(): void
    {
        Setting::put([self::HASH => null, self::HINT => null, self::CREATED_AT => null, self::CREATED_BY => null]);
    }

    public static function enabled(): bool
    {
        return filled(Setting::get(self::HASH));
    }

    public static function matches(?string $token): bool
    {
        $hash = (string) Setting::get(self::HASH);

        return $hash !== '' && filled($token) && hash_equals($hash, hash('sha256', $token));
    }

    /**
     * @return array{hint: ?string, created_at: ?string, created_by: ?string}
     */
    public static function details(): array
    {
        return [
            'hint' => Setting::get(self::HINT),
            'created_at' => Setting::get(self::CREATED_AT),
            'created_by' => Setting::get(self::CREATED_BY),
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedIps(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) Setting::get(self::ALLOWED_IPS)))));
    }

    /**
     * @param  list<string>  $ips
     */
    public static function setAllowedIps(array $ips): void
    {
        Setting::put([self::ALLOWED_IPS => implode(',', array_unique($ips)) ?: null]);
    }
}
