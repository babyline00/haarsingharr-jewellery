<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the roles on every test so we don't need to do it manually.
        if (! Role::where('slug', 'super-admin')->exists()) {
            $this->seedRoles();
        }
    }

    protected function seedRoles(): void
    {
        $roles = [
            ['super-admin', 'Super Admin'],
            ['store-admin', 'Store Admin'],
            ['product-manager', 'Product Manager'],
            ['inventory-manager', 'Inventory Manager'],
            ['order-manager', 'Order Manager'],
            ['customer-support', 'Customer Support'],
            ['marketing-manager', 'Marketing Manager'],
            ['report-viewer', 'Report Viewer'],
            ['customer', 'Customer'],
        ];
        foreach ($roles as [$slug, $title]) {
            Role::firstOrCreate(
                ['slug' => $slug, 'name' => $slug],
                ['title' => $title, 'guard_name' => 'web', 'is_system' => true]
            );
        }
    }

    protected function seedRolesAndPermissions(): void
    {
        $this->seedRoles();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }
}
