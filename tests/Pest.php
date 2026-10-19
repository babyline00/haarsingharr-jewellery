<?php

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
|
| Helpers used across Pest tests. They wrap common assertions and
| seeders to keep test files readable.
|
*/

if (! function_exists('seedRoles')) {
    function seedRoles(): void
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
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['slug' => $slug, 'name' => $slug],
                ['title' => $title, 'guard_name' => 'web', 'is_system' => true]
            );
        }
    }
}

if (! function_exists('createCustomer')) {
    function createCustomer(array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create($attributes);
        $user->assignRole('customer');
        return $user;
    }
}

if (! function_exists('createAdmin')) {
    function createAdmin(array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->admin()->create($attributes);
        $user->assignRole('super-admin');
        return $user;
    }
}
