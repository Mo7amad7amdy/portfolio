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
            // Same portrait with the eyes closed; used for a realistic blink.
            $table->string('hero_closed')->nullable()->after('hero_depth');
        });

        DB::table('profiles')
            ->where('hero_portrait', 'images/portrait/portrait.webp')
            ->whereNull('hero_closed')
            ->update(['hero_closed' => 'images/portrait/portrait-closed.webp']);
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('hero_closed');
        });
    }
};
