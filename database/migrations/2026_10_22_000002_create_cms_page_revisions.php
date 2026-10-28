<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('title');
            $table->mediumText('content')->nullable();
            $table->json('sections')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('snapshot')->nullable(); // full attribute snapshot for restore
            $table->timestamps();
            $table->index(['cms_page_id', 'created_at']);
        });

        // Optional staff preview of CMS pages/blog posts straight from the editor.
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('preview_token', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::drop('cms_page_revisions');

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('preview_token');
        });
    }
};
