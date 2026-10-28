<?php

/**
 * Haarsingharr — Phase 4 additions:
 *  - Customer segments need tags for CRM-style grouping.
 *  - Banner click tracking (impressions/clicks) + alt text for accessibility.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('internal_notes');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('subheading');
            $table->unsignedInteger('impressions')->default(0)->after('alt_text');
            $table->unsignedInteger('clicks')->default(0)->after('impressions');
            $table->index('clicks');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex(['clicks']);
            $table->dropColumn(['alt_text', 'impressions', 'clicks']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
