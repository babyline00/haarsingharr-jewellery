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
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\ContactEnquiryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ExchangeRequestController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GiftController as AdminGiftController;
use App\Http\Controllers\Admin\GiveawayController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NavigationMenuController;
use App\Http\Controllers\Admin\NewsletterSubscriptionController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductQuestionController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreLocationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Checkout\CartController;
use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Checkout\PaymentController;
use App\Http\Controllers\Storefront\AboutController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartPageController;
use App\Http\Controllers\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\Storefront\CollectionController as StorefrontCollectionController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\CmsPageController as StorefrontCmsController;
use App\Http\Controllers\Storefront\FaqController as StorefrontFaqController;
use App\Http\Controllers\Storefront\GiftController;
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
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');

Route::get('/faq', [StorefrontFaqController::class, 'index'])->name('faq');
Route::get('/locations', [StorefrontStoreLocationController::class, 'index'])->name('locations');
Route::get('/pages/{page:slug}', [StorefrontCmsController::class, 'show'])->name('pages.show');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Convenience redirects for legal pages
Route::get('/shipping-policy', fn () => redirect()->route('pages.show', 'shipping-policy'));
Route::get('/exchange-policy', fn () => redirect()->route('pages.show', 'exchange-policy'));
Route::get('/privacy-policy',  fn () => redirect()->route('pages.show', 'privacy-policy'));
Route::get('/terms',           fn () => redirect()->route('pages.show', 'terms'));
Route::get('/jewellery-care',  fn () => redirect()->route('pages.show', 'jewellery-care'));
Route::get('/size-guides',     fn () => redirect()->route('pages.show', 'size-guides'));

// Newsletter subscribe
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:newsletter')
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
        ->middleware('throttle:register')
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

// Email verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

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

// Homepage gift popup (unique coupon issue + analytics)
Route::post('/gift/claim', [GiftController::class, 'claim'])
    ->middleware('throttle:gift')
    ->name('gift.claim');
Route::post('/gift/event', [GiftController::class, 'event'])
    ->middleware('throttle:gift')
    ->name('gift.event');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');
Route::get('/order-confirmation/{order:number}', [CheckoutController::class, 'confirmation'])
    ->name('order.confirmation');

// --------------------------------------------------------------------------
// Payment
// --------------------------------------------------------------------------
Route::post('/payments/{payment}/initiate', [PaymentController::class, 'initiate'])
    ->name('payments.initiate');
