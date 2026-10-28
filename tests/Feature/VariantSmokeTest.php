<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

test('variant product page renders multi-attribute picker', function () {
    $product = Product::factory()->create([
        'name'          => 'Smoke Ring',
        'slug'          => 'smoke-ring',
        'sku'           => 'SMOKE-1',
        'regular_price' => 1000,
        'status'        => 'active',
        'has_variants'  => true,
        'variant_attributes' => ['Size', 'Colour'],
    ]);

    $default = ProductVariant::factory()->for($product)->create([
        'sku'          => 'SMOKE-1-14-GOLD',
        'title'        => 'Gold / 14',
        'attributes'   => ['Size' => '14', 'Colour' => 'Gold'],
        'regular_price' => 1000,
        'sale_price'   => 800,
        'stock_quantity' => 10,
        'is_default'   => true,
        'status'       => 'active',
    ]);

    ProductVariant::factory()->for($product)->create([
        'sku'          => 'SMOKE-1-16-GOLD',
        'title'        => 'Gold / 16',
        'attributes'   => ['Size' => '16', 'Colour' => 'Gold'],
        'regular_price' => 1200,
        'stock_quantity' => 2,
        'status'       => 'active',
    ]);

    ProductVariant::factory()->for($product)->create([
        'sku'          => 'SMOKE-1-16-SILVER',
        'title'        => 'Silver / 16',
        'attributes'   => ['Size' => '16', 'Colour' => 'Silver'],
        'regular_price' => 1200,
        'stock_quantity' => 0,
        'status'       => 'active',
    ]);

    $html = $this->get(route('products.show', $product->slug))->assertOk()->content();

    // Option groups rendered per attribute
    expect($html)->toContain('Size')
        ->and($html)->toContain('Colour')
        ->toContain('Gold')
        ->toContain('Silver');

    // Default variant pre-selected, price + payload JSON present
    expect($html)->toContain('value="'.$default->id.'"')
        ->and($html)->toContain('variantPicker')
        ->toContain('AggregateOffer')
        ->toContain('800');

    // Hidden input bound to Alpine selection
    expect(Str::contains($html, ':value="selectedVariantId"'))->toBeTrue();
});

test('single default variant product uses simple purchase flow', function () {
    $product = Product::factory()->create([
        'name'          => 'Simple Ring',
        'slug'          => 'simple-ring',
        'regular_price' => 500,
        'status'        => 'active',
    ]);

    // Simulate the auto-created default variant from admin store()
    ProductVariant::factory()->for($product)->create([
        'sku'            => 'SIMPLE-1-DEF',
        'title'          => 'Default',
        'regular_price'  => 500,
        'stock_quantity' => 5,
        'is_default'     => true,
        'status'         => 'active',
    ]);

    $html = $this->get(route('products.show', $product->slug))->assertOk()->content();

    // No option-group buttons rendered, but purchase form with the default variant
    expect($html)->not->toContain('Product options')
        ->and($html)->toContain('name="product_variant_id"')
        ->toContain('Rs. 500');
});
