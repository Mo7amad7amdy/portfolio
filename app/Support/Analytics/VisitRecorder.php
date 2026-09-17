<?php

namespace App\Support\Analytics;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitRecorder
{
    public static function record(Request $request, string $uuid, string $visitorKey, bool $isNew): ?Visit
    {
        $ip = GeoIp::clientIp($request);
        $ua = mb_substr((string) $request->userAgent(), 0, 500);
        $agent = UserAgent::parse($ua);
        [$language, $locale] = self::language($request->header('Accept-Language'));
        $referrer = $request->headers->get('referer');
        $source = TrafficSource::detect($referrer, $request->getHost(), $request->query('utm_source'), $request->query('utm_medium'), $agent['app']);

        return Visit::create([
            'uuid' => $uuid,
            'visitor_id' => hash('sha256', config('app.key').'|'.$visitorKey),
            'is_new' => $isNew,
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
            'referrer' => $referrer ? mb_substr($referrer, 0, 500) : null,
            'referrer_host' => $source['referrer_host'],
            'source' => mb_substr($source['source'], 0, 80),
            'medium' => mb_substr($source['medium'], 0, 30),
            'utm_source' => self::param($request, 'utm_source', 80),
            'utm_medium' => self::param($request, 'utm_medium', 80),
            'utm_campaign' => self::param($request, 'utm_campaign', 120),
            'ip' => config('analytics.store_ip', true) ? self::anonymize($ip) : null,
            'language' => $language,
            'locale' => $locale,
            'languages' => $request->header('Accept-Language') ? mb_substr($request->header('Accept-Language'), 0, 120) : null,
            'browser' => $agent['browser'],
            'browser_version' => $agent['browser_version'],
            'os' => $agent['os'],
            'device' => $agent['device'],
            'user_agent' => $ua ?: null,
            'is_bot' => $agent['is_bot'],
        ] + GeoIp::locate($ip, $request));
    }

    /** "en-US,en;q=0.9,ar;q=0.8" → ["en", "en-US"] */
    public static function language(?string $header): array
    {
        if (! $header || ! preg_match('/^\s*([a-zA-Z]{2,3})(?:[-_]([a-zA-Z0-9]{2,4}))?/', $header, $m)) {
            return [null, null];
        }
        $lang = strtolower($m[1]);

        return [$lang, isset($m[2]) ? $lang.'-'.strtoupper($m[2]) : $lang];
    }

    public static function anonymize(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        $packed = inet_pton($ip);

        return inet_ntop(substr($packed, 0, 6).str_repeat("\0", 10));
    }

    private static function param(Request $request, string $key, int $max): ?string
    {
        $v = $request->query($key);

        return is_string($v) && trim($v) !== '' ? Str::limit(trim($v), $max, '') : null;
    }
}
