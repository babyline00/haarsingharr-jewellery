<?php

/**
 * Haarsingharr — Customer addresses. Soft-deletes are used so historical orders
 * keep their address snapshots, but customer accounts can manage their own.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('label')->nullable(); // e.g. "Home", "Office"
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_country', 5)->default('+92');
            $table->string('phone', 20);

            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('landmark')->nullable();

            $table->string('city');
            $table->string('province');          // Punjab, Sindh, Islamabad, KPK, etc.
            $table->string('country', 2)->default('PK');
            $table->string('postal_code', 10)->nullable();

            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);

            $table->json('metadata')->nullable(); // delivery notes, gate code, etc.
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'is_default_shipping']);
            $table->index(['user_id', 'is_default_billing']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
