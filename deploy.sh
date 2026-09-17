#!/usr/bin/env bash
# Run on the server after uploading new code:  bash deploy.sh
set -e
cd "$(dirname "$0")"
php artisan down --retry=15 || true
[ -f composer.phar ] && php composer.phar install --no-dev --optimize-autoloader || composer install --no-dev --optimize-autoloader || true
php artisan migrate --force
[ -f storage/app/geoip/city.mmdb ] || php artisan analytics:geoip || echo "GeoIP download failed - see README (Analytics)"
php artisan optimize:clear
php artisan optimize
php artisan up
echo "Deployed."