Route::get('/payments/redirect', [PaymentController::class, 'redirectForm'])
    ->name('payments.redirect.form');
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
    Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:number}', [AccountOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [AccountOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/items', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/items/{item}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::get('/addresses/{address}/fill', [AddressController::class, 'fill'])->name('addresses.fill');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::patch('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:review')
        ->name('reviews.store');
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
    Route::get('/notifications', [\App\Http\Controllers\Storefront\AccountNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Storefront\AccountNotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// Wishlist — public add for guests too
Route::post('/wishlist/items', [WishlistController::class, 'store'])
    ->name('wishlist.add.public');

// Impersonation exit — must be reachable while browsing as a customer
Route::post('/impersonation/exit', [\App\Http\Controllers\Admin\CustomerController::class, 'exitImpersonation'])
    ->middleware('auth')
    ->name('impersonation.exit');

// Banner click tracking
Route::get('/banners/{banner}/click', [\App\Http\Controllers\Storefront\BannerClickController::class, 'click'])
    ->name('banners.click');

// --------------------------------------------------------------------------
// Admin — protected by auth + staff + (optional) MFA
// --------------------------------------------------------------------------
$adminPath = config('app.admin_path', 'admin');

Route::middleware(['auth', 'staff', 'mfa'])
    ->prefix($adminPath)
    ->name('admin.')
    ->group(function () {
        Route::get('mfa/enroll', [\App\Http\Controllers\Admin\MfaController::class, 'enroll'])->name('mfa.enroll');
        Route::post('mfa/enroll', [\App\Http\Controllers\Admin\MfaController::class, 'confirmEnrollment'])
            ->middleware('throttle:6,1')->name('mfa.confirm');
        Route::get('mfa/challenge', [\App\Http\Controllers\Admin\MfaController::class, 'challenge'])->name('mfa.challenge');
        Route::post('mfa/challenge', [\App\Http\Controllers\Admin\MfaController::class, 'verify'])
            ->middleware('throttle:6,1')->name('mfa.verify');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('search', [AdminSearchController::class, 'index'])->name('search');
        Route::get('search/suggest', [AdminSearchController::class, 'suggest'])->name('search.suggest');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notifications.read');

        Route::get('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');

        // Products & variants
<<<<<<< HEAD
        Route::get('products/export', [AdminProductController::class, 'export'])
            ->middleware('permission:products.view')->name('products.export');
        Route::get('products/import', [AdminProductController::class, 'importForm'])
            ->middleware('permission:products.create')->name('products.import');
        Route::post('products/import/preview', [AdminProductController::class, 'importPreview'])
            ->middleware('permission:products.create')->name('products.import.preview');
        Route::post('products/import', [AdminProductController::class, 'importStore'])
            ->middleware('permission:products.create')->name('products.import.store');
        Route::post('products/bulk', [AdminProductController::class, 'bulk'])
            ->middleware('permission:products.update|products.delete')->name('products.bulk');

=======
>>>>>>> origin/main
        Route::resource('products', AdminProductController::class)
            ->middlewareFor(['index', 'show'], 'permission:products.view')
            ->middlewareFor(['create', 'store'], 'permission:products.create')
            ->middlewareFor(['edit', 'update'], 'permission:products.update')
            ->middlewareFor(['destroy'], 'permission:products.delete');
<<<<<<< HEAD
        Route::post('products/{product}/duplicate', [AdminProductController::class, 'duplicate'])
            ->middleware('permission:products.create')->name('products.duplicate');
        Route::post('products/{product}/variants/generate', [AdminProductController::class, 'generateVariants'])
            ->middleware('permission:products.create')->name('products.variants.generate');
        Route::post('products/{product}/relations', [AdminProductController::class, 'storeRelation'])
            ->middleware('permission:products.update')->name('products.relations.store');
        Route::delete('products/{product}/relations/{related}', [AdminProductController::class, 'destroyRelation'])
            ->middleware('permission:products.update')->name('products.relations.destroy');
=======
>>>>>>> origin/main
        Route::post('products/{product}/variants', [AdminProductController::class, 'storeVariant'])
            ->middleware('permission:products.create')
            ->name('products.variants.store');
        Route::patch('products/{product}/variants/{variant}', [AdminProductController::class, 'updateVariant'])
            ->middleware('permission:products.update')
            ->name('products.variants.update');
        Route::patch('products/{product}/variants/{variant}/default', [AdminProductController::class, 'setDefaultVariant'])
            ->middleware('permission:products.update')
            ->name('products.variants.default');
        Route::delete('products/{product}/variants/{variant}', [AdminProductController::class, 'destroyVariant'])
            ->middleware('permission:products.delete')
            ->name('products.variants.destroy');
        Route::post('products/{product}/images', [AdminProductController::class, 'storeImage'])
            ->middleware('permission:products.update')
            ->name('products.images.store');
        Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])
            ->middleware('permission:products.update')
            ->name('products.images.destroy');
        Route::patch('products/{product}/images/{image}/primary', [AdminProductController::class, 'setPrimaryImage'])
            ->middleware('permission:products.update')
            ->name('products.images.primary');
<<<<<<< HEAD

        // Categories & collections
        Route::post('categories/bulk', [CategoryController::class, 'bulk'])
            ->middleware('permission:categories.update|categories.delete')->name('categories.bulk');
=======
        Route::post('products/{product}/videos', [AdminProductController::class, 'storeVideo'])
            ->middleware('permission:products.update')
            ->name('products.videos.store');
        Route::delete('products/{product}/videos/{video}', [AdminProductController::class, 'destroyVideo'])
            ->middleware('permission:products.update')
            ->name('products.videos.destroy');
        Route::patch('products/{product}/videos/{video}/primary', [AdminProductController::class, 'setPrimaryVideo'])
            ->middleware('permission:products.update')
            ->name('products.videos.primary');

        // Categories & collections
>>>>>>> origin/main
        Route::resource('categories', CategoryController::class)
            ->middlewareFor(['index', 'show'], 'permission:categories.view')
            ->middlewareFor(['create', 'store'], 'permission:categories.create')
            ->middlewareFor(['edit', 'update'], 'permission:categories.update')
            ->middlewareFor(['destroy'], 'permission:categories.delete');
<<<<<<< HEAD
        Route::post('categories/{category}/move/{direction}', [CategoryController::class, 'move'])
            ->middleware('permission:categories.update')->name('categories.move');
=======
>>>>>>> origin/main
        Route::resource('collections', CollectionController::class)
            ->middlewareFor(['index', 'show'], 'permission:collections.view')
            ->middlewareFor(['create', 'store'], 'permission:collections.create')
            ->middlewareFor(['edit', 'update'], 'permission:collections.update')
            ->middlewareFor(['destroy'], 'permission:collections.delete');
<<<<<<< HEAD
        Route::post('collections/{collection}/products', [CollectionController::class, 'attachProduct'])
            ->middleware('permission:collections.update')->name('collections.products.store');
        Route::delete('collections/{collection}/products/{product}', [CollectionController::class, 'detachProduct'])
            ->middleware('permission:collections.update')->name('collections.products.destroy');
        Route::post('collections/{collection}/products/{product}/move/{direction}', [CollectionController::class, 'moveProduct'])
            ->middleware('permission:collections.update')->name('collections.products.move');

        // Inventory
        Route::get('inventory/export', [InventoryController::class, 'export'])
            ->middleware('permission:inventory.view')->name('inventory.export');
        Route::get('inventory/import', [InventoryController::class, 'importForm'])
            ->middleware('permission:inventory.update')->name('inventory.import');
        Route::post('inventory/import', [InventoryController::class, 'importStore'])
            ->middleware('permission:inventory.update')->name('inventory.import.store');
        Route::post('inventory/bulk', [InventoryController::class, 'bulk'])
            ->middleware('permission:inventory.update')->name('inventory.bulk');
        Route::post('inventory/alerts/{alert}/resolve', [InventoryController::class, 'resolveAlert'])
            ->middleware('permission:inventory.update')->name('inventory.alerts.resolve');
=======

        // Inventory
>>>>>>> origin/main
        Route::get('inventory', [InventoryController::class, 'index'])
            ->middleware('permission:inventory.view')->name('inventory.index');
        Route::post('inventory/adjust', [InventoryController::class, 'adjust'])
            ->middleware('permission:inventory.update')->name('inventory.adjust');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])
            ->middleware('permission:inventory.view')->name('inventory.movements');

        // Orders
