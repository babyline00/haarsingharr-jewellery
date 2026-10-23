<?php

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;

/*
|--------------------------------------------------------------------------
| Storefront smoke tests
|--------------------------------------------------------------------------
|
| Guard the enum-vs-string comparisons that decide whether a detail page is
| viewable; these silently 404 every product when status is compared wrongly.
|
*/

test('active product detail page renders while draft products 404', function () {
    $active = Product::factory()->create(['name' => 'Active Ring', 'status' => 'active']);
    $draft  = Product::factory()->create(['name' => 'Draft Ring', 'status' => 'draft']);

    $this->get(route('products.show', $active->slug))->assertOk();
    $this->get(route('products.show', $draft->slug))->assertNotFound();
});

test('active category and collection pages render', function () {
    $category = Category::create(['name' => 'Rings', 'status' => 'active']);
    $collection = Collection::create([
        'name' => 'Bridal', 'type' => 'manual',
        'match_condition' => 'all', 'status' => 'active',
    ]);

    $this->get(route('categories.show', $category->slug))->assertOk();
    $this->get(route('collections.show', $collection->slug))->assertOk();
});
