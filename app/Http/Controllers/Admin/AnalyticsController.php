<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Visit;
use App\Models\VisitEvent;
use App\Support\Analytics\GeoIp;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public const RANGES = [
        'today' => 'Today',
        '7d' => '7 days',
        '30d' => '30 days',
        '90d' => '90 days',
        '12m' => '12 months',
        'all' => 'Since launch',
    ];

    /** Dimensions that can be clicked to filter the whole dashboard. */
    private const FILTERS = ['country_code', 'city', 'language', 'source', 'medium', 'device', 'browser', 'os', 'utm_campaign'];

    public function index(Request $request): View
    {
        $f = $this->filters($request);
        $tz = config('analytics.timezone', 'Africa/Cairo');
        $base = fn () => $this->query($f);

        $visits = $base()->count();
        $visitors = $base()->distinct()->count('visitor_id');
        $human = $base()->where('is_human', true);
        $avgDuration = (int) round((clone $human)->whereNotNull('duration')->avg('duration') ?? 0);
        $humanCount = (clone $human)->count();
        $engaged = (clone $human)->where(fn ($q) => $q->where('duration', '>=', 15)->orWhere('scroll_depth', '>=', 60))->count();

        $previous = null;
        if ($f['prevFrom']) {
            $prev = $this->query(['from' => $f['prevFrom'], 'to' => $f['from']] + $f);
            $previous = ['visits' => (clone $prev)->count(), 'visitors' => (clone $prev)->distinct()->count('visitor_id')];
        }

        $events = VisitEvent::query()
            ->whereIn('visit_id', $base()->select('id'))
            ->selectRaw('name, target, count(*) as total')
            ->groupBy('name', 'target')
            ->get();

        return view('admin.analytics', [
            'f' => $f,
            'ranges' => self::RANGES,
            'geoReady' => GeoIp::available(),
            'kpis' => [
                'visits' => $visits,
                'visitors' => $visitors,
                'newShare' => $visitors ? round($base()->where('is_new', true)->distinct()->count('visitor_id') / $visitors * 100) : 0,
                'avgDuration' => $avgDuration,
                'engagedRate' => $humanCount ? round($engaged / $humanCount * 100) : null,
                'cv' => (int) $events->where('name', 'cv_download')->sum('total'),
                'socialClicks' => (int) $events->where('name', 'social_click')->sum('total'),
                'countryCount' => $base()->whereNotNull('country_code')->distinct()->count('country_code'),
                'messages' => Message::where('created_at', '>=', $f['from'])->where('created_at', '<', $f['to'])->count(),
                'online' => Visit::humans()->where('is_human', true)->where('updated_at', '>=', now()->subMinutes(2))->count(),
                'previous' => $previous,
            ],
            'series' => $this->series($base(), $f, $tz),
            'hours' => $this->hours($base(), $tz),
            'countries' => $this->top($base(), 'country_code', extra: 'country'),
            'cities' => $this->top($base()->whereNotNull('city'), 'city', extra: 'country_code'),
            'languages' => $this->top($base(), 'language'),
            'locales' => $this->top($base(), 'locale', 8),
            'sources' => $this->top($base(), 'source'),
            'mediums' => $this->top($base(), 'medium', 6),
            'referrers' => $this->top($base()->whereNotNull('referrer_host'), 'referrer_host'),
            'campaigns' => $this->top($base()->whereNotNull('utm_campaign'), 'utm_campaign', 8),
            'devices' => $this->top($base(), 'device', 5),
            'browsers' => $this->top($base(), 'browser', 8),
            'oses' => $this->top($base(), 'os', 8),
            'screens' => $this->top($base()->whereNotNull('screen'), 'screen', 8),
            'timezones' => $this->top($base()->whereNotNull('timezone'), 'timezone', 8),
            'sections' => $this->sections($events, $humanCount),
            'actions' => $events->where('name', '!=', 'section')->groupBy('name')
                ->map(fn ($g, $name) => ['label' => VisitEvent::LABELS[$name] ?? $name, 'total' => (int) $g->sum('total')])
                ->sortByDesc('total')->values(),
            'socialTargets' => $events->where('name', 'social_click')->sortByDesc('total')->take(8)->values(),
            'projectTargets' => $events->where('name', 'project_click')->sortByDesc('total')->take(8)->values(),
            'recent' => $base()->withCount('events')->latest()->paginate(25)->withQueryString(),
            'tz' => $tz,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $f = $this->filters($request);
        $columns = ['created_at', 'visitor_id', 'is_new', 'country_code', 'country', 'region', 'city', 'language', 'locale',
            'source', 'medium', 'referrer', 'utm_source', 'utm_medium', 'utm_campaign', 'device', 'browser', 'browser_version',
            'os', 'screen', 'timezone', 'duration', 'scroll_depth', 'is_human', 'is_bot', 'imported', 'ip', 'user_agent'];

        return response()->streamDownload(function () use ($f, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
            fputcsv($out, $columns);
            $this->query($f)->orderBy('id')->select($columns)->chunk(1000, function ($rows) use ($out, $columns) {
                foreach ($rows as $row) {
                    fputcsv($out, array_map(fn ($c) => $row->getRawOriginal($c), $columns));
                }
            });
            fclose($out);
        }, 'visits-'.$f['range'].'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        $range = array_key_exists($request->query('range'), self::RANGES) ? $request->query('range') : '30d';
        $tz = config('analytics.timezone', 'Africa/Cairo');
        $now = Carbon::now($tz);

        $from = match ($range) {
            'today' => $now->copy()->startOfDay(),
            '7d' => $now->copy()->subDays(6)->startOfDay(),
            '30d' => $now->copy()->subDays(29)->startOfDay(),
            '90d' => $now->copy()->subDays(89)->startOfDay(),
            '12m' => $now->copy()->subMonths(11)->startOfMonth(),
            'all' => Carbon::parse(Visit::min('created_at') ?? $now)->setTimezone($tz)->startOfDay(),
        };
        $to = $now->copy()->addMinute();

        $active = [];
        foreach (self::FILTERS as $key) {
            $v = $request->query($key);
            if (is_string($v) && $v !== '') {
                $active[$key] = mb_substr($v, 0, 120);
            }
        }

        return [
            'range' => $range,
            'from' => $from->utc(),
            'to' => $to->utc(),
            'prevFrom' => $range === 'all' ? null : $from->copy()->sub($from->diff($to))->utc(),
            'bots' => $request->boolean('bots'),
            'active' => $active,
        ];
    }

    private function query(array $f): Builder
    {
        return Visit::query()
            ->where('created_at', '>=', $f['from'])
            ->where('created_at', '<', $f['to'])
            ->when(! $f['bots'], fn ($q) => $q->where('is_bot', false))
            ->where(function ($q) use ($f) {
                foreach ($f['active'] as $col => $value) {
                    $q->where($col, $value);
                }
            });
    }

    /** Visits per day (per month for long ranges), in the dashboard timezone. */
    private function series(Builder $q, array $f, string $tz): array
    {
        $from = $f['from']->copy()->setTimezone($tz);
        $to = $f['to']->copy()->setTimezone($tz);
        $monthly = $from->diffInDays($to) > 120;
        $hourly = $f['range'] === 'today';
        $format = $hourly ? 'Y-m-d H' : ($monthly ? 'Y-m' : 'Y-m-d');

        $buckets = [];
        for ($c = $from->copy(); $c < $to; $hourly ? $c->addHour() : ($monthly ? $c->addMonth() : $c->addDay())) {
            $buckets[$c->format($format)] = ['label' => $c->format($hourly ? 'H:00' : ($monthly ? 'M Y' : 'M j')), 'visits' => 0, 'visitors' => []];
        }

        foreach ($q->select('created_at', 'visitor_id')->cursor() as $v) {
            $key = $v->created_at->copy()->setTimezone($tz)->format($format);
            if (isset($buckets[$key])) {
                $buckets[$key]['visits']++;
                $buckets[$key]['visitors'][$v->visitor_id] = true;
            }
        }

        return [
            'unit' => $hourly ? 'hour' : ($monthly ? 'month' : 'day'),
            'points' => array_values(array_map(fn ($b) => ['label' => $b['label'], 'visits' => $b['visits'], 'visitors' => count($b['visitors'])], $buckets)),
        ];
    }

    /** Visits by hour of day (0–23), in the dashboard timezone. */
    private function hours(Builder $q, string $tz): array
    {
        $hours = array_fill(0, 24, 0);
        foreach ($q->select('created_at')->cursor() as $v) {
            $hours[(int) $v->created_at->copy()->setTimezone($tz)->format('G')]++;
        }

        return $hours;
    }

    private function top(Builder $q, string $column, int $limit = 10, ?string $extra = null): Collection
    {
        $select = [$column];
        if ($extra) {
            $select[] = $extra;
        }

        $rows = $q->selectRaw(implode(', ', $select).', count(*) as visits, count(distinct visitor_id) as visitors')
            ->groupBy($select)
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();

        $max = max(1, (int) $rows->max('visits'));

        return $rows->map(fn ($r) => [
            'value' => $r->{$column},
            'extra' => $extra ? $r->{$extra} : null,
            'visits' => (int) $r->visits,
            'visitors' => (int) $r->visitors,
            'pct' => round($r->visits / $max * 100, 1),
        ]);
    }

    private function sections(Collection $events, int $humans): Collection
    {
        $order = ['about' => 'About', 'experience' => 'Experience', 'work' => 'Work', 'stack' => 'Stack', 'contact' => 'Contact'];
        $counts = $events->where('name', 'section')->pluck('total', 'target');

        return collect($order)->map(fn ($label, $id) => [
            'label' => $label,
            'total' => (int) ($counts[$id] ?? 0),
            'pct' => $humans ? round(($counts[$id] ?? 0) / $humans * 100) : 0,
        ])->values();
    }
}
