<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    /** Lifetime spend at (or above) which a customer counts as VIP. */
    private const VIP_THRESHOLD = 250000.00;

    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $customers = $this->filteredQuery($request)
            ->withCount('orders')
            ->withSum('orders as lifetime_spend', 'grand_total')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'filters'   => $filters,
            'segments'  => $this->segments($filters),
            'stats'     => $this->stats(),
        ]);
    }

    public function show(Request $request, User $customer): View
    {
        $tab = in_array($request->input('tab'), ['orders', 'addresses', 'reviews', 'wishlist'], true)
            ? $request->input('tab')
            : 'orders';

        $customer->load('addresses');

        $orders = $customer->orders()->latest()->paginate(10, ['*'], 'orders_page');
        $reviews = $customer->reviews()->with('product')->latest()->paginate(10, ['*'], 'reviews_page');

        $wishlistItems = collect();
        if ($wishlist = $customer->wishlist()->with('items')->first()) {
            $wishlistItems = $wishlist->items()->with('product.images')->limit(50)->get();
        }

        return view('admin.customers.show', [
            'customer'      => $customer,
            'tab'           => $tab,
            'orders'        => $orders,
            'reviews'       => $reviews,
            'wishlistItems' => $wishlistItems,
            'lifetimeSpend' => (float) $customer->orders()->sum('grand_total'),
            'orderCount'    => $customer->orders()->count(),
        ]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $validated = $request->validate([
            'status'            => 'required|in:active,suspended,deactivated',
            'marketing_consent' => 'sometimes|boolean',
            'internal_notes'    => 'nullable|string|max:2000',
            'tags'              => 'nullable|string|max:500',
        ]);

        $previous = $customer->only(['status', 'marketing_consent', 'internal_notes', 'tags']);

        $validated['tags'] = collect(
            array_map('trim', explode(',', $validated['tags'] ?? ''))
        )->filter()
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->values()
            ->all();

        $this->audit->record('customers.update', $customer, $previous, $validated);
        $customer->update($validated);

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(User $customer): RedirectResponse
    {
        $this->audit->record('customers.delete', $customer);
        $customer->forceFill(['status' => UserStatus::Deactivated, 'data_deleted_at' => now()])->save();
        return back()->with('success', 'Customer deactivated.');
    }

    // --------------------------------------------------------------------------
    // Bulk actions
    // --------------------------------------------------------------------------
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'  => ['integer', 'exists:users,id'],
            'action' => ['required', 'in:activate,suspend,consent_on,consent_off,export,deactivate'],
        ]);

        if ($validated['action'] === 'export') {
            return redirect()->route('admin.customers.export', ['ids' => implode(',', $validated['ids'])]);
        }

        $customers = User::customers()->whereIn('id', $validated['ids'])->get();

        foreach ($customers as $customer) {
            match ($validated['action']) {
                'activate'  => $customer->update(['status' => UserStatus::Active]),
                'suspend'   => $customer->update(['status' => UserStatus::Suspended]),
                'deactivate' => (function () use ($customer) {
                    $this->audit->record('customers.bulk.deactivate', $customer);
                    $customer->forceFill(['status' => UserStatus::Deactivated, 'data_deleted_at' => now()])->save();
                })(),
                'consent_on' => $customer->forceFill(['marketing_consent' => true, 'marketing_consent_at' => now()])->save(),
                'consent_off' => $customer->forceFill(['marketing_consent' => false])->save(),
            };
        }

        $this->audit->record('customers.bulk.update', null, [], [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', count($validated['ids']).' customer(s) updated.');
    }

    // --------------------------------------------------------------------------
    // CSV export
    // --------------------------------------------------------------------------
    public function export(Request $request): StreamedResponse
    {
        $ids = $this->resolveIds($request);

        $query = $this->filteredQuery($request)
            ->withCount('orders')
            ->withSum('orders as lifetime_spend', 'grand_total')
            ->orderBy('id');

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id', 'name', 'email', 'phone', 'status', 'tags',
                'orders', 'lifetime_spend', 'marketing_consent', 'verified_email',
                'last_login_at', 'created_at',
            ]);

            $query->chunk(200, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->id,
                        $customer->name,
                        $customer->email,
                        $customer->phone ? $customer->phone_country.' '.$customer->phone : '',
                        $customer->status->value,
                        implode('|', $customer->tags ?? []),
                        $customer->orders_count,
                        $customer->lifetime_spend ?? 0,
                        $customer->marketing_consent ? 'yes' : 'no',
                        $customer->email_verified_at ? 'yes' : 'no',
                        $customer->last_login_at?->toDateTimeString(),
                        $customer->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'customers-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // --------------------------------------------------------------------------
    // Impersonation (browse the storefront as a customer — audited)
    // --------------------------------------------------------------------------
    public function impersonate(User $customer): RedirectResponse
    {
        abort_if($customer->isStaff(), 403, 'Only customer accounts can be impersonated.');
        abort_if($customer->status !== UserStatus::Active, 403, 'Suspended or deactivated accounts cannot be impersonated.');

        $this->audit->record('customers.impersonate.start', $customer, [], [
            'actor_id'   => auth()->id(),
            'actor_name' => auth()->user()?->name,
        ]);

        session([
            'impersonator_id'          => auth()->id(),
            'impersonator_customer_id' => $customer->id,
        ]);

        Auth::login($customer);

        return redirect()->route('account.dashboard')
            ->with('status', 'You are browsing as '.($customer->name ?: $customer->email).'. Use the banner above to return to the admin panel.');
    }

    public function exitImpersonation(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('home');
        }

        $customer = User::find(session('impersonator_customer_id'));
        session()->forget(['impersonator_id', 'impersonator_customer_id']);

        $admin = User::find($impersonatorId);

        if (! $admin || ! $admin->isStaff()) {
            Auth::logout();

            return redirect()->route('login')->with('status', 'Session expired — please sign in again.');
        }

        Auth::login($admin);

        if ($customer) {
            $this->audit->record('customers.impersonate.end', $customer, [], ['actor_id' => $admin->id]);
        }

        return redirect()->route('admin.customers.show', $customer ?? $admin)
            ->with('success', 'Impersonation ended.');
    }

    // --------------------------------------------------------------------------
    // GDPR
    // --------------------------------------------------------------------------
    public function gdprExport(User $customer): BinaryFileResponse
    {
        $customer->load(['orders.items', 'orders.addresses', 'orders.payments', 'addresses', 'reviews']);

        $this->audit->record('customers.gdpr.export', $customer);

        return response()->download(            $this->gdprFile($customer),
            "customer-{$customer->id}-data.json",
            ['Content-Type' => 'application/json'],
        )->deleteFileAfterSend();
    }

    public function gdprErase(User $customer): RedirectResponse
    {
        $previous = $customer->only(['email', 'phone', 'first_name', 'last_name', 'marketing_consent']);
        $consentLog = $customer->consent_log ?? [];

        DB::transaction(function () use ($customer, $consentLog) {
            $customer->forceFill([
                'first_name'        => 'Data',
                'last_name'         => 'Erased',
                'name'              => 'Data Erased',
                'email'             => 'removed+customer-'.$customer->id.'@deleted.invalid',
                'phone'             => null,
                'status'            => UserStatus::Deactivated,
                'marketing_consent' => false,
                'internal_notes'    => 'Personal data erased under a privacy (GDPR) request on '.now()->format('Y-m-d H:i').'.',
                'tags'              => null,
                'consent_log'       => array_merge($consentLog, [[
                    'event' => 'gdpr_erase',
                    'at'    => now()->toISOString(),
                ]]),
                'data_deleted_at' => now(),
            ])->save();

            $customer->phone_country = '+92';
            $customer->saveQuietly();

            $customer->addresses->each->delete();
        });

        $this->audit->record('customers.gdpr.erase', $customer, $previous, ['name' => 'Data Erased']);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer personal data erased and account deactivated.');
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    private function filters(Request $request): array
    {
        return $request->only(['q', 'segment', 'status', 'consent', 'date_from', 'date_to']);
    }

    private function filteredQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return User::customers()
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$term}%"]);
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(
                ($filters['consent'] ?? '') !== '' && ($filters['consent'] ?? null) !== null,
                fn ($q, $consent) => $q->where('marketing_consent', (bool) $consent),
            )
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['segment'] ?? null, fn ($q, $segment) => $this->applySegment($q, $segment));
    }

    private function applySegment(Builder $query, string $segment): Builder
    {
        return match ($segment) {
            'repeat'  => $query->whereHas('orders', fn ($orders) => $orders->whereIn('status', ['packed', 'shipped', 'delivered']), '>=', 2),
            'vip'     => $this->vip($query),
            'blocked' => $query->whereIn('status', ['suspended', 'deactivated']),
            'recently_joined' => $query->whereDate('created_at', '>=', now()->subDays(30)),
            'new' => $query->whereHas('orders', fn ($orders) => $orders->whereIn('status', ['pending', 'confirmed']))
                ->whereDate('created_at', '>=', now()->subDays(30)),
            default => $query,
        };
    }

    /** VIPs: customers whose lifetime order value crosses the threshold. */
    private function vip(Builder $query): Builder
    {
        return $query
            ->whereHas('orders', fn ($orders) => $orders->whereIn('status', ['packed', 'shipped', 'delivered']))
            ->whereRaw(
                '(SELECT COALESCE(SUM(o.grand_total), 0) FROM orders o WHERE o.user_id = users.id AND o.status IN (?, ?, ?)) >= ?',
                ['packed', 'shipped', 'delivered', self::VIP_THRESHOLD],
            );
    }

    private function segments(array $filters): array
    {
        $current = $filters['segment'] ?? 'all';

        return collect([
            'all'             => 'All customers',
            'recently_joined' => 'New signups (30d)',
            'repeat'          => 'Repeat buyers',
            'vip'             => 'VIP'.number_format(self::VIP_THRESHOLD, 0).' Rs.+ spend',
            'blocked'         => 'Blocked / suspended',
        ])->map(fn ($label, $key) => [
            'key'    => $key,
            'label'  => $label,
            'url'    => route('admin.customers.index', array_filter(array_merge($filters, ['segment' => $key === 'all' ? null : $key, 'page' => null]))),
            'active' => $current === $key,
        ])->values()->all();
    }

    private function stats(): array
    {
        return Cache::remember('admin.customers.stats', 60, function () {
            return [
                'total'        => User::customers()->count(),
                'new_month'    => User::customers()->whereDate('created_at', '>=', now()->startOfMonth())->count(),
                'active'       => User::customers()->where('status', 'active')->count(),
                'blocked'      => User::customers()->whereIn('status', ['suspended', 'deactivated'])->count(),
                'with_consent' => User::customers()->where('marketing_consent', true)->count(),
            ];
        });
    }

    /** Build a temporary JSON file with the customer's personal data. */
    private function gdprFile(User $customer)
    {
        $path = tempnam(sys_get_temp_dir(), 'gdpr');

        $payload = [
            'exported_at' => now()->toISOString(),
            'customer'    => [
                'id'                   => $customer->id,
                'uuid'                 => $customer->uuid,
                'name'                 => $customer->name,
                'email'                => $customer->email,
                'phone'                => $customer->phone,
                'phone_country'        => $customer->phone_country,
                'type'                 => $customer->type->value,
                'status'               => $customer->status->value,
                'marketing_consent'    => $customer->marketing_consent,
                'marketing_consent_at' => $customer->marketing_consent_at?->toISOString(),
                'consent_log'          => $customer->consent_log,
                'created_at'           => $customer->created_at?->toISOString(),
            ],
            'addresses' => $customer->addresses->map(fn ($a) => $a->only([
                'label', 'first_name', 'last_name', 'phone_country', 'phone',
                'address_line_1', 'address_line_2', 'landmark', 'city',
                'province', 'country', 'postal_code',
            ])),
            'orders' => $customer->orders->map(fn ($order) => [
                'number'         => $order->number,
                'placed_at'      => $order->created_at?->toISOString(),
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
                'grand_total'    => $order->grand_total,
                'items'          => $order->items->map(fn ($i) => $i->only(['sku', 'product_name', 'quantity', 'unit_price', 'line_total'])),
                'payments'       => $order->payments->map(fn ($p) => $p->only(['provider', 'method', 'status', 'amount'])),
                'addresses'      => $order->addresses->map(fn ($a) => $a->only([
                    'type', 'first_name', 'last_name', 'address_line_1', 'city', 'province', 'country',
                ])),
            ]),
            'reviews' => $customer->reviews->map(fn ($r) => $r->only(['rating', 'title', 'body', 'created_at'])),
        ];

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    private function resolveIds(Request $request): array
    {
        $ids = $request->input('ids');

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        return collect($ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }
}
