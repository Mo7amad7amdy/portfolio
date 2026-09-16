<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Stat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $lastmod = collect([
            Profile::max('updated_at'), Experience::max('updated_at'), Project::max('updated_at'),
            Skill::max('updated_at'), Stat::max('updated_at'),
        ])->filter()->max();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .'  <url>'."\n"
            .'    <loc>'.e(url('/')).'</loc>'."\n"
            .($lastmod ? '    <lastmod>'.\Illuminate\Support\Carbon::parse($lastmod)->toAtomString().'</lastmod>'."\n" : '')
            .'    <changefreq>monthly</changefreq>'."\n"
            .'    <priority>1.0</priority>'."\n"
            .'  </url>'."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function manifest(): JsonResponse
    {
        $profile = Profile::current();

        return response()->json([
            'name' => "{$profile->name} — {$profile->title}",
            'short_name' => $profile->name,
            'start_url' => url('/'),
            'display' => 'browser',
            'background_color' => '#e6e4df',
            'theme_color' => '#e6e4df',
            'icons' => [
                ['src' => asset('images/icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('images/icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
