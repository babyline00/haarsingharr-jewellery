<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\ExchangeRequest;
use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Models\Refund;
use App\Models\Review;
use App\Models\StoreLocation;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Admin write-path regression tests
|--------------------------------------------------------------------------
|
| The GET sweep only proves the admin pages render. These tests exercise the
| create / update / delete (and special action) endpoints, since those are
| where latent bugs hide.
|
*/

beforeEach(function () {
    $this->actingAs(createAdmin());
});

// ----------------------------------------------------------------------------
// Simple CRUD resources
// ----------------------------------------------------------------------------

test('category create/update/delete round-trips', function () {
    $this->post(route('admin.categories.store'), [
        'name' => 'Rings', 'status' => 'active', 'sort_order' => 1,
    ])->assertRedirect(route('admin.categories.index'));

    $category = Category::where('name', 'Rings')->firstOrFail();

    $this->patch(route('admin.categories.update', $category), [
        'name' => 'Rings & Bands', 'status' => 'active',
    ])->assertRedirect();
    expect($category->fresh()->name)->toBe('Rings & Bands');

    $this->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));
    expect(Category::find($category->id))->toBeNull();
});

test('collection create/update/delete round-trips', function () {
    $this->post(route('admin.collections.store'), [
        'name' => 'Bridal Edit', 'type' => 'manual',
        'match_condition' => 'all', 'status' => 'active',
    ])->assertRedirect(route('admin.collections.index'));

    $collection = Collection::where('name', 'Bridal Edit')->firstOrFail();

    $this->patch(route('admin.collections.update', $collection), [
        'name' => 'Bridal Collection', 'type' => 'manual',
        'match_condition' => 'all', 'status' => 'inactive',
    ])->assertRedirect();
    expect($collection->fresh()->status)->toBe('inactive');

    $this->delete(route('admin.collections.destroy', $collection))
        ->assertRedirect(route('admin.collections.index'));
    expect(Collection::find($collection->id))->toBeNull();
});

test('coupon create/update/delete round-trips', function () {
    $this->post(route('admin.coupons.store'), [
        'code' => 'EID20', 'type' => 'percentage', 'value' => 20,
        'status' => 'active',
    ])->assertRedirect(route('admin.coupons.index'));

    $coupon = Coupon::where('code', 'EID20')->firstOrFail();

    $this->patch(route('admin.coupons.update', $coupon), [
        'code' => 'EID20', 'type' => 'percentage', 'value' => 25,
        'status' => 'disabled',
    ])->assertRedirect();
    expect((float) $coupon->fresh()->value)->toBe(25.0);

    $this->delete(route('admin.coupons.destroy', $coupon))
        ->assertRedirect(route('admin.coupons.index'));
    expect(Coupon::find($coupon->id))->toBeNull();
});

test('faq create/update/delete round-trips', function () {
    $this->post(route('admin.faqs.store'), [
        'question' => 'Do you ship nationwide?', 'answer' => 'Yes, across Pakistan.',
    ])->assertRedirect(route('admin.faqs.index'));

    $faq = Faq::where('question', 'Do you ship nationwide?')->firstOrFail();

    $this->patch(route('admin.faqs.update', $faq), [
        'question' => 'Do you ship nationwide?', 'answer' => 'Yes, to all cities.',
    ])->assertRedirect();
    expect($faq->fresh()->answer)->toBe('Yes, to all cities.');

    $this->delete(route('admin.faqs.destroy', $faq))
        ->assertRedirect(route('admin.faqs.index'));
    expect(Faq::find($faq->id))->toBeNull();
});

test('banner create/update/delete round-trips', function () {
    $this->post(route('admin.banners.store'), [
        'name' => 'Hero', 'location' => 'homepage_hero', 'image' => 'banners/hero.jpg',
    ])->assertRedirect(route('admin.banners.index'));

    $banner = Banner::where('name', 'Hero')->firstOrFail();

    $this->patch(route('admin.banners.update', $banner), [
        'name' => 'Hero Banner', 'location' => 'homepage_hero', 'image' => 'banners/hero.jpg',
    ])->assertRedirect();
    expect($banner->fresh()->name)->toBe('Hero Banner');

    $this->delete(route('admin.banners.destroy', $banner))
        ->assertRedirect(route('admin.banners.index'));
    expect(Banner::find($banner->id))->toBeNull();
});

