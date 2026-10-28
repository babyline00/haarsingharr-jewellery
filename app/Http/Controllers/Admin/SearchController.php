<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q'));

        $orders = collect();
        $customers = collect();
        $products = collect();

        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

            $orders = Order::query()
                ->where(function ($query) use ($like) {
                    $query->where('number', 'like', $like)
                        ->orWhere('guest_email', 'like', $like);
                })
                ->latest()
                ->limit(10)
                ->get();

            $customers = User::query()
                ->where(function ($query) use ($like) {
                    $query->where('email', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                ->orderBy('first_name')
                ->limit(10)
                ->get();

            $products = Product::query()
                ->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like);
                })
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        $total = $orders->count() + $customers->count() + $products->count();

        return view('admin.search.results', compact('q', 'orders', 'customers', 'products', 'total'));
    }

    /**
     * JSON suggestions for the ⌘K command palette. Permission-aware: each
     * group only appears when the user can view that module, and it also
     * carries the quick actions the signed-in staff member may use.
     */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_substr($q, 0, 60)).'%';
        $result = [];

        if (mb_strlen($q) >= 2) {
            if ($request->user()->can('orders.view')) {
                $result['orders'] = Order::query()
                    ->where('number', 'like', $like)
                    ->orWhere('guest_email', 'like', $like)
                    ->with('user')
                    ->latest()->limit(5)
                    ->get()
                    ->map(fn ($order) => [
                        'title' => $order->number,
                        'meta'  => ($order->user?->name ?? 'Guest').' — '.number_format((float) $order->grand_total),
                        'url'   => route('admin.orders.show', $order),
                    ])->all();
            }

            if ($request->user()->can('customers.view')) {
                $result['customers'] = User::customers()
                    ->where(function ($query) use ($like) {
                        $query->where('email', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    })
                    ->orderBy('first_name')->limit(5)->get()
                    ->map(fn ($customer) => [
                        'title' => trim($customer->first_name.' '.$customer->last_name) ?: $customer->email,
                        'meta'  => $customer->email,
                        'url'   => route('admin.users.show', $customer),
                    ])->all();
            }

            if ($request->user()->can('products.view')) {
                $result['products'] = Product::query()
                    ->where(function ($query) use ($like) {
                        $query->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('barcode', 'like', $like);
                    })
                    ->orderBy('name')->limit(5)->get()
                    ->map(fn ($product) => [
                        'title' => $product->name,
                        'meta'  => $product->sku,
                        'url'   => route('admin.products.edit', $product),
                    ])->all();
            }
        }

        // Quick actions — permission-filtered, filtered client-side as the
        // staff member types.
        $result['actions'] = collect([
            ['keywords' => 'product catalogue new', 'title' => 'Create product', 'url' => route('admin.products.create'), 'perm' => 'products.create', 'icon' => 'plus'],
            ['keywords' => 'category', 'title' => 'Categories', 'url' => route('admin.categories.index'), 'perm' => 'categories.view', 'icon' => 'grid'],
            ['keywords' => 'order sales', 'title' => 'Orders', 'url' => route('admin.orders.index'), 'perm' => 'orders.view', 'icon' => 'shopping-bag'],
            ['keywords' => 'customer users', 'title' => 'Customers', 'url' => route('admin.users.index'), 'perm' => 'customers.view', 'icon' => 'users'],
            ['keywords' => 'staff member', 'title' => 'Staff', 'url' => route('admin.staff.index'), 'perm' => 'staff.view', 'icon' => 'shield'],
            ['keywords' => 'roles permissions matrix', 'title' => 'Permission matrix', 'url' => route('admin.roles.matrix'), 'perm' => 'roles.view', 'icon' => 'lock'],
            ['keywords' => 'settings', 'title' => 'Store settings', 'url' => route('admin.settings.index'), 'perm' => 'settings.view', 'icon' => 'settings'],
            ['keywords' => 'system health backups maintenance', 'title' => 'System health', 'url' => route('admin.system.index'), 'perm' => 'settings.view', 'icon' => 'refresh'],
            ['keywords' => 'database', 'title' => 'Database', 'url' => route('admin.database.info'), 'perm' => 'settings.view', 'icon' => 'database'],
            ['keywords' => 'reports sales analytics', 'title' => 'Reports', 'url' => route('admin.reports.sales'), 'perm' => 'reports.view', 'icon' => 'chart'],
            ['keywords' => 'security audit logs', 'title' => 'Audit logs', 'url' => route('admin.audit-logs.index'), 'perm' => 'audit.view', 'icon' => 'file-text'],
        ])
            ->filter(fn ($action) => $request->user()->can($action['perm']))
            ->map(fn ($action) => ['title' => $action['title'], 'url' => $action['url'], 'icon' => $action['icon']])
            ->values()->all();

        return response()->json($result);
    }
}
