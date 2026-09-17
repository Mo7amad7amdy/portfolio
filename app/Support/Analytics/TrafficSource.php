<?php

namespace App\Support\Analytics;

/** Classifies where a visit came from: campaign → in-app browser → referrer → direct. */
class TrafficSource
{
    private const SEARCH = ['google' => 'Google', 'bing' => 'Bing', 'duckduckgo' => 'DuckDuckGo', 'yahoo' => 'Yahoo',
        'yandex' => 'Yandex', 'baidu' => 'Baidu', 'ecosia' => 'Ecosia', 'brave' => 'Brave Search', 'chatgpt' => 'ChatGPT',
        'perplexity' => 'Perplexity', 'claude.ai' => 'Claude'];

    private const SOCIAL = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'fb.me' => 'Facebook', 'linkedin' => 'LinkedIn',
        'lnkd.in' => 'LinkedIn', 't.co' => 'X', 'twitter' => 'X', 'x.com' => 'X', 'youtube' => 'YouTube', 'tiktok' => 'TikTok',
        'reddit' => 'Reddit', 'whatsapp' => 'WhatsApp', 'wa.me' => 'WhatsApp', 't.me' => 'Telegram', 'telegram' => 'Telegram',
        'github' => 'GitHub', 'threads' => 'Threads', 'pinterest' => 'Pinterest', 'snapchat' => 'Snapchat', 'behance' => 'Behance',
        'dribbble' => 'Dribbble', 'medium' => 'Medium', 'dev.to' => 'DEV', 'stackoverflow' => 'Stack Overflow', 'discord' => 'Discord'];

    /** @return array{source: string, medium: string, referrer_host: ?string} */
    public static function detect(?string $referrer, ?string $ownHost, ?string $utmSource, ?string $utmMedium, ?string $app): array
    {
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : null;
        $host = $host ? preg_replace('/^(www|m|l|lm|mobile)\./', '', $host) : null;
        if ($host && $ownHost && $host === preg_replace('/^www\./', '', strtolower($ownHost))) {
            $host = null; // internal navigation
        }

        if ($utmSource) {
            return ['source' => self::named(strtolower($utmSource)) ?? ucfirst($utmSource), 'medium' => strtolower($utmMedium ?: 'campaign'), 'referrer_host' => $host];
        }
        if ($app) {
            return ['source' => $app, 'medium' => 'social', 'referrer_host' => $host];
        }
        if (! $host) {
            return ['source' => 'Direct', 'medium' => 'direct', 'referrer_host' => null];
        }
        foreach (self::SEARCH as $needle => $name) {
            if (str_contains($host, $needle)) {
                return ['source' => $name, 'medium' => 'search', 'referrer_host' => $host];
            }
        }
        if ($name = self::named($host)) {
            return ['source' => $name, 'medium' => 'social', 'referrer_host' => $host];
        }

        return ['source' => $host, 'medium' => 'referral', 'referrer_host' => $host];
    }

    private static function named(string $value): ?string
    {
        foreach (self::SOCIAL as $needle => $name) {
            if ($value === $needle || str_contains($value, $needle)) {
                return $name;
            }
        }

        return null;
    }
}