<<<<<<< HEAD
        Route::get('orders/export', [AdminOrderController::class, 'export'])
            ->middleware('permission:orders.view|orders.export')
            ->name('orders.export');
        Route::get('orders/print', [AdminOrderController::class, 'printOrders'])
            ->middleware('permission:orders.view')
            ->name('orders.print');
        Route::post('orders/bulk', [AdminOrderController::class, 'bulk'])
            ->middleware('permission:orders.update|orders.export')
            ->name('orders.bulk');
        Route::resource('orders', AdminOrderController::class)->except(['create', 'store', 'edit', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:orders.view');
=======
        Route::resource('orders', AdminOrderController::class)->except(['create', 'store'])
            ->middlewareFor(['index', 'show'], 'permission:orders.view')
            ->middlewareFor(['edit', 'update'], 'permission:orders.update')
            ->middlewareFor(['destroy'], 'permission:orders.delete');
>>>>>>> origin/main
        Route::post('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update')
            ->name('orders.status.update');
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])
            ->middleware('permission:orders.cancel')
            ->name('orders.cancel');
        Route::post('orders/{order}/notes', [AdminOrderController::class, 'storeNote'])
            ->middleware('permission:orders.update')
            ->name('orders.notes.store');
        Route::delete('orders/{order}/notes/{note}', [AdminOrderController::class, 'destroyNote'])
            ->middleware('permission:orders.update')
            ->name('orders.notes.destroy');
        Route::put('orders/{order}/addresses/{type}', [AdminOrderController::class, 'updateAddress'])
            ->middleware('permission:orders.update')
            ->name('orders.addresses.update');
        Route::patch('orders/{order}/items/{item}/fulfilment', [AdminOrderController::class, 'updateItemFulfilment'])
            ->middleware('permission:orders.update')
            ->name('orders.items.fulfilment');
        Route::post('orders/{order}/payments', [AdminOrderController::class, 'storePayment'])
            ->middleware('permission:payments.update')
            ->name('orders.payments.store');
        Route::post('orders/{order}/emails', [AdminOrderController::class, 'resendEmail'])
            ->middleware('permission:orders.update')
            ->name('orders.emails.send');
        Route::post('orders/{order}/shipments', [AdminOrderController::class, 'storeShipment'])
            ->middleware('permission:orders.ship')
            ->name('orders.shipments.store');
        Route::put('orders/{order}/shipments/{shipment}', [AdminOrderController::class, 'updateShipment'])
            ->middleware('permission:orders.ship')
            ->name('orders.shipments.update');
        Route::delete('orders/{order}/shipments/{shipment}', [AdminOrderController::class, 'destroyShipment'])
            ->middleware('permission:orders.ship')
            ->name('orders.shipments.destroy');
        Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])
            ->middleware('permission:orders.view')
            ->name('orders.invoice');
        Route::get('orders/{order}/packing-slip', [AdminOrderController::class, 'packingSlip'])
            ->middleware('permission:orders.view')
            ->name('orders.packing_slip');

        // Payments & refunds
