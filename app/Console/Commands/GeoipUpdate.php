<?php

namespace App\Console\Commands;

use App\Support\Analytics\GeoIp;
use App\Support\Analytics\MmdbReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeoipUpdate extends Command
{
    protected $signature = 'analytics:geoip
        {--file= : Use a .mmdb or .mmdb.gz file you downloaded yourself (DB-IP City Lite or GeoLite2-City)}
        {--backfill : Also add locations to visits that have none}';

    protected $description = 'Download the free DB-IP city database used to find visitor city & country';

    public function handle(): int
    {
        $target = GeoIp::path();
        @mkdir(dirname($target), 0755, true);
        $tmp = $target.'.download';

        try {
            if ($file = $this->option('file')) {
                $this->unpack($file, $tmp);
            } else {
                $this->download($tmp);
            }

            $reader = new MmdbReader($tmp);
            $this->info('Database OK: '.($reader->metadata()['database_type'] ?? 'unknown').', built '.date('Y-m-d', (int) ($reader->metadata()['build_epoch'] ?? time())));
            unset($reader);

            rename($tmp, $target);
            GeoIp::reset();
        } catch (Throwable $e) {
            @unlink($tmp);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Saved to $target");

        if ($this->option('backfill')) {
            $this->call('analytics:backfill-geo');
        }

        return self::SUCCESS;
    }

    private function download(string $tmp): void
    {
        $gz = $tmp.'.gz';
        // DB-IP publishes a new free file each month; fall back to last month early in the month.
        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = 'https://download.db-ip.com/free/dbip-city-lite-'.$month->format('Y-m').'.mmdb.gz';
            $this->line("Downloading $url …");
            $res = Http::timeout(600)->withOptions(['sink' => $gz])->get($url);
            if ($res->successful() && filesize($gz) > 1_000_000) {
                $this->unpack($gz, $tmp);
                @unlink($gz);

                return;
            }
            @unlink($gz);
        }
        throw new \RuntimeException('Download failed. Download "IP to City Lite (MMDB)" from https://db-ip.com/db/download/ip-to-city-lite and run: php artisan analytics:geoip --file=path/to/file.mmdb.gz');
    }

    private function unpack(string $source, string $dest): void
    {
        if (! is_file($source)) {
            throw new \RuntimeException("File not found: $source");
        }
        if (! str_ends_with(strtolower($source), '.gz')) {
            copy($source, $dest);

            return;
        }
        $in = gzopen($source, 'rb');
        $out = fopen($dest, 'wb');
        while (! gzeof($in)) {
            fwrite($out, gzread($in, 1 << 20));
        }
        gzclose($in);
        fclose($out);
    }
}
