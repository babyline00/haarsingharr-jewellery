<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboard;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ReviewController;
use App\Http\Controllers\Account\WishlistController;
use App\Http\Controllers\Account\ReturnController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExchangeRequestController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreLocationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Checkout\CartController;
use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Checkout\PaymentController;
use App\Http\Controllers\Storefront\AboutController;
use App\Http\Controllers\Storefront\CartPageController;
use App\Http\Controllers\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\Storefront\CollectionController as StorefrontCollectionController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\CmsPageController as StorefrontCmsController;
use App\Http\Controllers\Storefront\FaqController as StorefrontFaqController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\OrderTrackingController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\StoreLocationController as StorefrontStoreLocationController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// --------------------------------------------------------------------------
// Public storefront
// --------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [StorefrontProductController::class, 'index'])->name('shop.index');
Route::get('/best-sellers', [StorefrontProductController::class, 'bestSellers'])->name('shop.best_sellers');
Route::get('/new-arrivals', [StorefrontProductController::class, 'newArrivals'])->name('shop.new_arrivals');
Route::get('/sale', [StorefrontProductController::class, 'sale'])->name('shop.sale');
Route::get('/bridal', [StorefrontProductController::class, 'bridal'])->name('shop.bridal');
Route::get('/men', [StorefrontProductController::class, 'men'])->name('shop.men');
Route::get('/shop-by-budget/{range?}', [StorefrontProductController::class, 'budget'])->name('shop.budget');

Route::get('/categories/{category:slug}', [StorefrontCategoryController::class, 'show'])->name('categories.show');
Route::get('/collections/{collection:slug}', [StorefrontCollectionController::class, 'show'])->name('collections.show');
Route::get('/products/{product:slug}', [StorefrontProductController::class, 'show'])->name('products.show');

Route::get('/search', [SearchController::class, 'index'])->name('search.index');

Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact,5,15')
    ->name('contact.store');

Route::get('/faq', [StorefrontFaqController::class, 'index'])->name('faq');
Route::get('/locations', [StorefrontStoreLocationController::class, 'index'])->name('locations');
Route::get('/pages/{page:slug}', [StorefrontCmsController::class, 'show'])->name('pages.show');

// Convenience redirects for legal pages
Route::get('/shipping-policy', fn () => redirect()->route('pages.show', 'shipping-policy'));
Route::get('/exchange-policy', fn () => redirect()->route('pages.show', 'exchange-policy'));
Route::get('/privacy-policy',  fn () => redirect()->route('pages.show', 'privacy-policy'));
Route::get('/terms',           fn () => redirect()->route('pages.show', 'terms'));
Route::get('/jewellery-care',  fn () => redirect()->route('pages.show', 'jewellery-care'));
Route::get('/size-guides',     fn () => redirect()->route('pages.show', 'size-guides'));

// Newsletter subscribe
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:newsletter,3,10')
    ->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

// Order tracking — public but token-protected
Route::get('/track-order', [OrderTrackingController::class, 'form'])->name('order.tracking.form');
Route::post('/track-order', [OrderTrackingController::class, 'lookup'])->name('order.tracking.lookup');

// --------------------------------------------------------------------------
// Auth (customer + staff share the routes; the Staff middleware filters
// back-office access)
// --------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.attempt');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:register,3,30')
        ->name('register.store');

    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:reset')
        ->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// --------------------------------------------------------------------------
// Cart & checkout
// --------------------------------------------------------------------------
Route::get('/cart', [CartPageController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])
    ->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])
    ->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])
    ->name('cart.items.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout,5,1')
    ->name('checkout.store');
Route::get('/order-confirmation/{order:number}', [CheckoutController::class, 'confirmation'])
    ->name('order.confirmation');

// --------------------------------------------------------------------------
// Payment
// --------------------------------------------------------------------------
Route::post('/payments/{payment}/initiate', [PaymentController::class, 'initiate'])
    ->name('payments.initiate');
Route::get('/payments/return/{provider}', [PaymentController::class, 'return'])
    ->name('payments.return');

// Webhooks — no session, no CSRF; rely on signature verification
Route::post('/webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle'])
    ->withoutMiddleware(['csrf']);

// --------------------------------------------------------------------------
// Customer account
// --------------------------------------------------------------------------
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountDashboard::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:number}', [AccountOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [AccountOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/items', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/items/{item}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::patch('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:review,5,60')
        ->name('reviews.store');
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
});

