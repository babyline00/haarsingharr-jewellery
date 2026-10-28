<?php

use App\Mail\ReviewReplyMail;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\HomepageSection;
use App\Models\Order;
use App\Models\ProductQuestion;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Admin marketing & customers tests (Phase 4)
|--------------------------------------------------------------------------
|
| Covers customer segments/tags/GDPR/impersonation, review moderation and
| replies, product Q&A, coupon bundles/usage reporting, banner tracking
| and homepage builder operations.
|
*/

beforeEach(function () {
    $this->actingAs(createAdmin());
});

// --------------------------------------------------------------------------
// Customers
// --------------------------------------------------------------------------

test('customers can be filtered by segments and exported as CSV', function () {
    $repeat = createCustomer(['email' => 'repeat@example.com']);
    $blocked = createCustomer(['email' => 'blocked@example.com']);
    $blocked->forceFill(['status' => 'suspended'])->save();

    // Created individually — nextOrderNumber() is evaluated when each model is built.
    Order::factory()->create([
        'user_id' => $repeat->id,
        'guest_email' => null,
        'status' => 'delivered',
    ]);
    Order::factory()->create([
        'user_id' => $repeat->id,
        'guest_email' => null,
        'status' => 'delivered',
    ]);

    $this->get(route('admin.customers.index', ['segment' => 'repeat']))
        ->assertOk()
        ->assertSee($repeat->email)
        ->assertDontSee($blocked->email);

    $this->get(route('admin.customers.index', ['segment' => 'blocked']))
        ->assertOk()
        ->assertSee($blocked->email);

    $this->get(route('admin.customers.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('customer tags and notes persist on update', function () {
    $customer = createCustomer();

    $this->put(route('admin.customers.update', $customer), [
        'status'            => 'active',
        'marketing_consent' => '1',
        'internal_notes'    => 'Prefers gold over silver.',
        'tags'              => 'vip, weekend, VIP ,',
    ])->assertRedirect();

    $customer->refresh();

    expect($customer->tags)->toBe(['vip', 'weekend'])
        ->and($customer->internal_notes)->toBe('Prefers gold over silver.');
});

test('customers bulk actions update consent, status and route export', function () {
    $first = createCustomer();
    $second = createCustomer();

    $this->post(route('admin.customers.bulk'), [
        'ids' => [$first->id, $second->id],
        'action' => 'consent_on',
    ])->assertRedirect();

    expect($first->fresh()->marketing_consent)->toBeTrue();

    $this->post(route('admin.customers.bulk'), [
        'ids' => [$first->id],
        'action' => 'suspend',
    ])->assertRedirect();

    expect($first->fresh()->status->value)->toBe('suspended');

    $this->post(route('admin.customers.bulk'), [
        'ids' => [$second->id],
        'action' => 'export',
    ])->assertRedirect(route('admin.customers.export', ['ids' => (string) $second->id]));
});

it('impersonates a customer audited and returns staff to the admin panel', function () {
    $customer = createCustomer(['email' => 'shopper@example.com']);
    $admin = auth()->user();

    $this->post(route('admin.customers.impersonate', $customer))
        ->assertRedirect(route('account.dashboard'));

    expect(auth()->id())->toBe($customer->id)
        ->and(session('impersonator_id'))->toBe($admin->id)
        ->and(\App\Models\AuditLog::where('action', 'customers.impersonate.start')
            ->where('target_type', \App\Models\User::class)
            ->where('target_id', $customer->id)->exists())->toBeTrue();

    // The storefront banner is visible while impersonating.
    $this->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Viewing the store as', false);

    $this->post(route('impersonation.exit'))
        ->assertRedirect();

    expect(auth()->id())->toBe($admin->id)
        ->and(session('impersonator_id'))->toBeNull()
        ->and(\App\Models\AuditLog::where('action', 'customers.impersonate.end')
            ->where('target_id', $customer->id)->exists())->toBeTrue();
});

it('refuses to impersonate staff or blocked accounts', function () {
    $admin = auth()->user();
    $staff = User::factory()->admin()->create();
    $blocked = createCustomer(['status' => 'suspended']);

    $this->post(route('admin.customers.impersonate', $staff))->assertForbidden();
    $this->post(route('admin.customers.impersonate', $blocked))->assertForbidden();

    expect(auth()->id())->toBe($admin->id);
});

it('exports a customer data bundle and erases personal data on request', function () {
    $customer = createCustomer(['email' => 'erase.me@example.com', 'first_name' => 'Fatima', 'last_name' => 'Khan']);

    $this->get(route('admin.customers.gdpr', $customer))
        ->assertOk()
        ->assertHeader('content-type', 'application/json');

    $this->post(route('admin.customers.gdpr.erase', $customer))->assertRedirect();

    $customer->refresh();

    expect($customer->email)->not->toBe('erase.me@example.com')
        ->and($customer->first_name)->toBe('Data')
        ->and($customer->status->value)->toBe('deactivated')
        ->and($customer->data_deleted_at)->not->toBeNull();
});

// --------------------------------------------------------------------------
// Reviews
// --------------------------------------------------------------------------

test('reviews can be moderated in bulk and staff replies email the customer', function () {
    Mail::fake();

    $reviewer = createCustomer(['email' => 'reviewer@example.com']);
    $product = Product::factory()->create();
    $guestProduct = Product::factory()->create();

    $one = \App\Models\Review::create([
        'product_id' => $product->id,
        'user_id' => $reviewer->id,
        'rating' => 5,
        'body' => 'Gorgeous piece, precisely as described.',
    ]);

    $two = \App\Models\Review::create([
        'product_id' => $guestProduct->id,
        'customer_name' => 'Nadia',
        'rating' => 3,
        'body' => 'Delivery was slower than expected.',
    ]);

    $this->post(route('admin.reviews.bulk'), [
        'ids' => [$one->id, $two->id],
        'action' => 'approve',
    ])->assertRedirect();

    expect($one->fresh()->is_approved)->toBeTrue()
        ->and($two->fresh()->is_approved)->toBeTrue();

    $this->put(route('admin.reviews.update', $reviewer ? $one : $one), [
        'admin_response' => 'Thank you for the kind words!',
    ])->assertRedirect();

    Mail::assertSent(ReviewReplyMail::class, fn (ReviewReplyMail $mail) => $mail->hasTo('reviewer@example.com'));

    // Guest reviews still save a response but there is no email to send.
    $this->put(route('admin.reviews.update', $two), [
        'admin_response' => 'Sorry about that — we have followed up.',
    ])->assertRedirect();

    Mail::assertSent(ReviewReplyMail::class, 1);
    expect($two->fresh()->admin_response)->toBe('Sorry about that — we have followed up.');
});

// --------------------------------------------------------------------------
// Product questions
// --------------------------------------------------------------------------

it('answers, publishes and deletes product questions', function () {
    $product = Product::factory()->create();
    $asker = createCustomer(['email' => 'asker@example.com']);

    $question = ProductQuestion::create([
        'product_id' => $product->id,
        'user_id' => $asker->id,
        'question' => 'Is this available in size 18k gold?',
    ]);

    $this->get(route('admin.questions.index', ['status' => 'unanswered']))
        ->assertOk()->assertSee($product->name);

    $this->post(route('admin.questions.answer', $question), [
        'answer' => 'Yes — ships with a gold chain of your choice.',
    ])->assertRedirect();

    $question->refresh();
    expect($question->answer)->toBe('Yes — ships with a gold chain of your choice.')
        ->and($question->answered_at)->not->toBeNull();

    $this->post(route('admin.questions.approve', $question))->assertRedirect();
    expect($question->fresh()->is_approved)->toBeTrue();

    $this->delete(route('admin.questions.destroy', $question))->assertRedirect();
    expect(ProductQuestion::find($question->id))->toBeNull();
});

test('product question bulk actions approve and publish', function () {
    $product = Product::factory()->create();

    $one = ProductQuestion::create(['product_id' => $product->id, 'question' => 'First?']);
    $two = ProductQuestion::create(['product_id' => $product->id, 'question' => 'Second?']);

    $this->post(route('admin.questions.bulk'), [
        'ids' => [$one->id, $two->id],
        'action' => 'approve',
    ])->assertRedirect();

    expect($one->fresh()->is_approved)->toBeTrue()
        ->and($two->fresh()->is_approved)->toBeTrue();

    $this->post(route('admin.questions.bulk'), [
        'ids' => [$two->id],
        'action' => 'delete',
    ])->assertRedirect();

    expect(ProductQuestion::find($two->id))->toBeNull();
});

// --------------------------------------------------------------------------
// Coupons
// --------------------------------------------------------------------------

test('bulk coupon generation creates unique codes with the given prefix', function () {
    Coupon::create([
        'code' => 'MOOD-AAAAA',
        'type' => 'fixed',
        'value' => 100,
        'status' => 'active',
    ]);

    $this->get(route('admin.coupons.generate'))->assertOk();

    $this->post(route('admin.coupons.generate.store'), [
        'prefix'      => 'mood',
        'quantity'    => 3,
        'code_length' => 5,
        'type'        => 'fixed',
        'value'       => 250,
        'min_order_value' => 1000,
        'usage_limit_total' => 1,
        'usage_limit_per_customer' => 1,
        'first_order_only' => '1',
        'status'      => 'active',
    ])->assertRedirect();

    $codes = Coupon::where('code', 'like', 'MOOD-%')->pluck('code')->all();

    expect(count($codes))->toBe(4)
        ->and(collect($codes)->unique()->count())->toBe(4);

    $bundle = Coupon::where('code', '!=', 'MOOD-AAAAA')->where('code', 'like', 'MOOD-%')->first();

    expect($bundle->type)->toBe('fixed')
        ->and($bundle->value)->toBe('250.00')
        ->and($bundle->min_order_value)->toBe('1000.00')
        ->and($bundle->usage_limit_total)->toBe(1)
        ->and($bundle->first_order_only)->toBeTrue();
});

it('shows the coupon usage page with redemption rows', function () {
    $coupon = Coupon::factory()->create([
        'type' => 'fixed',
        'value' => 500,
        'used_count' => 1,
    ]);

    $customer = createCustomer(['email' => 'redeemer@example.com']);
    $order = Order::factory()->create(['user_id' => $customer->id, 'guest_email' => null, 'subtotal' => 20000]);

    CouponRedemption::create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'discount_applied' => 500,
        'amount' => 500,
    ]);

    $this->get(route('admin.coupons.show', $coupon))
        ->assertOk()
        ->assertSee($order->number, false)
        ->assertSee($customer->email);

    $this->post(route('admin.coupons.bulk'), [
        'ids' => [$coupon->id],
        'action' => 'disable',
    ])->assertRedirect();

    expect($coupon->fresh()->status)->toBe('disabled');
});

