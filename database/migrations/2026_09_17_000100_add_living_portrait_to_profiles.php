<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('hero_portrait')->nullable()->after('hero_body');
            $table->string('hero_depth')->nullable()->after('hero_portrait');
            $table->json('hero_meta')->nullable()->after('hero_depth');
        });

        // Existing installs get the bundled living portrait straight away.
        DB::table('profiles')->whereNull('hero_portrait')->update([
            'hero_portrait' => 'images/portrait/portrait.webp',
            'hero_depth' => 'images/portrait/portrait-depth.png',
            'hero_meta' => json_encode([
                'version' => 1,
                'focus' => 0.36,
                'eyes' => [
                    ['x' => 0.3547, 'y' => 0.2539, 'rx' => 0.0446, 'ry' => 0.0136],
                    ['x' => 0.5330, 'y' => 0.2596, 'rx' => 0.0463, 'ry' => 0.0136],
                ],
                'head' => ['x' => 0.4501, 'y' => 0.2710, 'rx' => 0.2674, 'ry' => 0.2996],
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['hero_portrait', 'hero_depth', 'hero_meta']);
        });
    }
};
