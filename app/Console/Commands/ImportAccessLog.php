<?php

namespace App\Console\Commands;

use App\Models\Visit;
use App\Support\Analytics\GeoIp;
use App\Support\Analytics\TrafficSource;
use App\Support\Analytics\UserAgent;
use App\Support\Analytics\VisitRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Imports home-page visits from web server access logs (Apache/LiteSpeed/Nginx
 * "combined" format, plain or .gz), so you get history from before tracking started.
 */
class ImportAccessLog extends Command
{
    protected $signature = 'analytics:import-log
        {files* : Access log files (.log or .gz)}
        {--host= : Your site host, to ignore internal referrers (defaults to APP_URL)}
        {--dry-run : Only count what would be imported}';

    protected $description = 'Import past visits from server access logs';

    private const LINE = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(GET|HEAD) (\S+)[^"]*" (\d{3}) \S+(?: "([^"]*)" "([^"]*)")?/';

    public function handle(): int
    {
        $host = $this->option('host') ?: parse_url((string) config('app.url'), PHP_URL_HOST);
        $firstTracked = Visit::where('imported', false)->min('created_at');
        $stats = ['lines' => 0, 'pages' => 0, 'imported' => 0, 'skipped' => 0];

        foreach ($this->argument('files') as $file) {
            if (! is_file($file)) {
                $this->warn("Missing: $file");

                continue;
            }
            $this->line("Reading $file");
            $fh = str_ends_with($file, '.gz') ? gzopen($file, 'rb') : fopen($file, 'rb');
            $batch = [];

            while (($line = str_ends_with($file, '.gz') ? gzgets($fh) : fgets($fh)) !== false) {
                $stats['lines']++;
                if (! preg_match(self::LINE, $line, $m)) {
                    continue;
                }
                [, $ip, $time, $method, $url, $status, $referrer, $ua] = $m + array_fill(0, 8, '');
                $path = parse_url($url, PHP_URL_PATH) ?: '/';
                if ($method !== 'GET' || ! in_array($status, ['200', '304'], true) || rtrim($path, '/') !== '') {
                    continue; // only home-page views
                }
                $stats['pages']++;

                $at = Carbon::createFromFormat('d/M/Y:H:i:s O', $time)->utc();
                if ($firstTracked && $at >= Carbon::parse($firstTracked)) {
                    $stats['skipped']++; // already tracked live

                    continue;
                }

                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $agent = UserAgent::parse($ua);
                $referrer = $referrer === '-' ? null : $referrer;
                $source = TrafficSource::detect($referrer, $host, $query['utm_source'] ?? null, $query['utm_medium'] ?? null, $agent['app']);

                $batch[] = [
                    'uuid' => (string) Str::uuid(),
                    'visitor_id' => hash('sha256', config('app.key').'|log|'.$ip.'|'.$ua),
                    'is_new' => true,
                    'path' => '/',
                    'referrer' => $referrer ? mb_substr($referrer, 0, 500) : null,
                    'referrer_host' => $source['referrer_host'],
                    'source' => mb_substr($source['source'], 0, 80),
                    'medium' => $source['medium'],
                    'utm_source' => isset($query['utm_source']) ? mb_substr((string) $query['utm_source'], 0, 80) : null,
                    'utm_medium' => isset($query['utm_medium']) ? mb_substr((string) $query['utm_medium'], 0, 80) : null,
                    'utm_campaign' => isset($query['utm_campaign']) ? mb_substr((string) $query['utm_campaign'], 0, 120) : null,
                    'ip' => config('analytics.store_ip', true) ? VisitRecorder::anonymize($ip) : null,
                    'browser' => $agent['browser'],
                    'browser_version' => $agent['browser_version'],
                    'os' => $agent['os'],
                    'device' => $agent['device'],
                    'user_agent' => mb_substr($ua, 0, 500) ?: null,
                    'is_bot' => $agent['is_bot'],
                    'imported' => true,
                    'created_at' => $at,
                    'updated_at' => $at,
                ] + GeoIp::locate($ip);

                if (count($batch) >= 500) {
                    $stats['imported'] += $this->flush($batch);
                }
            }
            $stats['imported'] += $this->flush($batch);
            str_ends_with($file, '.gz') ? gzclose($fh) : fclose($fh);
        }

        $this->table(['Lines read', 'Home-page views', 'Imported', 'Skipped (already tracked)'], [array_values($stats)]);
        if ($this->option('dry-run')) {
            $this->comment('Dry run: nothing was saved.');
        }

        return self::SUCCESS;
    }

    private function flush(array &$batch): int
    {
        if (! $batch) {
            return 0;
        }
        $count = 0;
        if (! $this->option('dry-run')) {
            // Skip rows already imported from an overlapping log file.
            foreach ($batch as $row) {
                $exists = Visit::where('imported', true)
                    ->where('visitor_id', $row['visitor_id'])
                    ->where('created_at', $row['created_at'])
                    ->exists();
                if (! $exists) {
                    Visit::insert([$row]);
                    $count++;
                }
            }
        } else {
            $count = count($batch);
        }
        $batch = [];

        return $count;
    }
}