// --------------------------------------------------------------------------
// Banners
// --------------------------------------------------------------------------

it('tracks banner impressions on the homepage and clicks through the storefront', function () {
    $banner = Banner::create([
        'name' => 'Eid Hero',
        'location' => 'hero',
        'image' => 'banners/eid.png',
        'link_url' => 'https://example.com/collection',
        'is_published' => true,
    ]);

    $this->get(route('home'))->assertOk();
    expect($banner->fresh()->impressions)->toBe(1);

    $this->get(route('banners.click', $banner))
        ->assertRedirect('https://example.com/collection');
    expect($banner->fresh()->clicks)->toBe(1);

    // Unpublished banners 404; banners without a link fall back to the shop.
    $banner->update(['is_published' => false]);
    $this->get(route('banners.click', $banner))->assertNotFound();

    $fallback = Banner::create([
        'name' => 'No link',
        'location' => 'hero',
        'image' => 'banners/no-link.png',
        'is_published' => true,
    ]);

    $this->get(route('banners.click', $fallback))->assertRedirect(route('shop.index'));
    expect($fallback->fresh()->clicks)->toBe(1);
});

test('banner alt text persists and banners reorder within their placement', function () {
    $first = Banner::create(['name' => 'One', 'location' => 'hero', 'image' => 'banners/one.png', 'sort_order' => 0]);
    $second = Banner::create(['name' => 'Two', 'location' => 'hero', 'image' => 'banners/two.png', 'sort_order' => 1]);

    $this->put(route('admin.banners.update', $first), [
        'name' => 'One',
        'location' => 'hero',
        'image' => 'banners/one.png',
        'alt_text' => 'Eid jewellery collection on silk cloth',
        'is_published' => '1',
    ])->assertRedirect();

    expect($first->fresh()->alt_text)->toBe('Eid jewellery collection on silk cloth');

    $this->post(route('admin.banners.move', [$second, 'up']))->assertRedirect();

    expect($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);

    $this->post(route('admin.banners.bulk'), [
        'ids' => [$first->id, $second->id],
        'action' => 'unpublish',
    ])->assertRedirect();

    expect($first->fresh()->is_published)->toBeFalse()
        ->and($second->fresh()->is_published)->toBeFalse();
});

