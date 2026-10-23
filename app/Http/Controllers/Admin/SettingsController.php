<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Models\StoreSetting;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
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
        $request->validate(['settings' => 'array']);

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
                if (($field['type'] ?? 'text') === 'file') {
                    $inputName = "settings.{$module}.{$key}";

                    if (! $request->hasFile($inputName)) {
                        continue;
                    }

                    $request->validate([
                        $inputName => 'file|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
                    ]);

                    $path = ImageCompressor::store(
                        $request->file($inputName),
                        $module === 'branding' ? 'branding' : 'marketing',
                    );

                    StoreSetting::set($field['target'] ?? $key, $path, $module);
                    $saved++;
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

        $this->audit->record('settings.update', StoreSetting::class, null, ['updated' => $saved]);

        return back()->with('success', 'Store settings saved.');
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
                'store_logo_file'   => ['label' => 'Or upload logo', 'type' => 'file', 'target' => 'store_logo_url', 'preview' => 'store_logo_url'],
                'store_logo_height' => ['label' => 'Logo height (px)', 'type' => 'number', 'default' => 56],
                'store_favicon_url' => ['label' => 'Favicon URL', 'type' => 'text', 'default' => '/images/favicon.ico'],
                'store_favicon_file'=> ['label' => 'Or upload favicon', 'type' => 'file', 'target' => 'store_favicon_url', 'preview' => 'store_favicon_url'],
                'store_description' => ['label' => 'Store description (SEO)', 'type' => 'textarea', 'default' => ''],
                'primary_color'     => ['label' => 'Primary brand colour (hex)', 'type' => 'text', 'default' => '#C8A968'],
            ],
            'marketing' => [
                'gift_popup_enabled'   => ['label' => 'Enable gift popup', 'type' => 'boolean', 'default' => false, 'help' => 'Shows once per browser session on the homepage.'],
                'gift_popup_mode'      => [
                    'label'   => 'Popup experience',
                    'type'    => 'select',
                    'default' => 'scratch',
                    'options' => ['scratch' => 'Scratch card', 'spin' => 'Spin wheel', 'both' => 'Let the customer choose'],
                ],
                'gift_popup_title'     => ['label' => 'Popup title', 'type' => 'text', 'default' => 'Scratch & Win'],
                'gift_popup_subtitle'  => ['label' => 'Popup subtitle', 'type' => 'text', 'default' => 'Scratch the card or spin the wheel to reveal your free gift.'],
                'gift_popup_image_url' => ['label' => 'Popup banner URL', 'type' => 'text', 'default' => ''],
                'gift_popup_image_file'=> ['label' => 'Or upload popup banner', 'type' => 'file', 'target' => 'gift_popup_image_url', 'preview' => 'gift_popup_image_url'],
                'gift_popup_prizes'    => [
                    'label'   => 'Prizes (one per line)',
                    'type'    => 'textarea',
                    'default' => "10% OFF|percentage|10|70\nFree shipping|free_shipping|0|30\nRs. 500 OFF|fixed|500|10",
                    'help'    => 'Format: Label|type|value|weight. Type is percentage, fixed or free_shipping. Weight is relative (higher = more likely).',
                ],
                'gift_popup_prize'     => ['label' => 'Fallback prize label', 'type' => 'text', 'default' => '10% OFF your next order', 'help' => 'Used when the prize list above is empty.'],
                'gift_unique_coupons'  => ['label' => 'Issue a unique single-use coupon per claim', 'type' => 'boolean', 'default' => true, 'help' => 'Recommended. Disable only to show one shared coupon code instead.'],
                'gift_coupon_prefix'   => ['label' => 'Coupon code prefix', 'type' => 'text', 'default' => 'GIFT', 'help' => 'Letters and numbers only, e.g. GIFT.'],
                'gift_coupon_type'     => [
                    'label'   => 'Default coupon type',
                    'type'    => 'select',
                    'default' => 'percentage',
                    'options' => ['percentage' => 'Percentage off', 'fixed' => 'Fixed amount off', 'free_shipping' => 'Free shipping'],
                ],
                'gift_coupon_value'    => ['label' => 'Default coupon value', 'type' => 'number', 'default' => 10],
                'gift_coupon_min_order'=> ['label' => 'Minimum order value', 'type' => 'number', 'default' => 0],
                'gift_coupon_expiry_days' => ['label' => 'Coupon expiry (days)', 'type' => 'number', 'default' => 30],
                'gift_popup_coupon'    => ['label' => 'Fallback shared coupon code', 'type' => 'text', 'default' => 'WELCOME10', 'help' => 'Only used when unique coupons are disabled.'],
                'gift_popup_button'    => ['label' => 'Claim button text', 'type' => 'text', 'default' => 'Claim my gift'],
                'gift_popup_delay'     => ['label' => 'Show after (seconds)', 'type' => 'number', 'default' => 4],
                'gift_popup_terms'     => ['label' => 'Terms / small print', 'type' => 'textarea', 'default' => ''],
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
                    'type'    => $field['type'] ?? 'text',
                    'value'   => $setting?->value ?? $setting?->data ?? ($field['default'] ?? null),
                    'target'  => $field['target'] ?? $key,
                    'preview' => $field['preview'] ?? null,
                    'options' => $field['options'] ?? [],
                    'help'    => $field['help'] ?? null,
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
