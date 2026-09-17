<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\VisitEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Receives the small beacon sent by public/js/track.js. */
class AnalyticsController extends Controller
{
    public function collect(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (! is_array($data) || ! Str::isUuid($data['v'] ?? null)) {
            return response()->noContent();
        }

        $visit = Visit::where('uuid', $data['v'])->where('created_at', '>=', now()->subHours(12))->first();
        if (! $visit) {
            return response()->noContent();
        }

        $int = fn ($v, $max) => is_numeric($v) ? (int) max(0, min($max, $v)) : null;
        $str = fn ($v, $max) => is_string($v) && $v !== '' ? mb_substr(strip_tags($v), 0, $max) : null;

        $w = $int($data['w'] ?? null, 20000);
        $h = $int($data['h'] ?? null, 20000);
        $duration = $int($data['d'] ?? null, 6 * 3600);
        $scroll = $int($data['s'] ?? null, 100);

        $visit->fill(array_filter([
            'is_human' => true,
            'duration' => $duration !== null ? max((int) $visit->duration, $duration) : null,
            'scroll_depth' => $scroll !== null ? max((int) $visit->scroll_depth, $scroll) : null,
            'screen' => $w && $h ? "{$w}x{$h}" : null,
            'viewport_width' => $int($data['vw'] ?? null, 20000),
            'timezone' => $str($data['tz'] ?? null, 64),
        ], fn ($v) => $v !== null));

        if (! $visit->language && ($l = $str($data['l'] ?? null, 20)) && preg_match('/^([a-z]{2,3})(?:-([A-Za-z0-9]{2,4}))?$/i', $l, $m)) {
            $visit->language = strtolower($m[1]);
            $visit->locale = strtolower($m[1]).(isset($m[2]) ? '-'.strtoupper($m[2]) : '');
        }

        $visit->updated_at = now(); // "online now" heartbeat
        $visit->save();

        $events = collect($data['e'] ?? [])->take(20)
            ->filter(fn ($e) => is_array($e) && in_array($e['n'] ?? null, VisitEvent::NAMES, true))
            ->map(fn ($e) => ['visit_id' => $visit->id, 'name' => $e['n'], 'target' => $str($e['t'] ?? null, 255), 'created_at' => now()]);

        if ($events->isNotEmpty()) {
            // One "section" event per section per visit.
            $seen = $visit->events()->where('name', 'section')->pluck('target')->all();
            $rows = $events->reject(fn ($e) => $e['name'] === 'section' && in_array($e['target'], $seen, true))
                ->unique(fn ($e) => $e['name'] === 'section' ? 'section:'.$e['target'] : Str::uuid()->toString());
            VisitEvent::insert($rows->values()->all());
        }

        return response()->noContent();
    }
}
