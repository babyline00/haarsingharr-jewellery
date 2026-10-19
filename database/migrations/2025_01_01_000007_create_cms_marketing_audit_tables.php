<?php

/**
 * Haarsingharr — CMS, marketing, store settings and audit tables.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --------------------------------------------------------------------------
        // CMS & content
        // --------------------------------------------------------------------------
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('template')->default('default');
            $table->mediumText('content')->nullable();
            $table->json('sections')->nullable(); // flexible page builder blocks
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])
                ->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image')->nullable();
            $table->foreignId('created_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index('slug');
            $table->index(['status', 'published_at']);
        });

        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->mediumText('answer');
            $table->string('category')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('author_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->mediumText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])
                ->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['blog_post_id', 'blog_tag_id']);
        });

        // --------------------------------------------------------------------------
        // Homepage builder
        // --------------------------------------------------------------------------
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('key')->index();          // hero, featured, etc.
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->json('configuration')->nullable();
            $table->string('view')->nullable();        // blade view partial override
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->string('location')->default('hero'); // hero, mega_menu, footer, etc.
            $table->string('image');
            $table->string('mobile_image')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();
            $table->json('configuration')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['location', 'is_published']);
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('location')->default('main'); // main, footer, mobile
            $table->json('items')->nullable();
            $table->timestamps();
        });

        // --------------------------------------------------------------------------
        // Reviews & support
        // --------------------------------------------------------------------------
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->foreignId('order_id')->nullable()
                ->references('id')->on('orders')->nullOnDelete();
            $table->unsignedInteger('rating'); // 1-5
            $table->string('title')->nullable();
            $table->mediumText('body');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->text('admin_response')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('report_count')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'is_approved', 'rating']);
            $table->index('order_id');
        });

        Schema::create('review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->foreignId('answered_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('subject')->nullable();
            $table->mediumText('message');
            $table->enum('type', ['general', 'support', 'complaint', 'wholesale', 'press'])
                ->default('general');
            $table->enum('status', ['new', 'open', 'resolved', 'closed'])->default('new');
            $table->foreignId('assigned_to')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 80)->unique();
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->enum('status', ['pending', 'subscribed', 'unsubscribed', 'bounced'])
                ->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // --------------------------------------------------------------------------
        // Settings, locations, redirects
        // --------------------------------------------------------------------------
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->json('data')->nullable();
            $table->string('module')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });

        Schema::create('store_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('map_link')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('hours')->nullable();
            $table->boolean('is_main')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_url')->unique();
            $table->string('destination_url');
            $table->integer('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // --------------------------------------------------------------------------
        // Audit, security
        // --------------------------------------------------------------------------
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('actor_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('actor_email')->nullable();
            $table->string('action')->index();
            $table->string('module')->nullable()->index();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('properties')->nullable();
            $table->json('previous')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('outcome', ['success', 'failure', 'warning'])->default('success');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('event_type')->index();
            $table->enum('outcome', ['success', 'failure', 'warning', 'blocked'])
                ->default('failure');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->mediumText('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel'); // mail, sms, whatsapp, push
            $table->string('type');    // order_placed, low_stock, etc.
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'channel', 'type']);
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
            $table->index('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('active_sessions');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('store_locations');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('newsletter_subscriptions');
        Schema::dropIfExists('contact_enquiries');
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('review_images');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('cms_sections');
        Schema::dropIfExists('cms_pages');
    }
};
