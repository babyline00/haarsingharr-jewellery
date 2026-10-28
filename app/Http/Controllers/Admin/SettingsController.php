<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PlainNoticeMail;
use App\Models\Redirect;
use App\Models\StoreSetting;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        return view('admin.settings.index', [
            'groups' => $this->resolvedGroups(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'array',
            'logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $inputs = $request->input('settings', []);
        $saved = 0;

        $schema = array_merge($this->schema(), ['seo' => $this->seoSchema()]);

        foreach ($schema as $module => $fields) {
            // Only persist modules that were actually submitted, so saving one
            // tab never resets settings managed on another tab.
            if (! array_key_exists($module, $inputs)) {
                continue;
            }

            foreach ($fields as $key => $field) {
                if ($request->hasFile('logo_file') && $module === 'branding' && $key === 'store_logo_url') {
                    continue;
                }

                $value = data_get($inputs, "{$module}.{$key}");

                if (($field['type'] ?? 'text') === 'boolean') {
                    $value = (bool) $value;
                } elseif (($field['type'] ?? 'text') === 'number') {
                    $value = $value === null || $value === '' ? null : (float) $value;
                } else {
                    $value = is_string($value) ? trim($value) : $value;
                }

                StoreSetting::set($key, $value, $module);
                $saved++;
            }
        }

        if ($request->hasFile('logo_file')) {
            $oldLogo = StoreSetting::get('store_logo_url');
            $newLogo = ImageCompressor::store($request->file('logo_file'), 'branding');

            try {
                StoreSetting::set('store_logo_url', $newLogo, 'branding');
            } catch (Throwable $exception) {
                Storage::disk('public')->delete($newLogo);
                throw $exception;
            }

            if ($oldLogo && $oldLogo !== $newLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $saved++;
        }

        $this->audit->record('settings.update', StoreSetting::class, null, ['updated' => $saved]);

        return back()->with('success', 'Store settings saved.');
    }

    // --------------------------------------------------------------------------
    // Email / SMTP test send
    // --------------------------------------------------------------------------
    public function emailTest(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        Mail::to($validated['email'])->send(new PlainNoticeMail(
            '[Test] Haarsingharr outgoing mail — '.now()->format('M j, Y H:i'),
            'This is a test email from the Haarsingharr admin settings screen. '
            .'If you can read this, outgoing mail works.',
        ));

        $this->audit->record('settings.email_test', StoreSetting::class, null, ['to' => $validated['email']]);

        return back()->with('success', "Test email queued to {$validated['email']}.");
    }

    // --------------------------------------------------------------------------
    // SEO
    // --------------------------------------------------------------------------
    public function seoIndex(Request $request): View
    {
        return view('admin.seo.index', [
            'groups'    => $this->resolvedGroups(['seo' => $this->seoSchema()]),
            'redirects' => Redirect::latest()->paginate(20),
        ]);
    }

    public function storeRedirect(Request $request)
    {
        $validated = $request->validate([
            'source_url'      => 'required|string|max:300',
            'destination_url' => 'required|string|max:500',
            'status_code'     => 'required|integer|in:301,302,307,308',
        ]);

        $validated['source_url'] = Redirect::normaliseSource($validated['source_url']);
        $validated['is_active'] = true;

        $redirect = Redirect::updateOrCreate(
            ['source_url' => $validated['source_url']],
            $validated,
        );

        $this->audit->record('seo.redirect.create', $redirect);
        return back()->with('success', 'Redirect saved.');
    }

    public function destroyRedirect(Redirect $redirect)
    {
        $this->audit->record('seo.redirect.delete', $redirect);
        $redirect->delete();
        return back()->with('success', 'Redirect removed.');
    }

    // --------------------------------------------------------------------------
    // Payments
    // --------------------------------------------------------------------------
    public function paymentSettings(Request $request): View
    {
        return view('admin.settings.payments', [
            'gateways' => $this->gateways(),
        ]);
    }

    public function updatePaymentSettings(Request $request)
    {
        $request->validate([
            'enabled'      => 'array',
            'instructions' => 'array',
        ]);

        $enabled = $request->input('enabled', []);
        $instructions = $request->input('instructions', []);

        foreach (array_keys(config('payments.gateways', [])) as $provider) {
            StoreSetting::set("payment.{$provider}.enabled", (bool) ($enabled[$provider] ?? false), 'payments');
            StoreSetting::set("payment.{$provider}.instructions", (string) ($instructions[$provider] ?? ''), 'payments');
        }

        $this->audit->record('settings.payments.update', StoreSetting::class);

        return back()->with('success', 'Payment settings saved.');
    }

    // --------------------------------------------------------------------------
    // Schema helpers
    // --------------------------------------------------------------------------
    private function schema(): array
    {
        return [
            'general' => [
                'store_name'      => ['label' => 'Store name', 'type' => 'text', 'default' => config('app.name')],
                'store_tagline'   => ['label' => 'Tagline', 'type' => 'text', 'default' => ''],
                'contact_email'   => ['label' => 'Contact email', 'type' => 'email', 'default' => config('storefront.contact.email')],
                'contact_phone'   => ['label' => 'Contact phone', 'type' => 'text', 'default' => config('storefront.contact.phone')],
                'whatsapp_number' => ['label' => 'WhatsApp number', 'type' => 'text', 'default' => config('storefront.contact.whatsapp')],
                'contact_address' => ['label' => 'Address', 'type' => 'textarea', 'default' => config('storefront.contact.address')],
                'currency'        => ['label' => 'Currency code', 'type' => 'text', 'default' => 'PKR'],
                'currency_symbol' => ['label' => 'Currency symbol', 'type' => 'text', 'default' => 'Rs.'],
            ],
            'branding' => [
                'store_logo_url'    => ['label' => 'Logo URL', 'type' => 'text', 'default' => '/images/logo.png'],
                'store_favicon_url' => ['label' => 'Favicon URL', 'type' => 'text', 'default' => '/images/favicon.ico'],
                'store_description' => ['label' => 'Store description (SEO)', 'type' => 'textarea', 'default' => ''],
                'primary_color'     => ['label' => 'Primary brand colour (hex)', 'type' => 'text', 'default' => '#C8A968'],
            ],
            'tax' => [
                'tax_enabled'      => ['label' => 'Enable tax', 'type' => 'boolean', 'default' => config('storefront.tax.enabled')],
                'tax_inclusive'    => ['label' => 'Prices include tax', 'type' => 'boolean', 'default' => config('storefront.tax.inclusive')],
                'tax_default_rate' => ['label' => 'Default tax rate (%)', 'type' => 'number', 'default' => config('storefront.tax.default_rate')],
            ],
            'inventory' => [
                'low_stock_threshold' => ['label' => 'Low stock threshold', 'type' => 'number', 'default' => config('storefront.inventory.low_stock_threshold')],
                'reserve_minutes'     => ['label' => 'Reservation window (minutes)', 'type' => 'number', 'default' => config('storefront.inventory.reserve_minutes')],
                'allow_backorders'    => ['label' => 'Allow backorders', 'type' => 'boolean', 'default' => config('storefront.inventory.allow_backorders')],
            ],
            'reviews' => [
                'reviews_enabled'          => ['label' => 'Enable reviews', 'type' => 'boolean', 'default' => config('storefront.reviews.enabled')],
                'reviews_require_purchase' => ['label' => 'Only verified purchases', 'type' => 'boolean', 'default' => config('storefront.reviews.require_purchase')],
                'reviews_auto_approve'    => ['label' => 'Auto-approve reviews', 'type' => 'boolean', 'default' => config('storefront.reviews.auto_approve')],
            ],
            'social' => [
                'social_instagram' => ['label' => 'Instagram', 'type' => 'text', 'default' => config('storefront.social.instagram')],
                'social_facebook'  => ['label' => 'Facebook', 'type' => 'text', 'default' => config('storefront.social.facebook')],
                'social_tiktok'    => ['label' => 'TikTok', 'type' => 'text', 'default' => config('storefront.social.tiktok')],
                'social_pinterest' => ['label' => 'Pinterest', 'type' => 'text', 'default' => config('storefront.social.pinterest')],
                'social_youtube'   => ['label' => 'YouTube', 'type' => 'text', 'default' => config('storefront.social.youtube')],
            ],
            'shipping' => [
                'free_shipping_threshold' => ['label' => 'Free shipping threshold (Rs.)', 'type' => 'number', 'default' => 5000],
                'default_shipping_cost'  => ['label' => 'Default shipping cost (Rs.)', 'type' => 'number', 'default' => 250],
                'shipping_note'          => ['label' => 'Shipping note shown at checkout', 'type' => 'textarea', 'default' => ''],
            ],
            'orders' => [
                'order_prefix'        => ['label' => 'Order number prefix', 'type' => 'text', 'default' => 'HS'],
                'allow_guest_checkout' => ['label' => 'Allow guest checkout', 'type' => 'boolean', 'default' => true],
                'auto_cancel_minutes'  => ['label' => 'Auto-cancel unpaid orders after (minutes)', 'type' => 'number', 'default' => 60],
            ],
            'checkout' => [
                'cod_max_order_value' => [
                    'label' => 'COD maximum order value (Rs.)', 'type' => 'number', 'default' => 200000,
                    'help'  => 'Cash on delivery is unavailable above this order total.',
                ],
                'cod_note' => ['label' => 'COD note shown at checkout', 'type' => 'textarea', 'default' => ''],
                'min_order_value' => ['label' => 'Minimum order value (Rs.)', 'type' => 'number', 'default' => 0],
                'max_quantity_per_item' => ['label' => 'Max quantity per line item', 'type' => 'number', 'default' => 10],
            ],
            'email' => [
                'email_from_name' => ['label' => 'From name', 'type' => 'text', 'default' => config('mail.from.name')],
                'email_from_address' => ['label' => 'From address', 'type' => 'email', 'default' => config('mail.from.address')],
                'email_admin_address' => ['label' => 'Staff notification inbox', 'type' => 'email', 'default' => ''],
            ],
            'notifications' => [
                'notify_new_order' => ['label' => 'Email staff on new orders', 'type' => 'boolean', 'default' => true],
                'notify_low_stock' => ['label' => 'Email staff on low stock', 'type' => 'boolean', 'default' => true],
                'notify_failed_login' => ['label' => 'Email super-admins on failed-login bursts', 'type' => 'boolean', 'default' => true],
            ],
            'homepage' => [
                'hero_autoplay' => ['label' => 'Automatically rotate homepage hero banners', 'type' => 'boolean', 'default' => true],
                'hero_autoplay_seconds' => ['label' => 'Hero slide duration (seconds)', 'type' => 'number', 'default' => 6, 'help' => 'Choose between 3 and 15 seconds. Visitors can pause the slideshow.'],
            ],
            'legal' => [
                'legal_terms_url' => ['label' => 'Terms &amp; conditions URL', 'type' => 'text', 'default' => '/pages/terms'],
                'legal_privacy_url' => ['label' => 'Privacy policy URL', 'type' => 'text', 'default' => '/pages/privacy'],
                'legal_returns_url' => ['label' => 'Returns &amp; exchange policy URL', 'type' => 'text', 'default' => '/pages/returns'],
            ],
            'system' => [
                'audit_retention_days' => ['label' => 'Audit log retention (days)', 'type' => 'number', 'default' => 365],
            ],
        ];
    }

    private function seoSchema(): array
    {
        return [
            'seo_meta_title'       => ['label' => 'Default meta title', 'type' => 'text', 'default' => ''],
            'seo_meta_description' => ['label' => 'Default meta description', 'type' => 'textarea', 'default' => ''],
            'seo_meta_keywords'    => ['label' => 'Meta keywords', 'type' => 'text', 'default' => ''],
            'seo_robots_txt'       => ['label' => 'Robots.txt contents', 'type' => 'textarea', 'default' => "User-agent: *\nAllow: /"],
            'seo_google_analytics' => ['label' => 'Google Analytics ID', 'type' => 'text', 'default' => ''],
            'seo_facebook_pixel'   => ['label' => 'Facebook Pixel ID', 'type' => 'text', 'default' => ''],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function resolvedGroups(array $extra = []): array
    {
        $schema = $extra ?: $this->schema();
        $stored = StoreSetting::all()->keyBy('key');

        $groups = [];
        foreach ($schema as $module => $fields) {
            $groups[$module] = [];
            foreach ($fields as $key => $field) {
                $setting = $stored->get($key);
                $groups[$module][] = [
                    'key'   => $key,
                    'label' => $field['label'] ?? $key,
                    'type'  => $field['type'] ?? 'text',
                    'value' => $setting?->value ?? $setting?->data ?? ($field['default'] ?? null),
                    'help'  => $field['help'] ?? null,
                ];
            }
        }

        return $groups;
    }

    private function gateways(): array
    {
        $stored = StoreSetting::all()->keyBy('key');
        $gateways = [];

        foreach (config('payments.gateways', []) as $provider => $config) {
            $enabledSetting = $stored->get("payment.{$provider}.enabled");

            $gateways[$provider] = [
                'label'        => $config['label'] ?? ucfirst($provider),
                'description'  => $config['description'] ?? '',
                'enabled'      => $enabledSetting ? filter_var($enabledSetting->value, FILTER_VALIDATE_BOOLEAN) : (bool) ($config['enabled'] ?? false),
                'environment'  => $config['environment'] ?? null,
                'instructions' => $stored->get("payment.{$provider}.instructions")?->value ?? '',
                'credentials'  => $this->credentialSummary($provider, $config),
            ];
        }

        return $gateways;
    }

    private function credentialSummary(string $provider, array $config): array
    {
        $secretKeys = ['password', 'integrity_salt', 'secret_key', 'webhook_secret', 'hash'];
        $summary = [];

        foreach ($config as $key => $value) {
            if (in_array($key, ['enabled', 'label', 'description'], true)) {
                continue;
            }
            $isSecret = in_array($key, $secretKeys, true);
            $summary[$key] = [
                'is_secret' => $isSecret,
                'set'       => ! empty($value),
                'value'     => $isSecret ? null : (is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value),
            ];
        }

        return $summary;
    }
}
