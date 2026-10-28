<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only staff notes shown on the order timeline.
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        // Refund fulfilment metadata. A full refund may optionally put the
        // returned pieces back into sellable stock exactly once.
        Schema::table('refunds', function (Blueprint $table) {
            $table->boolean('restock_items')->default(false)->after('notes');
            $table->timestamp('restocked_at')->nullable()->after('processed_at');
        });

        // Exchange/return receipt and stock-return milestones.
        Schema::table('exchange_requests', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('refund_id');
            $table->timestamp('restocked_at')->nullable()->after('received_at');
            $table->timestamp('completed_at')->nullable()->after('restocked_at');
            $table->string('return_label_url', 500)->nullable()->after('completed_at');
        });

        // Delivery estimate, notes and cancellation-safe shipment editing.
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('estimated_delivery_at')->nullable()->after('delivered_at');
            $table->text('notes')->nullable()->after('estimated_delivery_at');
        });

        // Zone-based shipping rates. The legacy flat/express settings remain
        // as a fallback while zones are being rolled out.
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name', 120);
            $table->string('code', 60)->unique();
            $table->json('countries')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('method', 60)->default('standard');
            $table->decimal('min_order_total', 14, 2)->nullable();
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('free_over', 14, 2)->nullable();
            $table->decimal('min_weight_grams', 10, 2)->nullable();
            $table->decimal('max_weight_grams', 10, 2)->nullable();
            $table->unsignedInteger('eta_min_days')->nullable();
            $table->unsignedInteger('eta_max_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['shipping_zone_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['estimated_delivery_at', 'notes']);
        });

        Schema::table('exchange_requests', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'restocked_at', 'completed_at', 'return_label_url']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['restock_items', 'restocked_at']);
        });

        Schema::dropIfExists('order_notes');
    }
};
