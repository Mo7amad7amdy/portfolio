@extends('admin.layout')

@section('title', 'Analytics')

@php
    $params = request()->query();
    unset($params['page']);
    $url = fn (array $changes) => route('admin.analytics', array_filter(array_merge($params, $changes), fn ($v) => $v !== null && $v !== '' && $v !== false));
    $num = fn ($n) => $n >= 10000 ? round($n / 1000, 1).'K' : number_format($n);
    $dur = fn ($s) => $s === null ? '—' : ($s >= 60 ? intdiv($s, 60).'m '.str_pad($s % 60, 2, '0', STR_PAD_LEFT).'s' : $s.'s');
    $delta = function ($now, $before) {
        if ($before === null) return null;
        if ($before == 0) return $now > 0 ? ['+new', 'up'] : null;
        $d = round(($now - $before) / $before * 100);
        return [($d > 0 ? '+' : '').$d.'%', $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat')];
    };
    $flag = fn ($cc) => \App\Support\Analytics\GeoIp::flag($cc);
    $langName = function ($code) {
        if (! $code) return 'Unknown';
        $name = class_exists(\Locale::class) ? \Locale::getDisplayName($code, 'en') : $code;
        return $name && $name !== $code ? $name.' ('.$code.')' : $code;
    };
    $filterLabels = ['country_code' => 'Country', 'city' => 'City', 'language' => 'Language', 'source' => 'Source', 'medium' => 'Channel',
        'device' => 'Device', 'browser' => 'Browser', 'os' => 'OS', 'utm_campaign' => 'Campaign'];

    // Nice axis max: 1, 2, 5 × 10^k
    $niceMax = function ($max) {
        if ($max <= 4) return 4;
        $p = 10 ** floor(log10($max));
        foreach ([1, 2, 2.5, 5, 10] as $m) { if ($m * $p >= $max) return $m * $p; }
        return 10 * $p;
    };
    // Column with a 4px rounded data-end, square at the baseline.
    $col = function ($x, $y, $w, $base, $r = 4) {
        $h = $base - $y;
        if ($h <= 0) return '';
        $r = min($r, $h, $w / 2);
        return sprintf('M%.1f,%.1f V%.1f Q%.1f,%.1f %.1f,%.1f H%.1f Q%.1f,%.1f %.1f,%.1f V%.1f Z',
            $x, $base, $y + $r, $x, $y, $x + $r, $y, $x + $w - $r, $x + $w, $y, $x + $w, $y + $r, $base);
    };

    $cards = [
        ['Countries', $countries, 'country_code', fn ($r) => $flag($r['value']).' '.($r['extra'] ?: ($r['value'] ?: 'Unknown'))],
        ['Cities', $cities, 'city', fn ($r) => $flag($r['extra']).' '.$r['value']],
        ['Languages', $languages, 'language', fn ($r) => $langName($r['value'])],
        ['Sources', $sources, 'source', fn ($r) => $r['value'] ?: 'Unknown'],
        ['Channels', $mediums, 'medium', fn ($r) => ucfirst($r['value'] ?: 'unknown')],
        ['Referring sites', $referrers, null, fn ($r) => $r['value']],
        ['Campaigns (utm)', $campaigns, 'utm_campaign', fn ($r) => $r['value']],
        ['Devices', $devices, 'device', fn ($r) => ucfirst($r['value'] ?: 'unknown')],
        ['Browsers', $browsers, 'browser', fn ($r) => $r['value'] ?: 'Unknown'],
        ['Operating systems', $oses, 'os', fn ($r) => $r['value'] ?: 'Unknown'],
        ['Locales', $locales, null, fn ($r) => $r['value'] ?: 'Unknown'],
        ['Screen sizes', $screens, null, fn ($r) => $r['value']],
        ['Visitor timezones', $timezones, null, fn ($r) => $r['value']],
    ];
@endphp

@section('actions')
    <a class="btn btn-light" href="{{ route('admin.analytics.export', $params) }}">Export CSV</a>
@endsection

@section('content')
<div class="an">
    {{-- Filters: one row, above everything --}}
    <div class="an-filters">
        <nav class="seg" aria-label="Date range">
            @foreach ($ranges as $key => $label)
                <a href="{{ $url(['range' => $key]) }}" class="{{ $f['range'] === $key ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        @foreach ($f['active'] as $key => $value)
            <a class="chip" href="{{ $url([$key => null]) }}" title="Remove filter">
                {{ $filterLabels[$key] ?? $key }}: <b>{{ $key === 'country_code' ? $flag($value).' '.$value : $value }}</b> <span aria-hidden="true">×</span>
            </a>
        @endforeach
        <a class="toggle {{ $f['bots'] ? 'on' : '' }}" href="{{ $url(['bots' => $f['bots'] ? null : 1]) }}">
            <span class="dot"></span> Include bots
        </a>
        <span class="online" title="Visitors active in the last 2 minutes"><i></i>{{ $kpis['online'] }} online now</span>
    </div>

    @unless ($geoReady)
        <div class="flash flash-warn">
            <b>City and country lookup is off.</b> Run <code>php artisan analytics:geoip</code> once on the server
            to download the free location database. Visits recorded before that have no location.
        </div>
    @endunless

    {{-- KPI tiles --}}
    <section class="kpis">
        @php $dv = $delta($kpis['visitors'], $kpis['previous']['visitors'] ?? null); $dvi = $delta($kpis['visits'], $kpis['previous']['visits'] ?? null); @endphp
        <div class="kpi hero">
            <span>Unique visitors</span>
            <strong>{{ $num($kpis['visitors']) }}</strong>
            @if ($dv)<em class="d-{{ $dv[1] }}">{{ $dv[0] }} vs previous period</em>@endif
        </div>
        <div class="kpi">
            <span>Page views</span>
            <strong>{{ $num($kpis['visits']) }}</strong>
            @if ($dvi)<em class="d-{{ $dvi[1] }}">{{ $dvi[0] }}</em>@endif
        </div>
        <div class="kpi"><span>New visitors</span><strong>{{ $kpis['newShare'] }}%</strong></div>
        <div class="kpi"><span>Avg. time on page</span><strong>{{ $dur($kpis['avgDuration']) }}</strong></div>
        <div class="kpi"><span>Engaged visits</span><strong>{{ $kpis['engagedRate'] === null ? '—' : $kpis['engagedRate'].'%' }}</strong><em>15s+ or scrolled 60%</em></div>
        <div class="kpi"><span>CV downloads</span><strong>{{ $num($kpis['cv']) }}</strong></div>
        <div class="kpi"><span>Social clicks</span><strong>{{ $num($kpis['socialClicks']) }}</strong></div>
        <div class="kpi"><span>Countries</span><strong>{{ number_format($kpis['countryCount']) }}</strong></div>
        <div class="kpi"><span>Messages</span><strong>{{ $num($kpis['messages']) }}</strong></div>
    </section>

    {{-- Visits over time --}}
    @php
        $W = 1000; $H = 250; $L = 44; $R = 8; $T = 12; $B = 30;
        $seriesUnit = $series['unit'];
        $series = $series['points'];
        $n = max(1, count($series));
        $max = $niceMax(max(array_column($series, 'visits') ?: [0]));
        $slot = ($W - $L - $R) / $n;
        $bw = min(24, max(2, $slot * 0.62));
        $yOf = fn ($v) => $T + ($H - $T - $B) * (1 - $v / $max);
        $asLine = $n > 45;
        $labelEvery = max(1, (int) ceil($n / 10));
    @endphp
    <section class="card an-card">
        <div class="card-head"><h2>Visits over time</h2><small>per {{ $seriesUnit }} · {{ $tz }}</small></div>
        <div class="chart" data-chart>
            <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Page views over time">
                @foreach ([0, 0.25, 0.5, 0.75, 1] as $t)
                    @php $gy = $yOf($max * $t); @endphp
                    <line class="grid" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}"/>
                    <text class="axis" x="{{ $L - 8 }}" y="{{ $gy + 4 }}" text-anchor="end">{{ $num($max * $t) }}</text>
                @endforeach
                @if ($asLine)
                    @php
                        $pts = [];
                        foreach ($series as $i => $p) { $pts[] = round($L + $slot * ($i + .5), 1).','.round($yOf($p['visits']), 1); }
                        $base = $H - $B;
                    @endphp
                    <path class="area" d="M{{ explode(',', $pts[0])[0] }},{{ $base }} L{{ implode(' L', $pts) }} L{{ explode(',', end($pts))[0] }},{{ $base }} Z"/>
                    <polyline class="line" points="{{ implode(' ', $pts) }}"/>
                @else
                    @foreach ($series as $i => $p)
                        <path class="bar" d="{{ $col($L + $slot * $i + ($slot - $bw) / 2, $yOf($p['visits']), $bw, $H - $B) }}"/>
                    @endforeach
                @endif
                @foreach ($series as $i => $p)
                    @if ($i % $labelEvery === 0)
                        <text class="axis" x="{{ $L + $slot * ($i + .5) }}" y="{{ $H - 9 }}" text-anchor="middle">{{ $p['label'] }}</text>
                    @endif
                    <rect class="hit" x="{{ $L + $slot * $i }}" y="{{ $T }}" width="{{ $slot }}" height="{{ $H - $T - $B }}"
                          data-tip="{{ $p['label'] }}" data-tip-sub="{{ number_format($p['visits']) }} views · {{ number_format($p['visitors']) }} visitors"
                          data-x="{{ $L + $slot * ($i + .5) }}"/>
                @endforeach
                <line class="cross" x1="0" x2="0" y1="{{ $T }}" y2="{{ $H - $B }}" data-cross/>
            </svg>
        </div>
    </section>

    {{-- Breakdown cards --}}
    <div class="an-grid">
        @foreach ($cards as [$title, $rows, $filterKey, $labelFn])
            <section class="card an-card list-card">
                <div class="card-head"><h2>{{ $title }}</h2><small>visits</small></div>
                @forelse ($rows as $r)
                    @php $tag = $filterKey && $r['value'] !== null && ! isset($f['active'][$filterKey]) ? 'a' : 'div'; @endphp
                    <{{ $tag }} class="bar-row" @if ($tag === 'a') href="{{ $url([$filterKey => $r['value']]) }}" title="Filter by this" @endif
                        data-tip="{{ $labelFn($r) }}" data-tip-sub="{{ number_format($r['visits']) }} views · {{ number_format($r['visitors']) }} visitors">
                        <span class="bar-label">{{ $labelFn($r) }}</span>
                        <span class="bar-track"><span class="bar-fill" style="width: {{ max(1.5, $r['pct']) }}%"></span></span>
                        <span class="bar-value">{{ $num($r['visits']) }}</span>
                    </{{ $tag }}>
                @empty
                    <p class="empty">No data yet.</p>
                @endforelse
            </section>
        @endforeach

        {{-- Hour of day --}}
        @php $hmax = $niceMax(max($hours)); $hw = 480 / 24; @endphp
        <section class="card an-card">
            <div class="card-head"><h2>Time of day</h2><small>{{ $tz }}</small></div>
            <div class="chart small" data-chart>
                <svg viewBox="0 0 480 200" role="img" aria-label="Visits by hour of day">
                    <line class="grid" x1="0" x2="480" y1="170" y2="170"/>
                    @foreach ($hours as $h => $v)
                        <path class="bar" d="{{ $col($h * $hw + $hw * .2, 10 + 160 * (1 - $v / $hmax), $hw * .6, 170, 3) }}"/>
                        @if ($h % 6 === 0)<text class="axis" x="{{ $h * $hw + $hw / 2 }}" y="192" text-anchor="middle">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}h</text>@endif
                        <rect class="hit" x="{{ $h * $hw }}" y="10" width="{{ $hw }}" height="160" data-x="{{ $h * $hw + $hw / 2 }}"
                              data-tip="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00–{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:59" data-tip-sub="{{ number_format($v) }} views"/>
                    @endforeach
                </svg>
            </div>
        </section>

        {{-- How far people read --}}
        <section class="card an-card list-card">
            <div class="card-head"><h2>Sections reached</h2><small>% of real visitors</small></div>
            @foreach ($sections as $s)
                <div class="bar-row" data-tip="{{ $s['label'] }}" data-tip-sub="{{ number_format($s['total']) }} visitors reached it">
                    <span class="bar-label">{{ $s['label'] }}</span>
                    <span class="bar-track"><span class="bar-fill" style="width: {{ max(1.5, $s['pct']) }}%"></span></span>
                    <span class="bar-value">{{ $s['pct'] }}%</span>
                </div>
            @endforeach
        </section>

        {{-- Actions --}}
        <section class="card an-card list-card">
            <div class="card-head"><h2>Actions</h2><small>clicks</small></div>
            @forelse ($actions as $a)
                <div class="kv"><span>{{ $a['label'] }}</span><b>{{ number_format($a['total']) }}</b></div>
            @empty
                <p class="empty">No clicks yet.</p>
            @endforelse
            @if ($socialTargets->isNotEmpty())
                <p class="sub">Social profiles opened</p>
                @foreach ($socialTargets as $t)
                    <div class="kv"><span>{{ $t->target ?: '—' }}</span><b>{{ number_format($t->total) }}</b></div>
                @endforeach
            @endif
            @if ($projectTargets->isNotEmpty())
                <p class="sub">Project links opened</p>
                @foreach ($projectTargets as $t)
                    <div class="kv"><span class="trunc">{{ preg_replace('#^https?://(www\.)?#', '', $t->target) }}</span><b>{{ number_format($t->total) }}</b></div>
                @endforeach
            @endif
        </section>
    </div>

    {{-- Every visit --}}
    <section class="card table-card">
        <div class="card-head"><h2>Visit log</h2><small>{{ number_format($recent->total()) }} in this period</small></div>
        <div class="table-wrap">
            <table class="visits">
                <thead>
                <tr><th>When</th><th>Location</th><th>Language</th><th>Source</th><th>Device</th><th>Time</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($recent as $v)
                    <tr class="{{ $v->is_bot ? 'is-bot' : '' }}">
                        <td data-label="When">
                            <b>{{ $v->created_at->copy()->setTimezone($tz)->format('M j, H:i') }}</b>
                            <small>{{ $v->is_new ? 'New' : 'Returning' }}{{ $v->imported ? ' · from log' : '' }}</small>
                        </td>
                        <td data-label="Location">
                            {{ $flag($v->country_code) }} {{ $v->place() }}
                            @if ($v->region && $v->region !== $v->city)<small>{{ $v->region }}</small>@endif
                        </td>
                        <td data-label="Language">{{ $v->locale ?: '—' }}@if ($v->timezone)<small>{{ $v->timezone }}</small>@endif</td>
                        <td data-label="Source">
                            {{ $v->source ?: '—' }}
                            <small class="trunc" title="{{ $v->referrer }}">{{ $v->utm_campaign ? 'utm: '.$v->utm_campaign : ($v->referrer_host ?: $v->medium) }}</small>
                        </td>
                        <td data-label="Device">
                            {{ ucfirst($v->device ?? '—') }} · {{ $v->os ?: '?' }}
                            <small>{{ $v->browser }} {{ $v->browser_version }}{{ $v->screen ? ' · '.$v->screen : '' }}</small>
                        </td>
                        <td data-label="Time">
                            {{ $v->is_human ? $dur($v->duration) : ($v->is_bot ? 'bot' : '—') }}
                            @if ($v->scroll_depth !== null)<small>scrolled {{ $v->scroll_depth }}%</small>@endif
                        </td>
                        <td data-label="Actions">{{ $v->events_count ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No visits in this period yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($recent->hasPages())
            <div class="pager">
                @if ($recent->previousPageUrl())<a class="btn btn-light btn-sm" href="{{ $recent->previousPageUrl() }}">← Newer</a>@endif
                <span>Page {{ $recent->currentPage() }} of {{ $recent->lastPage() }}</span>
                @if ($recent->nextPageUrl())<a class="btn btn-light btn-sm" href="{{ $recent->nextPageUrl() }}">Older →</a>@endif
            </div>
        @endif
    </section>

    <p class="hint an-foot">
        Your own visits are not counted while you're logged in. To exclude a phone or another browser, open
        <code>{{ url('/?notrack=1') }}</code> on it once. Location data:
        <a href="https://db-ip.com" target="_blank" rel="noopener">IP Geolocation by DB-IP</a>.
    </p>
</div>

<div class="tip" id="an-tip" role="tooltip" hidden></div>
@endsection

@push('scripts')
<script>
    // One tooltip for every chart mark and bar row; crosshair on the time chart.
    (() => {
        const tip = document.getElementById('an-tip');
        const place = (e) => {
            const pad = 14, r = tip.getBoundingClientRect();
            let x = e.clientX + pad, y = e.clientY + pad;
            if (x + r.width > innerWidth - 8) x = e.clientX - r.width - pad;
            if (y + r.height > innerHeight - 8) y = e.clientY - r.height - pad;
            tip.style.transform = `translate(${x}px, ${y}px)`;
        };
        document.querySelectorAll('[data-tip]').forEach((el) => {
            el.addEventListener('pointerenter', (e) => {
                tip.replaceChildren(Object.assign(document.createElement('b'), { textContent: el.dataset.tip }), document.createTextNode(el.dataset.tipSub || ''));
                tip.hidden = false; place(e);
                const cross = el.closest('svg')?.querySelector('[data-cross]');
                if (cross && el.dataset.x) { cross.setAttribute('x1', el.dataset.x); cross.setAttribute('x2', el.dataset.x); cross.classList.add('on'); }
                el.classList.add('is-hover');
            });
            el.addEventListener('pointermove', place);
            el.addEventListener('pointerleave', () => {
                tip.hidden = true; el.classList.remove('is-hover');
                el.closest('svg')?.querySelector('[data-cross]')?.classList.remove('on');
            });
        });
    })();
</script>
@endpush
