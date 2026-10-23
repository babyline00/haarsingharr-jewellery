<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gift popup claims/analytics and admin giveaway draws.
 *
 * A "gift claim" records one issued popup prize. Unique coupons are created
 * with a single-use limit, so the coupon itself is the source of truth for
 * redemption while the claim provides attribution for analytics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_claims', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable();
            $table->string('prize');
            $table->string('mode', 20)->default('scratch');
            $table->string('status', 20)->default('issued')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('claimed_at')->nullable()->index();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'claimed_at']);
            $table->index(['user_id', 'claimed_at']);
        });

        Schema::create('gift_popup_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 20)->index();
            $table->string('session_id', 100)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gift_claim_id')->nullable()->constrained('gift_claims')->nullOnDelete();
            $table->string('mode', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });

        Schema::create('giveaways', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('prize');
            $table->string('coupon_type', 20)->default('percentage');
            $table->decimal('coupon_value', 12, 2)->default(10);
            $table->decimal('coupon_min_order', 12, 2)->nullable();
            $table->unsignedInteger('coupon_expiry_days')->default(30);
            $table->unsignedInteger('winners_count')->default(1);
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('draw_at')->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('giveaway_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giveaway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_claim_id')->nullable()->constrained('gift_claims')->nullOnDelete();
            $table->boolean('is_winner')->default(false);
            $table->timestamp('drawn_at')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['giveaway_id', 'user_id']);
            $table->index(['giveaway_id', 'is_winner']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giveaway_entries');
        Schema::dropIfExists('giveaways');
        Schema::dropIfExists('gift_popup_events');
        Schema::dropIfExists('gift_claims');
    }
};