<<<<<<< HEAD
        Route::get('payments/export', [AdminPaymentController::class, 'export'])
            ->middleware('permission:payments.view|payments.export')
            ->name('payments.export');
        Route::post('payments/{payment}/capture', [AdminPaymentController::class, 'capture'])
            ->middleware('permission:payments.update')
            ->name('payments.capture');
        Route::post('payments/{payment}/fail', [AdminPaymentController::class, 'fail'])
            ->middleware('permission:payments.update')
            ->name('payments.fail');
        Route::resource('payments', AdminPaymentController::class)->only(['index', 'show'])
            ->middlewareFor(['index', 'show'], 'permission:payments.view');
        Route::get('refunds/export', [RefundController::class, 'export'])
            ->middleware('permission:refunds.view|refunds.export')
            ->name('refunds.export');
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])
            ->middleware('permission:refunds.approve')
            ->name('refunds.approve');
        Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])
            ->middleware('permission:refunds.update')
            ->name('refunds.reject');
        Route::post('refunds/{refund}/complete', [RefundController::class, 'complete'])
            ->middleware('permission:refunds.update')
            ->name('refunds.complete');
        Route::get('refunds/{refund}/receipt', [RefundController::class, 'receipt'])
            ->middleware('permission:refunds.view')
            ->name('refunds.receipt');
=======
        Route::resource('payments', AdminPaymentController::class)->only(['index', 'show'])
            ->middlewareFor(['index', 'show'], 'permission:payments.view');
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])
            ->middleware('permission:refunds.approve')
            ->name('refunds.approve');
>>>>>>> origin/main
        Route::resource('refunds', RefundController::class)->only(['index', 'show', 'store'])
            ->middlewareFor(['index', 'show'], 'permission:refunds.view')
            ->middlewareFor(['store'], 'permission:refunds.create');

        // Customers
<<<<<<< HEAD
        Route::get('customers/export', [CustomerController::class, 'export'])
            ->middleware('permission:customers.view|customers.export')->name('customers.export');
        Route::post('customers/bulk', [CustomerController::class, 'bulk'])
            ->middleware('permission:customers.update|customers.delete')->name('customers.bulk');
        Route::get('customers/{customer}/gdpr-export', [CustomerController::class, 'gdprExport'])
            ->middleware('permission:customers.view')->name('customers.gdpr');
        Route::post('customers/{customer}/gdpr-erase', [CustomerController::class, 'gdprErase'])
            ->middleware('permission:customers.delete')->name('customers.gdpr.erase');
        Route::post('customers/{customer}/impersonate', [CustomerController::class, 'impersonate'])
            ->middleware('permission:customers.update')->name('customers.impersonate');
