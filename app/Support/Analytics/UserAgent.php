<?php

namespace App\Support\Analytics;

/** Small, dependency-free user-agent parser: browser, OS, device, in-app browser, bots. */
class UserAgent
{
    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|facebookcatalog|meta-externalagent|embedly|quora link|'
        .'whatsapp|telegrambot|discordbot|slackbot|linkedinbot|twitterbot|skypeuripreview|pinterest|vkshare|redditbot|'
        .'headless|phantomjs|puppeteer|playwright|selenium|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|'
        .'curl|wget|python|httpclient|okhttp|go-http|java\/|libwww|axios|node-fetch|guzzle|scrapy|feedfetcher|'
        .'preview|validator|checker|scanner|semrush|ahrefs|mj12|dotbot|petalbot|bytespider|gptbot|claudebot|perplexity|ccbot|amazonbot|applebot/i';

    /** @return array{browser: ?string, browser_version: ?string, os: ?string, device: string, app: ?string, is_bot: bool} */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;
        $isBot = $ua === '' || (bool) preg_match(self::BOTS, $ua);

        [$browser, $version] = self::browser($ua);
        $app = self::app($ua);
        if ($app && ! $isBot && in_array($browser, ['Other', 'Safari', 'Chrome'], true)) {
            $browser = "$app in-app";
        }

        return [
            'browser' => $browser,
            'browser_version' => $version,
            'os' => self::os($ua),
            'device' => $isBot ? 'bot' : self::device($ua),
            'app' => $app,
            'is_bot' => $isBot,
        ];
    }

    /** In-app browsers: tells you the visit came from a link inside that app. */
    public static function app(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Instagram') => 'Instagram',
            (bool) preg_match('/FBAN|FBAV|FB_IAB|FBIOS/', $ua) => 'Facebook',
            str_contains($ua, 'LinkedInApp') => 'LinkedIn',
            (bool) preg_match('/musical_ly|TikTok|BytedanceWebview/i', $ua) => 'TikTok',
            (bool) preg_match('/Twitter for|TwitterAndroid/', $ua) => 'X',
            str_contains($ua, 'Snapchat') => 'Snapchat',
            str_contains($ua, 'Telegram') => 'Telegram',
            default => null,
        };
    }

    /** @return array{0: ?string, 1: ?string} */
    private static function browser(string $ua): array
    {
        foreach ([
            'Edge' => '/Edg(?:e|A|iOS)?\/([\d.]+)/',
            'Opera' => '/(?:OPR|Opera|OPX)\/([\d.]+)/',
            'Samsung Internet' => '/SamsungBrowser\/([\d.]+)/',
            'Yandex' => '/YaBrowser\/([\d.]+)/',
            'UC Browser' => '/UCBrowser\/([\d.]+)/',
            'Brave' => '/Brave\/([\d.]+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/([\d.]+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/([\d.]+)/',
            'Safari' => '/Version\/([\d.]+).*Safari/',
            'Internet Explorer' => '/(?:MSIE |Trident\/.*rv:)([\d.]+)/',
        ] as $name => $re) {
            if (preg_match($re, $ua, $m)) {
                return [$name, explode('.', $m[1])[0]];
            }
        }

        return [$ua === '' ? null : 'Other', null];
    }

    private static function os(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/iPad/', $ua) => 'iPadOS',
            (bool) preg_match('/iPhone|iPod/', $ua) => 'iOS',
            (bool) preg_match('/Android/', $ua) => 'Android',
            (bool) preg_match('/CrOS/', $ua) => 'ChromeOS',
            (bool) preg_match('/Windows/', $ua) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
            (bool) preg_match('/Linux/', $ua) => 'Linux',
            $ua === '' => null,
            default => 'Other',
        };
    }

    private static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|Nexus (7|9|10)|SM-T|Kindle|Silk/i', $ua),
            str_contains($ua, 'Android') && ! str_contains($ua, 'Mobile') => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }
}
