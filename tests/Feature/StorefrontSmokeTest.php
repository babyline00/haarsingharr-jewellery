<?php

use App\Models\CmsPage;

/*
|--------------------------------------------------------------------------
| Storefront smoke tests
|--------------------------------------------------------------------------
|
| Every public page must render without a server error — the pages that
| tests previously missed (home, shop filters, static pages, account area)
| are exactly the ones a bad Blade edit or route rename would break last.
|
*/

test('every public storefront page renders without a server error', function () {
    $guestUrls = [
        '/' => [200],
        route('shop.index') => [200],
        route('shop.bridal') => [200],
        route('shop.men') => [200],
        route('shop.best_sellers') => [200],
        route('shop.new_arrivals') => [200],
        route('shop.sale') => [200],
        route('shop.budget', 'under-15k') => [200],
        route('shop.budget') => [200],
        // Price filters exercise the grouped min/max query constraints.
        route('shop.index', ['min' => 1000, 'max' => 50000, 'sort' => 'price_asc']) => [200],
        route('search.index', ['q' => 'ring']) => [200],
        route('faq') => [200],
        route('contact') => [200],
        route('about') => [200],
        route('locations') => [200],
        route('blog.index') => [200],
        route('cart.index') => [200],
        route('order.tracking.form') => [200],
        route('login') => [200],
        route('register') => [200],
        route('password.request') => [200],
        route('verification.notice') => [200, 302],
        // Empty cart may bounce to the cart page, but never to a 500.
        route('checkout.show') => [200, 302],
        // Unknown URLs must render the branded 404, not a framework error page.
        '/this-page-does-not-exist' => [404],
    ];

    foreach ($guestUrls as $url => $acceptable) {
        $response = $this->get($url);
        expect(in_array($response->status(), $acceptable, true))->toBeTrue(
            "GET {$url} returned HTTP {$response->status()}, expected " . implode(' or ', $acceptable)
        );
    }
});

test('legal convenience routes resolve to published CMS pages', function () {
    foreach (['shipping-policy', 'exchange-policy', 'privacy-policy', 'terms', 'jewellery-care', 'size-guides'] as $slug) {
        CmsPage::create([
            'title' => str($slug)->replace('-', ' ')->title(),
            'slug' => $slug,
            'template' => 'default',
            'status' => 'published',
        ]);

        $response = $this->get("/{$slug}")->assertRedirect();
        $this->followRedirects($this->get("/{$slug}"))->assertOk();
    }
});

test('customer account pages render without a server error', function () {
    $this->actingAs(createCustomer());

    $urls = [
        route('account.dashboard'),
        route('account.orders.index'),
        route('account.wishlist.index'),
        route('account.addresses.index'),
        route('account.profile.edit'),
        route('account.returns.index'),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});