=======
>>>>>>> origin/main
        Route::resource('customers', CustomerController::class)->only(['index', 'show', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:customers.view')
            ->middlewareFor(['update'], 'permission:customers.update')
            ->middlewareFor(['destroy'], 'permission:customers.delete');

        // Coupons
<<<<<<< HEAD
        Route::get('coupons/export', [CouponController::class, 'export'])
            ->middleware('permission:coupons.view|coupons.export')->name('coupons.export');
        Route::get('coupons/generate', [CouponController::class, 'generateForm'])
            ->middleware('permission:coupons.create')->name('coupons.generate');
        Route::post('coupons/generate', [CouponController::class, 'generateStore'])
            ->middleware('permission:coupons.create')->name('coupons.generate.store');
        Route::post('coupons/bulk', [CouponController::class, 'bulk'])
            ->middleware('permission:coupons.update|coupons.delete')->name('coupons.bulk');
=======
>>>>>>> origin/main
        Route::resource('coupons', CouponController::class)
            ->middlewareFor(['index', 'show'], 'permission:coupons.view')
            ->middlewareFor(['create', 'store'], 'permission:coupons.create')
            ->middlewareFor(['edit', 'update'], 'permission:coupons.update')
            ->middlewareFor(['destroy'], 'permission:coupons.delete');
<<<<<<< HEAD

        // Reviews
        Route::post('reviews/bulk', [AdminReviewController::class, 'bulk'])
            ->middleware('permission:reviews.moderate')->name('reviews.bulk');
=======

        // Gift popup analytics & giveaways
        Route::get('gifts', [AdminGiftController::class, 'index'])
            ->middleware('permission:coupons.view')
            ->name('gifts.index');
        Route::resource('giveaways', GiveawayController::class)
            ->middlewareFor(['index', 'show'], 'permission:coupons.view')
            ->middlewareFor(['create', 'store'], 'permission:coupons.create')
            ->middlewareFor(['edit', 'update'], 'permission:coupons.update')
            ->middlewareFor(['destroy'], 'permission:coupons.delete');
        Route::post('giveaways/{giveaway}/entries', [GiveawayController::class, 'addEntry'])
            ->middleware('permission:coupons.update')
            ->name('giveaways.entries.store');
        Route::delete('giveaways/{giveaway}/entries/{entry}', [GiveawayController::class, 'removeEntry'])
            ->middleware('permission:coupons.update')
            ->name('giveaways.entries.destroy');
        Route::post('giveaways/{giveaway}/draw', [GiveawayController::class, 'draw'])
            ->middleware('permission:coupons.update')
            ->name('giveaways.draw');

        // Reviews
>>>>>>> origin/main
        Route::resource('reviews', AdminReviewController::class)->only(['index', 'show', 'update'])
            ->middlewareFor(['index', 'show'], 'permission:reviews.view')
            ->middlewareFor(['update'], 'permission:reviews.update');
        Route::post('reviews/{review}/approve', [AdminReviewController::class, 'approve'])
            ->middleware('permission:reviews.moderate')
            ->name('reviews.approve');
        Route::post('reviews/{review}/reject', [AdminReviewController::class, 'reject'])
            ->middleware('permission:reviews.moderate')
            ->name('reviews.reject');

        // Product questions (Q&A moderation)
        Route::post('questions/bulk', [ProductQuestionController::class, 'bulk'])
            ->middleware('permission:questions.update|questions.delete')->name('questions.bulk');
        Route::resource('questions', ProductQuestionController::class)->only(['index'])
            ->middlewareFor(['index'], 'permission:questions.view|reviews.view');
        Route::post('questions/{question}/answer', [ProductQuestionController::class, 'answer'])
            ->middleware('permission:questions.update|reviews.moderate')->name('questions.answer');
        Route::post('questions/{question}/approve', [ProductQuestionController::class, 'approve'])
            ->middleware('permission:questions.update|reviews.moderate')->name('questions.approve');
        Route::post('questions/{question}/reject', [ProductQuestionController::class, 'reject'])
            ->middleware('permission:questions.update|reviews.moderate')->name('questions.reject');
        Route::delete('questions/{question}', [ProductQuestionController::class, 'destroy'])
            ->middleware('permission:questions.delete')->name('questions.destroy');

        // CMS
        Route::resource('pages', CmsPageController::class)
            ->middlewareFor(['index', 'show'], 'permission:cms.view')
            ->middlewareFor(['create', 'store'], 'permission:cms.create')
            ->middlewareFor(['edit', 'update'], 'permission:cms.update')
            ->middlewareFor(['destroy'], 'permission:cms.delete');
        Route::resource('faqs', FaqController::class)->except(['show'])
            ->middlewareFor(['index'], 'permission:faqs.view')
            ->middlewareFor(['create', 'store'], 'permission:faqs.create')
            ->middlewareFor(['edit', 'update'], 'permission:faqs.update')
            ->middlewareFor(['destroy'], 'permission:faqs.delete');
<<<<<<< HEAD

        // Blog
        Route::post('blog/bulk', [BlogPostController::class, 'bulk'])
            ->middleware('permission:blog.update|blog.delete')->name('blog.bulk');
        Route::get('blog/{blog_post}/preview', [BlogPostController::class, 'preview'])
            ->middleware('permission:blog.view')->name('blog.preview');
        Route::resource('blog', BlogPostController::class)->except(['show'])
            ->parameter('blog', 'blog_post')
            ->middlewareFor(['index'], 'permission:blog.view')
            ->middlewareFor(['create', 'store'], 'permission:blog.create')
            ->middlewareFor(['edit', 'update'], 'permission:blog.update')
            ->middlewareFor(['destroy'], 'permission:blog.delete');

        // Navigation menus
        Route::resource('menus', NavigationMenuController::class)
            ->middlewareFor(['index'], 'permission:menus.view')
            ->middlewareFor(['create', 'store'], 'permission:menus.create')
            ->middlewareFor(['edit', 'update'], 'permission:menus.update')
            ->middlewareFor(['destroy'], 'permission:menus.delete');

        // Email templates
        Route::resource('emails', EmailTemplateController::class)->only(['index', 'edit', 'update'])
            ->parameter('emails', 'email_template')
            ->middlewareFor(['index'], 'permission:email_templates.view')
            ->middlewareFor(['edit', 'update'], 'permission:email_templates.update');
        Route::get('emails/{email_template}/preview', [EmailTemplateController::class, 'preview'])
            ->middleware('permission:email_templates.view')->name('emails.preview');
        Route::post('emails/{email_template}/test-send', [EmailTemplateController::class, 'testSend'])
            ->middleware('permission:email_templates.update')->name('emails.test-send');
        Route::post('emails/{email_template}/toggle', [EmailTemplateController::class, 'toggle'])
            ->middleware('permission:email_templates.update')->name('emails.toggle');

        // Contact enquiries inbox
        Route::get('enquiries/export', [ContactEnquiryController::class, 'export'])
            ->middleware('permission:contacts.export|contacts.view')->name('enquiries.export');
        Route::post('enquiries/bulk', [ContactEnquiryController::class, 'bulk'])
            ->middleware('permission:contacts.update|contacts.delete')->name('enquiries.bulk');
        Route::post('enquiries/{enquiry}/assign', [ContactEnquiryController::class, 'assign'])
            ->middleware('permission:contacts.update')->name('enquiries.assign');
        Route::post('enquiries/{enquiry}/reply', [ContactEnquiryController::class, 'reply'])
            ->middleware('permission:contacts.update')->name('enquiries.reply');
        Route::resource('enquiries', ContactEnquiryController::class)->only(['index', 'show', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:contacts.view')
            ->middlewareFor(['destroy'], 'permission:contacts.delete');

        // Newsletter subscribers
        Route::get('newsletter/export', [NewsletterSubscriptionController::class, 'export'])
            ->middleware('permission:newsletter.export|newsletter.view')->name('newsletter.export');
        Route::post('newsletter/bulk', [NewsletterSubscriptionController::class, 'bulk'])
            ->middleware('permission:newsletter.update|newsletter.delete')->name('newsletter.bulk');
        Route::get('newsletter', [NewsletterSubscriptionController::class, 'index'])
            ->middleware('permission:newsletter.view')->name('newsletter.index');

        // CMS revisions & live preview
        Route::get('pages/{page}/revisions', [CmsPageController::class, 'revisions'])
            ->middleware('permission:cms.view')->name('pages.revisions');
        Route::post('pages/{page}/revisions/{revision}/restore', [CmsPageController::class, 'restore'])
            ->middleware('permission:cms.update')->name('pages.revisions.restore');
        Route::get('pages/{page}/preview', [CmsPageController::class, 'preview'])
            ->middleware('permission:cms.view')->name('pages.preview');

=======
>>>>>>> origin/main
        Route::resource('homepage-sections', HomepageSectionController::class)
            ->middlewareFor(['index', 'show'], 'permission:homepage.view')
            ->middlewareFor(['create', 'store'], 'permission:homepage.create')
            ->middlewareFor(['edit', 'update'], 'permission:homepage.update')
            ->middlewareFor(['destroy'], 'permission:homepage.delete');
<<<<<<< HEAD
        Route::post('homepage-sections/bulk', [HomepageSectionController::class, 'bulk'])
            ->middleware('permission:homepage.update|homepage.delete')->name('homepage-sections.bulk');
        Route::post('homepage-sections/{homepage_section}/duplicate', [HomepageSectionController::class, 'duplicate'])
            ->middleware('permission:homepage.create')->name('homepage-sections.duplicate');
        Route::post('homepage-sections/{homepage_section}/move/{direction}', [HomepageSectionController::class, 'move'])
            ->middleware('permission:homepage.update')->name('homepage-sections.move');
=======
>>>>>>> origin/main
        Route::resource('banners', BannerController::class)
            ->middlewareFor(['index', 'show'], 'permission:banners.view')
            ->middlewareFor(['create', 'store'], 'permission:banners.create')
            ->middlewareFor(['edit', 'update'], 'permission:banners.update')
            ->middlewareFor(['destroy'], 'permission:banners.delete');
<<<<<<< HEAD
        Route::post('banners/bulk', [BannerController::class, 'bulk'])
            ->middleware('permission:banners.update|banners.delete')->name('banners.bulk');
        Route::post('banners/{banner}/move/{direction}', [BannerController::class, 'move'])
            ->middleware('permission:banners.update')->name('banners.move');
=======
>>>>>>> origin/main
        Route::post('banners/{banner}/image', [BannerController::class, 'uploadImage'])
            ->middleware('permission:banners.update')
            ->name('banners.image.upload');

        // Shipping & store locations
        Route::resource('store-locations', StoreLocationController::class)
            ->middlewareFor(['index', 'show'], 'permission:store_locations.view')
            ->middlewareFor(['create', 'store'], 'permission:store_locations.create')
            ->middlewareFor(['edit', 'update'], 'permission:store_locations.update')
            ->middlewareFor(['destroy'], 'permission:store_locations.delete');
        Route::resource('shipping', ShippingController::class)->only(['index', 'store'])
            ->middlewareFor(['index'], 'permission:shipping.view')
            ->middlewareFor(['store'], 'permission:shipping.update');
<<<<<<< HEAD
        Route::post('shipping/zones', [ShippingController::class, 'storeZone'])
            ->middleware('permission:shipping.update')->name('shipping.zones.store');
        Route::put('shipping/zones/{zone}', [ShippingController::class, 'updateZone'])
            ->middleware('permission:shipping.update')->name('shipping.zones.update');
        Route::delete('shipping/zones/{zone}', [ShippingController::class, 'destroyZone'])
            ->middleware('permission:shipping.update')->name('shipping.zones.destroy');
        Route::post('shipping/zones/{zone}/rates', [ShippingController::class, 'storeRate'])
            ->middleware('permission:shipping.update')->name('shipping.rates.store');
        Route::put('shipping/rates/{rate}', [ShippingController::class, 'updateRate'])
            ->middleware('permission:shipping.update')->name('shipping.rates.update');
        Route::delete('shipping/rates/{rate}', [ShippingController::class, 'destroyRate'])
            ->middleware('permission:shipping.update')->name('shipping.rates.destroy');
        Route::get('shipping/shipments/{shipment}/label', [ShippingController::class, 'label'])
            ->middleware('permission:shipping.view')->name('shipping.shipments.label');
        Route::put('shipping/shipments/{shipment}', [ShippingController::class, 'updateShipment'])
            ->middleware('permission:shipping.update')->name('shipping.shipments.update');
        Route::delete('shipping/shipments/{shipment}', [ShippingController::class, 'destroyShipment'])
            ->middleware('permission:shipping.update')->name('shipping.shipments.destroy');
=======
>>>>>>> origin/main

        // SEO
        Route::get('seo', [SettingsController::class, 'seoIndex'])
            ->middleware('permission:seo.view')->name('seo.index');
        Route::post('seo/redirects', [SettingsController::class, 'storeRedirect'])
            ->middleware('permission:seo.update')->name('seo.redirects.store');
        Route::delete('seo/redirects/{redirect}', [SettingsController::class, 'destroyRedirect'])
            ->middleware('permission:seo.update')
            ->name('seo.redirects.destroy');

        // Staff & roles
        Route::resource('staff', StaffController::class)
            ->middlewareFor(['index', 'show'], 'permission:staff.view')
            ->middlewareFor(['create', 'store'], 'permission:staff.create')
            ->middlewareFor(['edit', 'update'], 'permission:staff.update')
            ->middlewareFor(['destroy'], 'permission:staff.delete');
<<<<<<< HEAD
        // Matrix + clone must be registered BEFORE the roles resource so
        // GET roles/matrix is not swallowed by GET roles/{role}.
        Route::get('roles/matrix', [RoleController::class, 'matrix'])
            ->middleware('permission:roles.view')->name('roles.matrix');
        Route::post('roles/matrix', [RoleController::class, 'updateMatrix'])
            ->middleware('permission:roles.update')->name('roles.matrix.update');
        Route::post('roles/{role}/clone', [RoleController::class, 'cloneRole'])
            ->middleware('permission:roles.create')->name('roles.clone');
=======
>>>>>>> origin/main
        Route::resource('roles', RoleController::class)
            ->middlewareFor(['index', 'show'], 'permission:roles.view')
            ->middlewareFor(['create', 'store'], 'permission:roles.create')
            ->middlewareFor(['edit', 'update'], 'permission:roles.update')
            ->middlewareFor(['destroy'], 'permission:roles.delete');
        Route::resource('users', UserController::class)->except(['create', 'store'])
            ->middlewareFor(['index', 'show'], 'permission:staff.view|customers.view')
            ->middlewareFor(['edit', 'update'], 'permission:staff.update|customers.update')
            ->middlewareFor(['destroy'], 'permission:staff.delete');

        // Exchange requests
<<<<<<< HEAD
        Route::get('exchanges/export', [ExchangeRequestController::class, 'export'])
            ->middleware('permission:exchanges.view|exchanges.export')
            ->name('exchanges.export');
        Route::post('exchanges', [ExchangeRequestController::class, 'store'])
            ->middleware('permission:exchanges.create')
            ->name('exchanges.store');
        Route::post('exchanges/{exchange}/receive', [ExchangeRequestController::class, 'receive'])
            ->middleware('permission:exchanges.update')
            ->name('exchanges.receive');
        Route::post('exchanges/{exchange}/complete', [ExchangeRequestController::class, 'complete'])
            ->middleware('permission:exchanges.update')
            ->name('exchanges.complete');
=======
>>>>>>> origin/main
        Route::resource('exchanges', ExchangeRequestController::class)->only(['index', 'show', 'update'])
            ->middlewareFor(['index', 'show'], 'permission:exchanges.view')
            ->middlewareFor(['update'], 'permission:exchanges.update');

        // Reports
        Route::get('reports/sales', [ReportController::class, 'sales'])
            ->middleware('permission:reports.view')->name('reports.sales');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])
            ->middleware('permission:reports.view')->name('reports.inventory');
        Route::get('reports/customers', [ReportController::class, 'customers'])
            ->middleware('permission:reports.view')->name('reports.customers');
        Route::get('reports/staff-activity', [ReportController::class, 'staffActivity'])
            ->middleware('permission:reports.view')
            ->name('reports.staff_activity');
        // Phase 6 — new reports
        Route::get('reports/products', [ReportController::class, 'products'])
            ->middleware('permission:reports.view')->name('reports.products');
        Route::get('reports/coupons', [ReportController::class, 'coupons'])
            ->middleware('permission:reports.view')->name('reports.coupons');
        Route::get('reports/tax', [ReportController::class, 'tax'])
            ->middleware('permission:reports.view')->name('reports.tax');
        Route::get('reports/shipping', [ReportController::class, 'shipping'])
            ->middleware('permission:reports.view')->name('reports.shipping');
        Route::get('reports/payments', [ReportController::class, 'payments'])
            ->middleware('permission:reports.view')->name('reports.payments');
        Route::get('reports/refunds', [ReportController::class, 'refunds'])
            ->middleware('permission:reports.view')->name('reports.refunds');
        Route::get('reports/exchanges', [ReportController::class, 'exchanges'])
            ->middleware('permission:reports.view')->name('reports.exchanges');
        // Email the on-screen report to the current staff member.
        Route::post('reports/{type}/email', [ReportController::class, 'emailReport'])
            ->whereIn('type', ReportController::TYPES)
            ->middleware('permission:reports.export')
            ->name('reports.email');
        Route::get('reports/export/{type}', [ReportController::class, 'export'])
<<<<<<< HEAD
            ->whereIn('type', ReportController::TYPES)
=======
>>>>>>> origin/main
            ->middleware('permission:reports.export')
            ->name('reports.export');

        // Database
        Route::get('database/info', [\App\Http\Controllers\Admin\DatabaseController::class, 'info'])
            ->middleware('permission:settings.view')->name('database.info');

<<<<<<< HEAD
        // System: health, backups, migrations, queue, maintenance
        Route::controller(\App\Http\Controllers\Admin\SystemController::class)
            ->group(function () {
                Route::get('system', 'index')
                    ->middleware('permission:settings.view')->name('system.index');

                Route::post('system/backup', 'createBackup')
                    ->middleware('permission:settings.update')->name('system.backup.create');
                Route::get('system/backup/{file}/download', 'downloadBackup')
                    ->middleware('permission:settings.view')->name('system.backup.download');
                Route::delete('system/backup/{file}', 'destroyBackup')
                    ->middleware('permission:settings.update')->name('system.backup.destroy');

                Route::post('system/maintenance', 'toggleMaintenance')
                    ->middleware('permission:settings.update')->name('system.maintenance');

                Route::post('system/failed-jobs/{id}/retry', 'retryFailedJob')
                    ->middleware('permission:settings.update')->name('system.failed-jobs.retry');
                Route::post('system/failed-jobs/{id}/delete', 'deleteFailedJob')
                    ->middleware('permission:settings.update')->name('system.failed-jobs.delete');
                Route::post('system/failed-jobs/flush', 'flushFailedJobs')
                    ->middleware('permission:settings.update')->name('system.failed-jobs.flush');
            });

=======
>>>>>>> origin/main
        // Settings
        Route::get('settings', [SettingsController::class, 'index'])
            ->middleware('permission:settings.view')->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])
            ->middleware('permission:settings.update')->name('settings.update');
        Route::get('settings/payments', [SettingsController::class, 'paymentSettings'])
            ->middleware('permission:settings.view')
            ->name('settings.payments');
        Route::post('settings/payments', [SettingsController::class, 'updatePaymentSettings'])
            ->middleware('permission:settings.update')
            ->name('settings.payments.update');
        Route::post('settings/email-test', [SettingsController::class, 'emailTest'])
            ->middleware('permission:settings.update')
            ->name('settings.email-test');

        // Audit