test('cms page create/update/delete round-trips', function () {
    $this->post(route('admin.pages.store'), [
        'title' => 'About Us', 'template' => 'default', 'status' => 'draft',
    ]);

    $page = CmsPage::where('title', 'About Us')->firstOrFail();
    expect($page->created_by)->toBe(auth()->id());

    $this->patch(route('admin.pages.update', $page), [
        'title' => 'About Haarsingharr', 'template' => 'default', 'status' => 'published',
    ])->assertRedirect();
    expect($page->fresh()->status)->toBe('published');

    $this->delete(route('admin.pages.destroy', $page))
        ->assertRedirect(route('admin.pages.index'));
    expect(CmsPage::find($page->id))->toBeNull();
});

test('store location create/update/delete round-trips', function () {
    $this->post(route('admin.store-locations.store'), [
        'name' => 'Lahore Flagship', 'address' => '12 Gulberg, Lahore',
        'status' => 'active', 'is_main' => true,
    ])->assertRedirect(route('admin.store-locations.index'));

    $location = StoreLocation::where('name', 'Lahore Flagship')->firstOrFail();
    expect((bool) $location->is_main)->toBeTrue();

    $this->patch(route('admin.store-locations.update', $location), [
        'name' => 'Lahore Flagship', 'address' => '12-A Gulberg, Lahore',
        'status' => 'active',
    ])->assertRedirect();
    expect($location->fresh()->address)->toBe('12-A Gulberg, Lahore');

    $this->delete(route('admin.store-locations.destroy', $location))
        ->assertRedirect(route('admin.store-locations.index'));
    expect(StoreLocation::find($location->id))->toBeNull();
});

test('homepage section create/update/delete round-trips', function () {
    $this->post(route('admin.homepage-sections.store'), [
        'key' => 'featured_grid', 'title' => 'Featured', 'sort_order' => 2,
    ])->assertRedirect(route('admin.homepage-sections.index'));

    $section = HomepageSection::where('key', 'featured_grid')->firstOrFail();

    $this->patch(route('admin.homepage-sections.update', $section), [
        'key' => 'featured_grid', 'title' => 'Featured Pieces', 'sort_order' => 1,
    ])->assertRedirect();
    expect($section->fresh()->title)->toBe('Featured Pieces');

    $this->delete(route('admin.homepage-sections.destroy', $section))
        ->assertRedirect(route('admin.homepage-sections.index'));
    expect(HomepageSection::find($section->id))->toBeNull();
});

// ----------------------------------------------------------------------------
// Products + variants
// ----------------------------------------------------------------------------

test('product and variant lifecycle round-trips', function () {
    $this->post(route('admin.products.store'), [
        'name' => 'Test Necklace', 'regular_price' => 5000, 'status' => 'active',
    ]);
    $product = Product::where('name', 'Test Necklace')->firstOrFail();

    $this->post(route('admin.products.variants.store', $product), [
        'title' => 'Default', 'regular_price' => 5000, 'stock_quantity' => 3,
    ])->assertRedirect();
    $variant = $product->variants()->firstOrFail();

    $this->patch(route('admin.products.variants.update', [
        'product' => $product, 'variant' => $variant,
    ]), [
        'title' => 'Default', 'regular_price' => 5500,
    ])->assertRedirect();
    expect((float) $variant->fresh()->regular_price)->toBe(5500.0);

    $this->patch(route('admin.products.update', $product), [
        'name' => 'Test Necklace (Gold)', 'regular_price' => 5500, 'status' => 'active',
    ])->assertRedirect();
    expect($product->fresh()->name)->toBe('Test Necklace (Gold)');

    $this->delete(route('admin.products.variants.destroy', [
        'product' => $product, 'variant' => $variant,
    ]))->assertRedirect();
    expect(ProductVariant::find($variant->id))->toBeNull();

    $this->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'));
    expect(Product::find($product->id))->toBeNull();
});

