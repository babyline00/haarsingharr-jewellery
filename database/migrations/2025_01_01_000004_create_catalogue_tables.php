<?php

/**
 * Haarsingharr — Catalogue: categories, collections, products, variants, images, tags.
 * Categories support unlimited nesting via `parent_id` with a nested-set
 * inspired `left`/`right` to optimise tree reads; the path column simplifies
 * breadcrumb rendering. The seeder keeps these in sync.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('parent_id')->nullable()
                ->references('id')->on('categories')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('path', 500)->nullable(); // /rings/diamond
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('icon')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('depth')->default(0);
            $table->unsignedInteger('left')->default(0);
            $table->unsignedInteger('right')->default(0);

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image')->nullable();

            $table->boolean('show_in_menu')->default(true);
            $table->boolean('show_on_homepage')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('status');
            $table->index('path');
            $table->index(['show_in_menu', 'sort_order']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            $table->enum('type', ['manual', 'rule_based'])->default('manual');
            $table->json('rules')->nullable(); // category, tags, price_range, status
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('match_condition', ['all', 'any'])->default('all');
            $table->boolean('show_on_homepage')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('show_on_homepage');
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['collection_id', 'product_id']);
        });

        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'product_tag_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('category_id')->nullable()
                ->references('id')->on('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()
                ->references('id')->on('categories')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable();

            $table->text('short_description')->nullable();
            $table->mediumText('description')->nullable();

            // Pricing — products carry "starting from" price for display only
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();

            // Status & flags
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->boolean('is_bridal')->default(false);
            $table->boolean('is_men')->default(false);
            $table->boolean('allow_reviews')->default(true);
            $table->boolean('allow_backorders')->default(false);

            // Physical attributes
            $table->decimal('weight_grams', 10, 2)->nullable();
            $table->string('metal')->nullable();           // Gold 22k, Silver 925, Platinum
            $table->string('gemstone')->nullable();
            $table->decimal('carat', 8, 3)->nullable();
            $table->string('size')->nullable();
            $table->string('colour')->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->string('material')->nullable();
            $table->text('care_instructions')->nullable();

            // Variant configuration
            $table->boolean('has_variants')->default(false);
            $table->json('variant_attributes')->nullable(); // e.g. ["metal","size"]

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image')->nullable();
            $table->string('canonical_url')->nullable();

            // Tracking
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('units_sold')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Relations metadata
            $table->foreignId('created_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
            $table->index(['is_featured', 'status']);
            $table->index(['is_best_seller', 'status']);
            $table->index(['is_new_arrival', 'status']);
            $table->index('regular_price');
            $table->index('sale_price');
            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('created_at');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable();
            $table->string('title')->nullable(); // e.g. "22k Gold — Size 14"

            // Variant attributes (one row per attribute key, JSON-encoded)
            $table->json('attributes')->nullable(); // {"metal":"Gold","size":"14"}

            // Pricing
            $table->decimal('regular_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();

            // Inventory
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->unsignedInteger('safety_stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('is_default')->default(false);

            // Status
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');

            // Dimensions
            $table->decimal('weight_grams', 10, 2)->nullable();
            $table->string('dimensions', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('sku');
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'is_default']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->references('id')->on('product_variants')->cascadeOnDelete();

            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('card_path')->nullable();
            $table->string('gallery_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->enum('type', ['gallery', 'hero', 'lifestyle', 'swatch'])->default('gallery');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('relation_type', ['related', 'upsell', 'cross_sell', 'similar'])
                ->default('related');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'related_product_id', 'relation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('categories');
    }
};
