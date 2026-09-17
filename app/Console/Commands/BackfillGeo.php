<?php

namespace App\Console\Commands;

use App\Models\Visit;
use App\Support\Analytics\GeoIp;
use Illuminate\Console\Command;

class BackfillGeo extends Command
{
    protected $signature = 'analytics:backfill-geo';

    protected $description = 'Add city & country to stored visits that have no location yet';

    public function handle(): int
    {
        if (! GeoIp::available()) {
            $this->error('No GeoIP database. Run: php artisan analytics:geoip');

            return self::FAILURE;
        }

        $done = 0;
        Visit::whereNull('country_code')->whereNotNull('ip')->chunkById(500, function ($visits) use (&$done) {
            foreach ($visits as $visit) {
                // Stored IPs are anonymised; city accuracy is the same for the /24 network.
                $geo = GeoIp::locate($visit->ip);
                if ($geo['country_code']) {
                    $visit->forceFill($geo)->saveQuietly();
                    $done++;
                }
            }
        });

        $this->info("Located $done visits.");

        return self::SUCCESS;
    }
}