test('product creation can set initial inventory', function () {
    $this->post(route('admin.products.store'), [
        'name' => 'Stocked Ring',
        'regular_price' => 1000,
        'status' => 'active',
        'stock_quantity' => 12,
        'low_stock_threshold' => 3,
        'allow_backorder' => 1,
    ])->assertRedirect();

    $product = Product::where('name', 'Stocked Ring')->firstOrFail();
    $variant = $product->variants()->firstOrFail();

    expect($variant->stock_quantity)->toBe(12)
        ->and($variant->low_stock_threshold)->toBe(3)
        ->and($variant->allow_backorder)->toBeTrue()
        ->and($product->availableStock())->toBe(12)
        ->and(InventoryMovement::where('product_variant_id', $variant->id)
            ->where('quantity_change', 12)->exists())->toBeTrue();
});

test('product videos and reels can be uploaded and removed', function () {
    Storage::fake('public');

    $product = Product::factory()->create();

    $this->post(route('admin.products.videos.store', $product), [
        'videos' => [UploadedFile::fake()->create('reel.mp4', 1024, 'video/mp4')],
        'type' => 'reel',
        'title' => 'Studio reel',
    ])->assertRedirect();

    $video = $product->videos()->firstOrFail();

    expect($video->type)->toBe('reel')
        ->and($video->title)->toBe('Studio reel');
    Storage::disk('public')->assertExists($video->path);

    $this->delete(route('admin.products.videos.destroy', [$product, $video]))
        ->assertRedirect();

    expect(ProductVideo::find($video->id))->toBeNull();
});

// ----------------------------------------------------------------------------
// Roles + staff + users + customers
// ----------------------------------------------------------------------------

test('role create/update/delete round-trips', function () {
    $this->post(route('admin.roles.store'), [
        'slug' => 'test-role', 'title' => 'Test Role',
    ])->assertRedirect(route('admin.roles.index'));

    $role = Role::where('slug', 'test-role')->firstOrFail();

    $this->patch(route('admin.roles.update', $role), [
        'slug' => 'test-role', 'title' => 'Renamed Role',
    ])->assertRedirect();
    expect($role->fresh()->title)->toBe('Renamed Role');

    $this->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));
    expect(Role::find($role->id))->toBeNull();
});

test('staff create/update/delete round-trips', function () {
    $this->post(route('admin.staff.store'), [
        'first_name' => 'Pat', 'last_name' => 'Staff',
        'email' => 'pat.staff@example.com',
        'password' => 'StrongPassw0rd!23', 'password_confirmation' => 'StrongPassw0rd!23',
        'type' => 'staff', 'status' => 'active',
    ])->assertRedirect(route('admin.staff.index'));

    $staff = User::where('email', 'pat.staff@example.com')->firstOrFail();

    $this->patch(route('admin.staff.update', $staff), [
        'first_name' => 'Patricia', 'last_name' => 'Staff',
        'email' => 'pat.staff@example.com',
        'type' => 'staff', 'status' => 'suspended',
    ])->assertRedirect();
    expect($staff->fresh()->first_name)->toBe('Patricia');

    $this->delete(route('admin.staff.destroy', $staff))
        ->assertRedirect(route('admin.staff.index'));
    expect(User::find($staff->id))->toBeNull();
});

test('admin can update a user and a customer', function () {
    $customer = createCustomer();

    $this->patch(route('admin.users.update', $customer), [
        'first_name' => 'Updated', 'last_name' => 'Customer',
        'email' => $customer->email, 'type' => 'customer', 'status' => 'active',
    ])->assertRedirect();
    expect($customer->fresh()->first_name)->toBe('Updated');

    $this->patch(route('admin.customers.update', $customer), [
        'status' => 'suspended', 'internal_notes' => 'VIP flagged',
    ])->assertRedirect();
    expect($customer->fresh()->status->value)->toBe('suspended');
});

// ----------------------------------------------------------------------------
// Inventory
// ----------------------------------------------------------------------------

test('admin can adjust stock and it writes a movement', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5, 'safety_stock' => 0]);

    $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $variant->id,
        'new_stock' => 12,
        'reason' => 'physical_count',
    ])->assertRedirect();

    expect((int) $variant->fresh()->stock_quantity)->toBe(12);
    expect(\App\Models\InventoryMovement::where('product_variant_id', $variant->id)->count())
        ->toBeGreaterThan(0);
});

// ----------------------------------------------------------------------------
// Orders, refunds, reviews, exchanges
// ----------------------------------------------------------------------------

