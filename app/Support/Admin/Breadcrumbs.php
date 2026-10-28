<?php

namespace App\Support\Admin;

use Illuminate\Support\Str;

class Breadcrumbs
{
    /**
     * [section, label, index route] for every admin module.
     */
    private const SECTIONS = [
        'products' => ['Catalogue', 'Products', 'admin.products.index'],
        'categories' => ['Catalogue', 'Categories', 'admin.categories.index'],
        'collections' => ['Catalogue', 'Collections', 'admin.collections.index'],
        'inventory' => ['Catalogue', 'Inventory', 'admin.inventory.index'],
        'orders' => ['Sales', 'Orders', 'admin.orders.index'],
        'payments' => ['Sales', 'Payments', 'admin.payments.index'],
        'refunds' => ['Sales', 'Refunds', 'admin.refunds.index'],
        'exchanges' => ['Sales', 'Exchanges & returns', 'admin.exchanges.index'],
        'customers' => ['Customers', 'Customers', 'admin.customers.index'],
        'users' => ['Customers', 'Users', 'admin.users.index'],
        'reviews' => ['Customers', 'Reviews', 'admin.reviews.index'],
        'coupons' => ['Marketing', 'Coupons', 'admin.coupons.index'],
        'banners' => ['Marketing', 'Banners', 'admin.banners.index'],
        'homepage-sections' => ['Marketing', 'Homepage builder', 'admin.homepage-sections.index'],
        'pages' => ['Content', 'CMS pages', 'admin.pages.index'],
        'faqs' => ['Content', 'FAQs', 'admin.faqs.index'],
        'staff' => ['System', 'Staff', 'admin.staff.index'],
        'roles' => ['System', 'Roles & permissions', 'admin.roles.index'],
        'reports' => ['System', 'Reports', 'admin.reports.sales'],
        'settings' => ['System', 'Store settings', 'admin.settings.index'],
        'database' => ['System', 'Database', 'admin.database.info'],
        'audit-logs' => ['System', 'Audit logs', 'admin.audit-logs.index'],
        'security-events' => ['System', 'Security events', 'admin.security-events.index'],
        'active-sessions' => ['System', 'Active sessions', 'admin.active-sessions.index'],
        'store-locations' => ['System', 'Store locations', 'admin.store-locations.index'],
        'shipping' => ['System', 'Shipping', 'admin.shipping.index'],
        'notifications' => [null, 'Notifications', null],
        'search' => [null, 'Search', null],
    ];

    public static function forCurrentRoute(): array
    {
        $name = request()->route()?->getName();

        if (! $name || ! str_starts_with($name, 'admin.')) {
            return [];
        }

        $crumbs = [
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ];

        if ($name === 'admin.dashboard') {
            return $crumbs;
        }

        $parts = explode('.', $name);
        $module = $parts[1] ?? '';
        $action = $parts[2] ?? 'index';

        // Reports use the action as the report name.
        if ($module === 'reports') {
            $crumbs[] = ['label' => 'Reports', 'url' => route('admin.reports.sales')];

            $report = match ($action) {
                'sales' => 'Sales',
                'inventory' => 'Inventory',
                'customers' => 'Customers',
                'staff_activity' => 'Staff activity',
                default => null,
            };

            if ($report) {
                $crumbs[] = ['label' => $report, 'url' => null];
            }

            return $crumbs;
        }

        // Settings sub-pages.
        if ($module === 'settings') {
            $crumbs[] = ['label' => 'Store settings', 'url' => route('admin.settings.index')];

            if ($action === 'payments') {
                $crumbs[] = ['label' => 'Payment settings', 'url' => null];
            } elseif ($action === 'seo') {
                $crumbs[] = ['label' => 'SEO & redirects', 'url' => null];
            }

            return $crumbs;
        }

        [$section, $label, $indexRoute] = self::SECTIONS[$module]
            ?? [null, Str::headline(str_replace('-', ' ', $module)), null];

        if ($section) {
            $crumbs[] = ['label' => $section, 'url' => null];
        }

        if ($action === 'index' || ! $indexRoute) {
            $crumbs[] = ['label' => $label, 'url' => null];
        } else {
            $crumbs[] = ['label' => $label, 'url' => route($indexRoute)];
        }

        $leaf = match ($action) {
            'create' => 'New',
            'edit' => 'Edit',
            'show' => 'Details',
            default => null,
        };

        if ($leaf) {
            $crumbs[] = ['label' => $leaf, 'url' => null];
        }

        return $crumbs;
    }
}
