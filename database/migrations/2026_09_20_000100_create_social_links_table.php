<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30)->index();
            $table->string('url');
            $table->string('label', 80)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Carry over the links already saved on the profile.
        $profile = DB::table('profiles')->first();
        if (! $profile) {
            return;
        }
        $now = now();
        $rows = array_filter([
            $profile->linkedin_url ? ['platform' => 'linkedin', 'url' => $profile->linkedin_url] : null,
            $profile->github_url ? ['platform' => 'github', 'url' => $profile->github_url] : null,
            ! empty($profile->twitter_handle) ? ['platform' => 'x', 'url' => 'https://x.com/'.ltrim($profile->twitter_handle, '@')] : null,
        ]);
        $i = 0;
        foreach ($rows as $row) {
            DB::table('social_links')->insert($row + ['sort_order' => $i++, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
