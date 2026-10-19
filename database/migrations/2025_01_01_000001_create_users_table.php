<?php

/**
 * Haarsingharr Jewellery — User table (customers + staff share this table; staff are
 * differentiated by the spatie permission roles and a "type" column).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();

            // Identity
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->virtualAs('CONCAT(first_name, " ", last_name)');
            $table->string('email')->unique();
            $table->string('phone_country', 5)->default('+92');
            $table->string('phone', 20)->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable(); // nullable to support staff invites
            $table->string('avatar')->nullable();

            // Type/Status
            $table->enum('type', ['customer', 'staff', 'admin'])->default('customer');
            $table->enum('status', ['active', 'suspended', 'invited', 'deactivated'])
                ->default('active');

            // Verification
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            // Security
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            // Consent & marketing
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('marketing_consent_at')->nullable();
            $table->json('consent_log')->nullable(); // history of consent toggles

            // Privacy
            $table->timestamp('data_export_requested_at')->nullable();
            $table->timestamp('data_deleted_at')->nullable();
            $table->softDeletes();

            $table->rememberToken();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('phone');
            $table->index('last_login_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->index('email');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
