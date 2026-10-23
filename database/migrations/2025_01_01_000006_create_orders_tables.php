<?php

/**
 * Haarsingharr — Cart, wishlists, orders, payments, refunds, shipments.
 * Order item snapshots preserve price/SKU at order time so historical
 * orders stay correct after products are edited or deleted.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --------------------------------------------------------------------------
        // Carts
        // --------------------------------------------------------------------------
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('code', 40)->unique();
            $table->string('slug')->unique();

            $table->enum('type', ['percentage', 'fixed', 'free_shipping',
                'product_specific', 'category_specific'])
                ->default('fixed');
            $table->decimal('value', 12, 2);
            $table->decimal('min_order_value', 12, 2)->nullable();
            $table->decimal('max_discount_amount', 12, 2)->nullable();

            $table->foreignId('product_id')->nullable()
                ->references('id')->on('products')->nullOnDelete();
            $table->foreignId('category_id')->nullable()
                ->references('id')->on('categories')->nullOnDelete();

            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            $table->boolean('first_order_only')->default(false);
            $table->boolean('exclude_sale_items')->default(false);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'scheduled', 'expired', 'disabled'])
                ->default('active');
            $table->softDeletes();
            $table->timestamps();
            $table->index('code');
            $table->index('status');
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('currency', 3)->default('PKR');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()
                ->references('id')->on('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->references('id')->on('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);     // snapshot at add time
            $table->decimal('line_total', 12, 2);
            $table->json('metadata')->nullable();      // gift wrap, engraving, etc.
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id']);
        });

        // --------------------------------------------------------------------------
        // Wishlists
        // --------------------------------------------------------------------------
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('name')->default('Saved Items');
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->references('id')->on('product_variants')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['wishlist_id', 'product_id']);
        });

        // --------------------------------------------------------------------------
        // Orders
        // --------------------------------------------------------------------------
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('number', 16)->unique();   // human-readable AU-2025-000001

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 20)->nullable();

            $table->enum('status', ['pending', 'confirmed', 'processing', 'packed',
                'shipped', 'delivered', 'cancelled', 'returned',
                'partially_fulfilled'])
                ->default('pending')->index();
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed',
                'partially_refunded', 'refunded'])
                ->default('unpaid')->index();
            $table->enum('fulfilment_status', ['unfulfilled', 'partial', 'fulfilled'])
                ->default('unfulfilled');

            $table->string('currency', 3)->default('PKR');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('shipping_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('rounding', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);

            $table->foreignId('coupon_id')->nullable()
                ->references('id')->on('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 14, 2)->default(0);

            $table->string('shipping_method')->nullable();
            $table->string('courier')->nullable();
            $table->string('tracking_number', 80)->nullable();
            $table->string('tracking_url')->nullable();

            $table->string('payment_method', 40)->nullable();
            $table->string('payment_provider', 40)->nullable();
            $table->string('payment_provider_ref', 80)->nullable();

            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->string('locale', 5)->default('en');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('order_channel', 20)->default('web');

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->string('tracking_token', 64)->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index('guest_email');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->references('id')->on('product_variants')->nullOnDelete();

            // Immutable snapshots
            $table->string('product_name');
            $table->string('product_slug')->nullable();
            $table->string('sku')->nullable();
            $table->string('variant_title')->nullable();
            $table->json('variant_attributes')->nullable();
            $table->string('product_image')->nullable();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('line_total', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->unsignedInteger('refunded_quantity')->default(0);

            $table->enum('fulfilment_status', ['unfulfilled', 'packed', 'shipped',
                'delivered', 'returned', 'cancelled'])
                ->default('unfulfilled');

            $table->timestamps();
            $table->index('product_id');
            $table->index('product_variant_id');
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping']);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_country', 5)->default('+92');
            $table->string('phone', 20);
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city');
            $table->string('province');
            $table->string('country', 2);
            $table->string('postal_code', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->text('note')->nullable();
            $table->boolean('customer_notified')->default(false);
            $table->foreignId('actor_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        // --------------------------------------------------------------------------
        // Coupons
        // --------------------------------------------------------------------------
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()
                ->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('discount_applied', 40);
            $table->decimal('amount', 14, 2);
            $table->timestamps();
            $table->index(['coupon_id', 'order_id']);
        });

        // --------------------------------------------------------------------------
        // Payments & refunds
        // --------------------------------------------------------------------------
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 40);   // cod, jazzcash, easypaisa, stripe
            $table->string('method', 40)->nullable(); // card, wallet, etc.
            $table->enum('status', ['pending', 'initiated', 'authorized', 'captured',
                'failed', 'cancelled', 'refunded', 'partially_refunded'])
                ->default('pending')->index();

            $table->decimal('amount', 14, 2);
            $table->decimal('amount_captured', 14, 2)->default(0);
            $table->decimal('amount_refunded', 14, 2)->default(0);
            $table->string('currency', 3)->default('PKR');

            $table->string('provider_transaction_ref', 80)->nullable()->index();
            $table->string('provider_payment_id', 80)->nullable()->index();
            $table->string('provider_merchant_id', 80)->nullable();
            $table->string('provider_status', 40)->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('provider_response')->nullable();

            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['initiated', 'authorized', 'captured', 'failed',
                'refunded', 'partial_refunded', 'webhook', 'verification', 'expired'])
                ->default('initiated');
            $table->string('provider_event_id', 120)->nullable()->index();
            $table->string('status', 40)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('payload')->nullable();
            $table->string('signature', 128)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->enum('type', ['full', 'partial'])->default('partial');
            $table->enum('status', ['pending', 'approved', 'processing',
                'completed', 'failed', 'cancelled'])
                ->default('pending');

            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('provider_ref', 80)->nullable();

            $table->foreignId('approved_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier', 80);
            $table->string('method', 80)->nullable();
            $table->string('tracking_number', 80);
            $table->string('tracking_url')->nullable();
            $table->enum('status', ['pending', 'picked_up', 'in_transit', 'out_for_delivery',
                'delivered', 'failed', 'returned'])
                ->default('pending')->index();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('weight_grams', 10, 2)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('exchange_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('number', 16)->unique();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()
                ->references('id')->on('order_items')->nullOnDelete();

            $table->enum('type', ['exchange', 'return', 'refund'])->default('return');
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected',
                'completed', 'cancelled'])
                ->default('pending')->index();

            $table->text('reason');
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->foreignId('requested_variant_id')->nullable()
                ->references('id')->on('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('refund_amount', 14, 2)->nullable();
            $table->foreignId('refund_id')->nullable()
                ->references('id')->on('refunds')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_requests');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
    }
};
