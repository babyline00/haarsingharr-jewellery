<?php

/**
 * Haarsingharr — Roles & Permissions (Spatie schema, but with Haarsingharr's required
 * permission slug naming: <module>.<action>). All eight default roles ship
 * through the RolesAndPermissionsSeeder.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('guard_name')->default('web');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // system roles cannot be deleted
            $table->boolean('is_default')->default(false); // assigned to new customers
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('guard_name')->default('web');
            $table->string('module')->index();   // products, orders, customers, etc.
            $table->string('action')->index();   // view, create, update, delete, approve...
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('guard_name')->default('web');
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
            $table->index(['model_type', 'user_id']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('guard_name')->default('web');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        // User-level override (grant permission to specific user beyond role)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('guard_name')->default('web');
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
            $table->index(['model_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
