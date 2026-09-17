<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->char('visitor_id', 64)->index();       // hashed first-party cookie
            $table->boolean('is_new')->default(true);
            $table->string('path', 255)->default('/');

            $table->string('referrer', 500)->nullable();
            $table->string('referrer_host', 120)->nullable()->index();
            $table->string('source', 80)->nullable()->index();   // Google, Instagram, Direct…
            $table->string('medium', 30)->nullable()->index();   // search, social, referral, direct, campaign
            $table->string('utm_source', 80)->nullable();
            $table->string('utm_medium', 80)->nullable();
            $table->string('utm_campaign', 120)->nullable()->index();

            $table->string('ip', 45)->nullable();                  // anonymised
            $table->char('country_code', 2)->nullable()->index();
            $table->string('country', 80)->nullable();
            $table->string('region', 80)->nullable();
            $table->string('city', 80)->nullable()->index();
            $table->decimal('latitude', 8, 4)->nullable();
            $table->decimal('longitude', 8, 4)->nullable();
            $table->string('timezone', 64)->nullable();

            $table->string('language', 10)->nullable()->index();  // "en"
            $table->string('locale', 20)->nullable();             // "en-US"
            $table->string('languages', 120)->nullable();         // Accept-Language

            $table->string('browser', 40)->nullable();
            $table->string('browser_version', 10)->nullable();
            $table->string('os', 20)->nullable();
            $table->string('device', 10)->nullable()->index();    // desktop, mobile, tablet, bot
            $table->string('screen', 12)->nullable();             // "1440x900"
            $table->smallInteger('viewport_width')->unsigned()->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->boolean('is_bot')->default(false)->index();
            $table->boolean('is_human')->default(false);          // confirmed by the browser script
            $table->unsignedInteger('duration')->nullable();      // active seconds on page
            $table->unsignedTinyInteger('scroll_depth')->nullable();
            $table->boolean('imported')->default(false);          // from server access logs

            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('visit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30)->index();                  // cv_download, social_click, section…
            $table->string('target', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_events');
        Schema::dropIfExists('visits');
    }
};
