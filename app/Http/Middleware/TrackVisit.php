<?php

namespace App\Http\Middleware;

use App\Support\Analytics\VisitRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records a page view. The row is written after the response is sent
 * (terminate), so visitors never wait for analytics.
 */
class TrackVisit
{
    public const COOKIE = '_vid';

    public function handle(Request $request, Closure $next): Response
    {
        // Visit /?notrack=1 once on each of your devices to exclude yourself.
        if ($request->has('notrack')) {
            return $next($request)->withCookie(cookie()->forever('_notrack', '1'));
        }

        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

        $visitor = $request->cookie(self::COOKIE);
        $isNew = ! is_string($visitor) || strlen($visitor) < 20;
        $visitor = $isNew ? Str::random(40) : $visitor;

        $request->attributes->set('analytics.uuid', (string) Str::uuid());
        $request->attributes->set('analytics.visitor', [$visitor, $isNew]);

        $response = $next($request);

        if ($isNew && method_exists($response, 'withCookie')) {
            $response->withCookie(cookie(self::COOKIE, $visitor, 60 * 24 * 395, null, null, $request->isSecure(), true, false, 'lax'));
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $uuid = $request->attributes->get('analytics.uuid');
        if (! $uuid || $response->getStatusCode() >= 400) {
            return;
        }
        [$visitor, $isNew] = $request->attributes->get('analytics.visitor');

        try {
            VisitRecorder::record($request, $uuid, $visitor, $isNew);
        } catch (Throwable $e) {
            report($e); // analytics must never break the site
        }
    }

    private function shouldTrack(Request $request): bool
    {
        return config('analytics.enabled', true)
            && $request->isMethod('GET')
            && ! $request->user()
            && ! $request->cookie('_notrack')
            && ! in_array($request->ip(), config('analytics.ignore_ips', []), true)
            && ! preg_match('/prefetch|prerender/i', (string) ($request->header('Sec-Purpose') ?: $request->header('Purpose')))
            && ! (config('analytics.respect_dnt') && ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1'));
    }
}