// --------------------------------------------------------------------------
// Homepage builder
// --------------------------------------------------------------------------

test('homepage sections can be duplicated as drafts, reordered and bulk-published', function () {
    $first = HomepageSection::create([
        'key' => 'best-sellers',
        'title' => 'Bestsellers',
        'sort_order' => 0,
        'is_published' => true,
    ]);
    $second = HomepageSection::create([
        'key' => 'new-arrivals',
        'title' => 'New arrivals',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $this->post(route('admin.homepage-sections.duplicate', $first))->assertRedirect();

    $copy = HomepageSection::where('key', 'like', 'copy-%')->first();

    expect($copy)->not->toBeNull()
        ->and($copy->is_published)->toBeFalse()
        ->and($copy->title)->toContain('Bestsellers');

    $this->post(route('admin.homepage-sections.move', [$second, 'up']))->assertRedirect();

    expect($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);

    $this->post(route('admin.homepage-sections.bulk'), [
        'ids' => [$copy->id],
        'action' => 'publish',
    ])->assertRedirect();

    expect($copy->fresh()->is_published)->toBeTrue();

    $this->post(route('admin.homepage-sections.bulk'), [
        'ids' => [$copy->id],
        'action' => 'delete',
    ])->assertRedirect();

    expect(HomepageSection::find($copy->id))->toBeNull();
});
