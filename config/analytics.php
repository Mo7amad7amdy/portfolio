<?php

return [
    // Record visits to the public site.
    'enabled' => env('ANALYTICS_ENABLED', true),

    // Visits from these IPs are never recorded (comma separated), e.g. your office.
    'ignore_ips' => array_filter(array_map('trim', explode(',', (string) env('ANALYTICS_IGNORE_IPS', '')))),

    // Skip visitors that send "Do Not Track" or "Global Privacy Control".
    'respect_dnt' => env('ANALYTICS_RESPECT_DNT', false),

    // IPs are stored anonymised (last IPv4 octet / last 80 IPv6 bits removed).
    // Set to false to not store any part of the IP.
    'store_ip' => env('ANALYTICS_STORE_IP', true),

    // Local MaxMind-format city database (DB-IP Lite or GeoLite2-City).
    'geoip_path' => env('GEOIP_PATH', storage_path('app/geoip/city.mmdb')),

    // Timezone used for charts and tables in the dashboard.
    'timezone' => env('ANALYTICS_TIMEZONE', 'Africa/Cairo'),
];
