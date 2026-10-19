<?php

/**
 * Haarsingharr — Inventory ledger & stock reservations.
 * Stock reservations are released atomically when they expire or the order is
 * confirmed. Inventory movements are append-only and immutable after insert.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->references('id')->on('users')->nullOnDelete();
            $table->enum('status', ['draft', 'sent', 'partial', 'received', 'cancelled'])
                ->default('draft');
            $table->date('expected_at')->nullable();
            $table->date('received_at')->nullable();
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->references('id')->on('product_variants')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('unit_cost', 12, 2);
            $table->unsignedInteger('quantity_ordered');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('product_variant_id')->nullable()
                ->references('id')->on('product_variants')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()
                ->references('id')->on('products')->cascadeOnDelete();

            $table->enum('type', ['purchase', 'adjustment', 'return_in',
                'sale_out', 'reservation', 'release', 'restock', 'damage', 'transfer'])
                ->index();
            $table->enum('direction', ['in', 'out', 'none'])->default('none');

            $table->integer('quantity_change');       // signed
            $table->unsignedInteger('previous_stock');
            $table->unsignedInteger('new_stock');
            $table->unsignedInteger('reserved_change')->default(0);
            $table->unsignedInteger('previous_reserved')->default(0);
            $table->unsignedInteger('new_reserved')->default(0);

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            // Polymorphic reference: order, purchase order, manual adjustment, etc.
            $table->morphs('reference');

            $table->foreignId('actor_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->index('created_at');
            $table->index('type');
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('product_variant_id')
                ->references('id')->on('product_variants')->cascadeOnDelete();

            $table->unsignedInteger('quantity');

            // Polymorphic owner: cart, order, manual hold
            $table->morphs('reservable');

            $table->timestamp('expires_at')->index();
            $table->timestamp('released_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'released_at']);
        });

        // Low-stock alerts — de-normalised snapshot for the dashboard.
        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->references('id')->on('product_variants')->cascadeOnDelete();
            $table->enum('type', ['low_stock', 'out_of_stock', 'overstock'])->index();
            $table->unsignedInteger('threshold');
            $table->unsignedInteger('current_stock');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['is_resolved', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
