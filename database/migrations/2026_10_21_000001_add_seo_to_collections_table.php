<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            if (! Schema::hasColumn('collections', 'seo_title')) {
                $table->string('seo_title')->nullable();
            }
            if (! Schema::hasColumn('collections', 'seo_description')) {
                $table->string('seo_description', 500)->nullable();
            }
            if (! Schema::hasColumn('collections', 'seo_image')) {
                $table->string('seo_image')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            if (Schema::hasColumn('collections', 'seo_title')) {
                $table->dropColumn('seo_title');
            }
            if (Schema::hasColumn('collections', 'seo_description')) {
                $table->dropColumn('seo_description');
            }
            if (Schema::hasColumn('collections', 'seo_image')) {
                $table->dropColumn('seo_image');
            }
        });
    }
};