<<<<<<< HEAD
        // Export routes must be registered BEFORE the audit-logs resource so
        // GET audit-logs/export is not swallowed by GET audit-logs/{audit_log}.
        Route::get('audit-logs/export', [AuditLogController::class, 'exportAudit'])
            ->middleware('permission:audit.view')->name('audit-logs.export');
=======
>>>>>>> origin/main
        Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show'])
            ->middlewareFor(['index', 'show'], 'permission:audit.view');
        Route::get('security-events', [AuditLogController::class, 'securityEvents'])
            ->middleware('permission:audit.view')
            ->name('security-events.index');
        Route::get('security-events/export', [AuditLogController::class, 'exportSecurityEvents'])
            ->middleware('permission:audit.view')->name('security-events.export');
        Route::get('active-sessions', [AuditLogController::class, 'activeSessions'])
            ->middleware('permission:audit.view')
            ->name('active-sessions.index');
        Route::post('active-sessions/{session}/revoke', [AuditLogController::class, 'revokeSession'])
            ->middleware('permission:audit.view')->name('sessions.revoke');
    });

// --------------------------------------------------------------------------
// Sitemap & robots
// --------------------------------------------------------------------------
Route::get('/sitemap.xml', [\App\Http\Controllers\Storefront\SitemapController::class, 'index'])
    ->name('sitemap.index');
Route::get('/robots.txt', [\App\Http\Controllers\Storefront\RobotsController::class, 'index'])
    ->name('robots.index');