test('admin can update order status and record a shipment', function () {
    $order = Order::factory()->create();

    $this->post(route('admin.orders.status.update', $order), [
        'status' => 'confirmed',
    ])->assertRedirect();
    expect($order->fresh()->status->value)->toBe('confirmed');

    $this->post(route('admin.orders.shipments.store', $order), [
        'courier' => 'tcs', 'tracking_number' => 'TCS-123456',
    ])->assertRedirect();
    expect($order->fresh()->tracking_number)->toBe('TCS-123456');
});

test('admin can create and approve a refund', function () {
    $order = Order::factory()->create(['grand_total' => 10000]);
    $payment = Payment::create([
        'order_id' => $order->id, 'provider' => 'cod',
        'amount' => 10000, 'currency' => 'PKR', 'status' => 'captured',
    ]);

    $this->post(route('admin.refunds.store'), [
        'payment_id' => $payment->id, 'amount' => 2000,
        'type' => 'partial', 'reason' => 'Damaged item',
    ])->assertRedirect();

    $refund = Refund::where('payment_id', $payment->id)->firstOrFail();

    $this->post(route('admin.refunds.approve', $refund))->assertRedirect();
    expect($refund->fresh()->status)->toBe('completed');
});

test('admin can moderate a review', function () {
    $product = Product::factory()->create();
    $review = Review::create([
        'product_id' => $product->id, 'rating' => 5,
        'body' => 'Beautiful craftsmanship.', 'customer_name' => 'Ayesha',
    ]);

    $this->post(route('admin.reviews.approve', $review))->assertRedirect();
    expect((bool) $review->fresh()->is_approved)->toBeTrue();

    $this->patch(route('admin.reviews.update', $review), [
        'admin_response' => 'Thank you!',
    ])->assertRedirect();
    expect($review->fresh()->admin_response)->toBe('Thank you!');

    $this->post(route('admin.reviews.reject', $review))->assertRedirect();
    expect((bool) $review->fresh()->is_approved)->toBeFalse();
});

test('admin can update an exchange request', function () {
    $order = Order::factory()->create();
    $exchange = ExchangeRequest::create([
        'number' => 'EX-'.uniqid(),
        'order_id' => $order->id,
        'type' => 'return', 'status' => 'pending',
        'reason' => 'Wrong size',
    ]);

    $this->patch(route('admin.exchanges.update', $exchange), [
        'status' => 'approved', 'admin_notes' => 'Approved for return.',
    ])->assertRedirect();
    expect($exchange->fresh()->status)->toBe('approved');
});

// ----------------------------------------------------------------------------
// Settings, shipping, SEO
// ----------------------------------------------------------------------------

test('admin can save settings, shipping and a redirect', function () {
    $this->post(route('admin.settings.update'), [
        'settings' => ['general' => ['currency_symbol' => 'PKR']],
    ])->assertRedirect();

    $this->post(route('admin.shipping.store'), [
        'shipping' => ['free_shipping_threshold' => 10000],
    ])->assertRedirect();

    $this->post(route('admin.seo.redirects.store'), [
        'source_url' => '/old-ring', 'destination_url' => '/rings', 'status_code' => 301,
    ])->assertRedirect();

    expect(\App\Models\Redirect::where('source_url', '/old-ring')->exists())->toBeTrue();
});

test('admin can upload a logo and configure the gift popup', function () {
    Storage::fake('public');

    $this->post(route('admin.settings.update'), [
        'settings' => [
            'branding' => [
                'store_logo_url'    => '/images/logo.png',
                'store_logo_height' => 72,
                'store_logo_file'   => UploadedFile::fake()->create('logo.png', 40, 'image/png'),
            ],
            'marketing' => [
                'gift_popup_enabled' => '1',
                'gift_popup_title'   => 'Scratch & Win',
                'gift_popup_prize'   => 'Free gift',
                'gift_popup_coupon'  => 'GIFT10',
                'gift_popup_delay'   => 2,
            ],
        ],
    ])->assertRedirect();

    expect((int) StoreSetting::get('store_logo_height'))->toBe(72)
        ->and((string) StoreSetting::get('gift_popup_coupon'))->toBe('GIFT10')
        ->and((bool) StoreSetting::get('gift_popup_enabled'))->toBeTrue();

    $logo = StoreSetting::get('store_logo_url');
    expect($logo)->toStartWith('branding/');
    Storage::disk('public')->assertExists($logo);
});
