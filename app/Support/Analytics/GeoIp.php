<?php

namespace App\Support\Analytics;

use Illuminate\Http\Request;
use Throwable;

/**
 * Resolves a visitor's location:
 *  1. Cloudflare headers, when the site is behind Cloudflare;
 *  2. a local MaxMind-format database (storage/app/geoip/city.mmdb),
 *     downloaded with `php artisan analytics:geoip`.
 * Nothing is sent to third parties.
 */
class GeoIp
{
    private static ?MmdbReader $reader = null;

    private static bool $readerFailed = false;

    public static function path(): string
    {
        return config('analytics.geoip_path', storage_path('app/geoip/city.mmdb'));
    }

    public static function available(): bool
    {
        return is_file(self::path());
    }

    /** The client IP, honouring Cloudflare's header when present. */
    public static function clientIp(Request $request): ?string
    {
        $cf = $request->header('CF-Connecting-IP');
        if ($cf && $request->headers->has('CF-Ray') && filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }

        return $request->ip();
    }

    /** @return array{country_code: ?string, country: ?string, region: ?string, city: ?string, latitude: ?float, longitude: ?float} */
    public static function locate(?string $ip, ?Request $request = null): array
    {
        $out = ['country_code' => null, 'country' => null, 'region' => null, 'city' => null, 'latitude' => null, 'longitude' => null];

        if (! $ip) {
            return $out;
        }

        // Cloudflare adds these headers itself; only trust them on requests that came through Cloudflare.
        if ($request && $request->headers->has('CF-Ray')
            && ($cc = strtoupper((string) $request->header('CF-IPCountry'))) && preg_match('/^[A-Z]{2}$/', $cc) && ! in_array($cc, ['XX', 'T1'], true)) {
            $out['country_code'] = $cc;
            $out['city'] = self::clean($request->header('CF-IPCity'));
            $out['region'] = self::clean($request->header('CF-Region'));
            $out['latitude'] = is_numeric($request->header('CF-IPLatitude')) ? (float) $request->header('CF-IPLatitude') : null;
            $out['longitude'] = is_numeric($request->header('CF-IPLongitude')) ? (float) $request->header('CF-IPLongitude') : null;
        } elseif (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['country' => 'Local network'] + $out;
        }

        if (! $out['city'] && ($row = self::lookup($ip))) {
            $out['country_code'] ??= $row['country']['iso_code'] ?? $row['registered_country']['iso_code'] ?? null;
            $out['city'] = $row['city']['names']['en'] ?? null;
            $out['region'] ??= $row['subdivisions'][0]['names']['en'] ?? null;
            $out['latitude'] ??= isset($row['location']['latitude']) ? (float) $row['location']['latitude'] : null;
            $out['longitude'] ??= isset($row['location']['longitude']) ? (float) $row['location']['longitude'] : null;
            $out['country'] = $row['country']['names']['en'] ?? null;
        }

        if ($out['country_code']) {
            $out['country'] ??= self::countryName($out['country_code']);
        }

        return $out;
    }

    public static function countryName(string $code): string
    {
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');
            if ($name && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }

    public static function flag(?string $code): string
    {
        if (! $code || ! preg_match('/^[A-Z]{2}$/', $code)) {
            return '🌐';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - 65).mb_chr(0x1F1E6 + ord($code[1]) - 65);
    }

    /** Forget the open database (after an update, or between tests). */
    public static function reset(): void
    {
        self::$reader = null;
        self::$readerFailed = false;
    }

    private static function lookup(string $ip): ?array
    {
        if (self::$readerFailed || ! self::available()) {
            return null;
        }
        try {
            self::$reader ??= new MmdbReader(self::path());

            return self::$reader->get($ip);
        } catch (Throwable $e) {
            self::$readerFailed = true;
            report($e);

            return null;
        }
    }

    private static function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : mb_substr(rawurldecode($v), 0, 80);
    }
}