// Wishlist — public add for guests too
Route::post('/wishlist/items', [WishlistController::class, 'store'])
    ->name('wishlist.add.public');

// --------------------------------------------------------------------------
// Admin — protected by auth + staff + (optional) MFA
// --------------------------------------------------------------------------
$adminPath = config('app.admin_path', 'admin');

Route::middleware(['auth', 'staff', 'mfa'])
    ->prefix($adminPath)
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Products & variants
        Route::resource('products', AdminProductController::class);
        Route::post('products/{product}/variants', [AdminProductController::class, 'storeVariant'])
            ->name('products.variants.store');
        Route::patch('products/{product}/variants/{variant}', [AdminProductController::class, 'updateVariant'])
            ->name('products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [AdminProductController::class, 'destroyVariant'])
            ->name('products.variants.destroy');

        // Categories & collections
        Route::resource('categories', CategoryController::class);
        Route::resource('collections', CollectionController::class);

        // Inventory
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

        // Orders
        Route::resource('orders', AdminOrderController::class)->except(['create', 'store']);
        Route::post('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.status.update');
        Route::post('orders/{order}/shipments', [AdminOrderController::class, 'storeShipment'])
            ->name('orders.shipments.store');
        Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])
            ->name('orders.invoice');
        Route::get('orders/{order}/packing-slip', [AdminOrderController::class, 'packingSlip'])
            ->name('orders.packing_slip');

        // Payments & refunds
        Route::resource('payments', AdminPaymentController::class)->only(['index', 'show']);
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])
            ->middleware('permission:refunds.approve')
            ->name('refunds.approve');
        Route::resource('refunds', RefundController::class)->only(['index', 'show', 'store']);

        // Customers
        Route::resource('customers', CustomerController::class)->only(['index', 'show', 'update', 'destroy']);

        // Coupons
        Route::resource('coupons', CouponController::class);

        // Reviews
        Route::resource('reviews', AdminReviewController::class)->only(['index', 'show', 'update']);
        Route::post('reviews/{review}/approve', [AdminReviewController::class, 'approve'])
            ->name('reviews.approve');
        Route::post('reviews/{review}/reject', [AdminReviewController::class, 'reject'])
            ->name('reviews.reject');

        // CMS
        Route::resource('pages', CmsPageController::class);
        Route::resource('faqs', FaqController::class)->except(['show']);
        Route::resource('homepage-sections', HomepageSectionController::class);
        Route::resource('banners', BannerController::class);

        // Shipping & store locations
        Route::resource('store-locations', StoreLocationController::class);
        Route::resource('shipping', ShippingController::class)->only(['index', 'store']);

        // SEO
        Route::get('seo', [SettingsController::class, 'seoIndex'])->name('seo.index');
        Route::post('seo/redirects', [SettingsController::class, 'storeRedirect'])->name('seo.redirects.store');
        Route::delete('seo/redirects/{redirect}', [SettingsController::class, 'destroyRedirect'])
            ->name('seo.redirects.destroy');

        // Staff & roles
        Route::resource('staff', StaffController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('users', UserController::class)->except(['create', 'store']);

        // Exchange requests
        Route::resource('exchanges', ExchangeRequestController::class)->only(['index', 'show', 'update']);

        // Reports
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
        Route::get('reports/staff-activity', [ReportController::class, 'staffActivity'])
            ->name('reports.staff_activity');
        Route::get('reports/export/{type}', [ReportController::class, 'export'])
            ->name('reports.export');

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/payments', [SettingsController::class, 'paymentSettings'])
            ->name('settings.payments');
        Route::post('settings/payments', [SettingsController::class, 'updatePaymentSettings'])
            ->name('settings.payments.update');

        // Audit
        Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show']);
        Route::get('security-events', [AuditLogController::class, 'securityEvents'])
            ->name('security-events.index');
        Route::get('active-sessions', [AuditLogController::class, 'activeSessions'])
            ->name('active-sessions.index');
    });

// --------------------------------------------------------------------------
// Sitemap & robots
// --------------------------------------------------------------------------
Route::get('/sitemap.xml', [\App\Http\Controllers\Storefront\SitemapController::class, 'index'])
    ->name('sitemap.index');
Route::get('/robots.txt', [\App\Http\Controllers\Storefront\RobotsController::class, 'index'])
    ->name('robots.index');
