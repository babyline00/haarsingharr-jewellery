<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product videos / reels.
 *
 * Stored separately from product_images because the catalogue image table
 * intentionally restricts its `type` enum to image roles and many hosts run
 * MySQL where widening that enum is awkward. A dedicated table keeps video
 * uploads portable and gives reels their own ordering / primary flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('title')->nullable();
            $table->enum('type', ['video', 'reel'])->default('video');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_videos');
    }
};
