<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('seo_title', 70)->nullable()->after('open_to_work');
            $table->string('seo_description', 170)->nullable()->after('seo_title');
            $table->string('seo_image')->nullable()->after('seo_description'); // custom share image (optional)
            $table->string('twitter_handle', 32)->nullable()->after('seo_image');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'seo_image', 'twitter_handle']);
        });
    }
};
