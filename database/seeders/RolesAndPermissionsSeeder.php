<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\HomepageSection;
use App\Models\NewsletterSubscription;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class RolesAndPermissionsSeeder extends Seeder
{
    private array $permissions = [];

    public function run(): void
    {
        $this->buildPermissionsList();
        $this->seedPermissions();
        $this->seedRoles();
        $this->ensureDefaultSettings();
    }

    private function buildPermissionsList(): void
    {
        $modules = [
            'products', 'categories', 'collections', 'inventory', 'orders',
            'payments', 'refunds', 'customers', 'coupons', 'reviews',
            'homepage', 'cms', 'seo', 'staff', 'roles', 'reports',
            'settings', 'shipping', 'audit', 'store_locations', 'newsletter',
            'contacts', 'exchanges', 'faqs', 'banners',
        ];

        $actions = ['view', 'create', 'update', 'delete', 'export', 'approve', 'moderate'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $this->permissions[$module][] = "{$module}.{$action}";
            }
        }

        // Module-specific extras
        $this->permissions['products'][]  = 'products.import';
        $this->permissions['products'][]  = 'products.adjust_cost';
        $this->permissions['orders'][]    = 'orders.ship';
        $this->permissions['orders'][]    = 'orders.cancel';
        $this->permissions['payments'][] = 'payments.configure';
        $this->permissions['refunds'][]   = 'refunds.approve';
        $this->permissions['settings'][]  = 'settings.edit_sensitive';
        $this->permissions['audit'][]     = 'audit.view_sensitive';
        $this->permissions['staff'][]     = 'staff.manage_roles';
        $this->permissions['staff'][]     = 'staff.export';
    }

    private function seedPermissions(): void
    {
        foreach ($this->permissions as $module => $permissions) {
            foreach ($permissions as $permission) {
                [$moduleName, $action] = explode('.', $permission);
                Permission::firstOrCreate(
                    ['name' => $permission, 'slug' => $permission],
                    [
                        'guard_name' => 'web',
                        'module'     => $moduleName,
                        'action'     => $action,
                        'description'=> ucfirst($action)." {$moduleName}",
                    ]
                );
            }
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            'super-admin'      => ['Super Admin', 'Unrestricted system administrator.'],
            'store-admin'      => ['Store Admin', 'Day-to-day operations administrator.'],
            'product-manager'  => ['Product Manager', 'Manages products, categories, inventory.'],
            'inventory-manager'=> ['Inventory Manager', 'Manages stock, reservations.'],
            'order-manager'    => ['Order Manager', 'Manages orders, shipments, refunds.'],
            'customer-support' => ['Customer Support', 'Reviews customer enquiries, limited order view.'],
            'marketing-manager'=> ['Marketing Manager', 'Coupons, banners, homepage, blog.'],
            'report-viewer'    => ['Report Viewer', 'Read-only access to approved reports.'],
            'customer'         => ['Customer', 'Frontend customer role.'],
        ];

        foreach ($roles as $slug => [$title, $description]) {
            Role::firstOrCreate(
                ['slug' => $slug, 'name' => $slug],
                [
                    'title'       => $title,
                    'description' => $description,
                    'guard_name'  => 'web',
                    'is_system'   => true,
                    'is_default'  => $slug === 'customer',
                    'sort_order'  => array_search($slug, array_keys($roles)),
                ]
            );
        }

        // Assign permissions
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // Store Admin — everything except staff/roles management
        $storeAdminPerms = Permission::whereNotIn('module', ['staff', 'roles', 'audit'])
            ->whereNotIn('slug', ['settings.edit_sensitive', 'payments.configure', 'refunds.approve'])
            ->pluck('id');
        Role::where('slug', 'store-admin')->first()->permissions()->sync($storeAdminPerms);

        // Product Manager
        $productPerms = Permission::whereIn('module', ['products', 'categories', 'collections', 'inventory'])
            ->whereNotIn('slug', ['products.adjust_cost'])
            ->pluck('id');
        Role::where('slug', 'product-manager')->first()->permissions()->sync($productPerms);

        // Inventory Manager
        $inventoryPerms = Permission::whereIn('module', ['inventory'])->pluck('id');
        Role::where('slug', 'inventory-manager')->first()->permissions()->sync($inventoryPerms);

        // Order Manager
        $orderPerms = Permission::whereIn('module', ['orders', 'payments', 'refunds', 'shipments', 'exchanges'])
            ->whereIn('slug', [
                'orders.view', 'orders.update', 'orders.ship', 'orders.cancel', 'orders.export',
                'payments.view', 'refunds.view', 'refunds.create', 'refunds.approve', 'refunds.update',
                'exchanges.view', 'exchanges.update',
            ])
            ->pluck('id');
        Role::where('slug', 'order-manager')->first()->permissions()->sync($orderPerms);

        // Customer Support
        $supportPerms = Permission::whereIn('slug', [
            'orders.view', 'customers.view', 'customers.update',
            'reviews.view', 'reviews.moderate', 'contacts.view', 'contacts.update',
        ])->pluck('id');
        Role::where('slug', 'customer-support')->first()->permissions()->sync($supportPerms);

        // Marketing Manager
        $marketingPerms = Permission::whereIn('module', [
            'coupons', 'banners', 'homepage', 'cms', 'newsletter', 'reviews',
        ])->whereNotIn('slug', ['reviews.delete'])
            ->pluck('id');
        Role::where('slug', 'marketing-manager')->first()->permissions()->sync($marketingPerms);

        // Report Viewer — read-only access to approved reports
        $reportPerms = Permission::whereIn('module', ['reports'])
            ->whereIn('action', ['view', 'export'])
            ->pluck('id');
        Role::where('slug', 'report-viewer')->first()->permissions()->sync($reportPerms);
    }

    private function ensureDefaultSettings(): void
    {
        StoreSetting::firstOrCreate(
            ['key' => 'currency_symbol'],
            ['value' => 'Rs.', 'module' => 'general']
        );

        StoreSetting::firstOrCreate(
            ['key' => 'currency'],
            ['value' => 'PKR', 'module' => 'general']
        );

        StoreSetting::firstOrCreate(
            ['key' => 'whatsapp_number'],
            ['value' => env('CONTACT_WHATSAPP', '+92 300 0000000'), 'module' => 'contact']
        );
    }
}
