<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| Admin catalogue regression tests (Phase 2)
|--------------------------------------------------------------------------
|
| Covers bulk actions, duplication, CSV import, the variant matrix,
| category tree operations, collection curation and inventory bulk edits.
|
*/

beforeEach(function () {
    $this->actingAs(createAdmin());
});

test('product bulk actions update and soft delete selections', function () {
    $first = Product::factory()->create(['status' => ProductStatus::Draft]);
    $second = Product::factory()->create(['status' => ProductStatus::Draft]);
    $untouched = Product::factory()->create(['status' => ProductStatus::Draft]);

    $this->post(route('admin.products.bulk'), [
        'ids' => [$first->id, $second->id],
        'action' => 'activate',
    ])->assertRedirect();

    expect($first->fresh()->status)->toBe(ProductStatus::Active)
        ->and($second->fresh()->status)->toBe(ProductStatus::Active)
        ->and($untouched->fresh()->status)->toBe(ProductStatus::Draft);

    $this->post(route('admin.products.bulk'), [
        'ids' => [$first->id],
        'action' => 'delete',
    ])->assertRedirect();

    $this->assertSoftDeleted('products', ['id' => $first->id]);
    expect(Product::find($second->id))->not->toBeNull();
});

test('duplicating a product copies variants with zero opening stock', function () {
    $product = Product::factory()->withVariants()->create([
        'name' => 'Heritage Ring',
        'sku' => 'HERITAGE-1',
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'sku' => 'HERITAGE-1-16',
        'stock_quantity' => 12,
    ]);

    $this->post(route('admin.products.duplicate', $product))
        ->assertRedirect();

    $copy = Product::where('name', 'Heritage Ring (Copy)')->firstOrFail();

    expect($copy->id)->not->toBe($product->id)
        ->and($copy->status)->toBe(ProductStatus::Draft)
        ->and($copy->sku)->toBe('HERITAGE-1-COPY')
        ->and($copy->variants)->toHaveCount(1)
        ->and($copy->variants->first()->sku)->toBe('HERITAGE-1-16-COPY')
        ->and($copy->variants->first()->stock_quantity)->toBe(0)
        ->and($variant->fresh()->stock_quantity)->toBe(12);
});

test('a product can be updated while keeping its own SKU and toggle flags', function () {
    $product = Product::factory()->create([
        'sku' => 'KEEP-SKU-1',
        'status' => ProductStatus::Draft,
        'allow_reviews' => true,
        'is_featured' => true,
    ]);

    $this->put(route('admin.products.update', $product), [
        'name' => 'Updated product name',
        'sku' => 'KEEP-SKU-1',
        'regular_price' => $product->regular_price,
        'status' => 'active',
        'allow_reviews' => '1',
        // Deliberately omit is_featured: an unchecked checkbox must clear it.
    ])->assertRedirect();

    $product->refresh();

    expect($product->name)->toBe('Updated product name')
        ->and($product->status)->toBe(ProductStatus::Active)
        ->and($product->allow_reviews)->toBeTrue()
        ->and($product->is_featured)->toBeFalse();
});

test('CSV preview validates rows and import creates a default variant with ledger stock', function () {
    $csv = "name,regular_price,sale_price,sku,category,status,stock\n"
        ."Imported Ring,1200,1000,IMP-1,,active,7\n"
        ."Bad Price,100,200,IMP-2,,draft,0\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $preview = $this->post(route('admin.products.import.preview'), ['file' => $file]);
    $preview->assertOk()
        ->assertSee('Imported Ring')
        ->assertSee('Sale price must be lower than the regular price.');

    $token = session('product_import.token');
    expect($token)->not->toBeNull();

    $this->post(route('admin.products.import.store'), ['token' => $token])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::where('sku', 'IMP-1')->firstOrFail();
    $variant = $product->variants()->firstOrFail();

    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->variants)->toHaveCount(1)
        ->and($variant->is_default)->toBeTrue()
        ->and($variant->stock_quantity)->toBe(7)
        ->and($product->availableStock())->toBe(7);

    expect(InventoryMovement::where('product_variant_id', $variant->id)->sum('quantity_change'))
        ->toBe(7);
});

test('variant matrix generates unique combinations and skips existing ones', function () {
    $product = Product::factory()->create([
        'sku' => 'MATRIX-1',
        'regular_price' => 1000,
    ]);

    $payload = [
        'attributes' => [
            ['name' => 'Size', 'values' => '14, 16'],
            ['name' => 'Colour', 'values' => 'Gold, Silver'],
        ],
        'regular_price' => 1000,
        'stock_quantity' => 5,
        'low_stock_threshold' => 2,
        'sku_prefix' => 'MATRIX-1',
    ];

    $this->post(route('admin.products.variants.generate', $product), $payload)
        ->assertRedirect();

    $product->refresh();

    expect($product->variants)->toHaveCount(4)
        ->and($product->has_variants)->toBeTrue()
        ->and($product->variant_attributes)->toBe(['Size', 'Colour'])
        ->and($product->variants->every(fn ($variant) => $variant->stock_quantity === 5))->toBeTrue();

    $skus = $product->variants->pluck('sku')->all();
    expect(array_unique($skus))->toHaveCount(4);

    $this->post(route('admin.products.variants.generate', $product), $payload)
        ->assertRedirect();

    expect($product->fresh()->variants)->toHaveCount(4);
});

test('category reorder, safe delete and bulk status changes work', function () {
    $rings = Category::create(['name' => 'Rings', 'status' => 'active', 'sort_order' => 0]);
    $necklaces = Category::create(['name' => 'Necklaces', 'status' => 'active', 'sort_order' => 1]);
    $bridal = Category::create(['name' => 'Bridal Rings', 'parent_id' => $rings->id, 'status' => 'active']);

    $this->post(route('admin.categories.move', [$necklaces, 'up']))->assertRedirect();
    expect($necklaces->fresh()->sort_order)->toBe(0)
        ->and($rings->fresh()->sort_order)->toBe(1);

    // A category with a child cannot be deleted.
    $this->delete(route('admin.categories.destroy', $rings))->assertRedirect();
    expect(Category::find($rings->id))->not->toBeNull();

    // A category with a product cannot be deleted either.
    $product = Product::factory()->create(['category_id' => $bridal->id]);
    $this->delete(route('admin.categories.destroy', $bridal))->assertRedirect();
    expect(Category::find($bridal->id))->not->toBeNull();

    // Reassign the product, then deletion succeeds.
    $product->update(['category_id' => null]);
    $this->delete(route('admin.categories.destroy', $bridal))->assertRedirect();
    $this->assertSoftDeleted('categories', ['id' => $bridal->id]);

    $this->post(route('admin.categories.bulk'), [
        'ids' => [$rings->id, $necklaces->id],
        'action' => 'inactive',
    ])->assertRedirect();

    expect($rings->fresh()->status)->toBe('inactive')
        ->and($necklaces->fresh()->status)->toBe('inactive');

    // Bulk delete skips categories that still have children.
    Category::create(['name' => 'Everyday Rings', 'parent_id' => $rings->id, 'status' => 'active']);

    $this->post(route('admin.categories.bulk'), [
        'ids' => [$rings->id],
        'action' => 'delete',
    ])->assertRedirect();

    expect(Category::find($rings->id))->not->toBeNull();
});

test('collection products can be attached reordered detached and given SEO metadata', function () {
    $collection = Collection::create([
        'name' => 'Bridal Edit',
        'type' => 'manual',
        'match_condition' => 'all',
        'status' => 'active',
    ]);

    $first = Product::factory()->create();
    $second = Product::factory()->create();

    $this->post(route('admin.collections.products.store', $collection), ['product_id' => $first->id])
        ->assertRedirect();
    $this->post(route('admin.collections.products.store', $collection), ['product_id' => $second->id])
        ->assertRedirect();

    expect($collection->fresh()->products->pluck('id')->all())->toBe([$first->id, $second->id]);

    $this->post(route('admin.collections.products.move', [$collection, $second, 'up']))
        ->assertRedirect();

    expect($collection->fresh()->products->pluck('id')->all())->toBe([$second->id, $first->id]);

    $this->delete(route('admin.collections.products.destroy', [$collection, $first]))
        ->assertRedirect();

    expect($collection->fresh()->products->pluck('id')->all())->toBe([$second->id]);

    $this->put(route('admin.collections.update', $collection), [
        'name' => 'Bridal Edit',
        'type' => 'manual',
        'match_condition' => 'all',
        'status' => 'active',
        'seo_title' => 'Bridal jewellery | Haarsingharr',
        'seo_description' => 'Hand-finished bridal pieces for the modern bride.',
    ])->assertRedirect();

    $collection->refresh();

    expect($collection->seo_title)->toBe('Bridal jewellery | Haarsingharr')
        ->and($collection->show_on_homepage)->toBeFalse();
});

test('inventory bulk set and adjust write ledger movements', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'stock_quantity' => 10,
        'low_stock_threshold' => 2,
    ]);

    $this->post(route('admin.inventory.bulk'), [
        'ids' => [$variant->id],
        'mode' => 'set',
        'value' => 4,
        'reason' => 'physical_count',
        'notes' => 'Cycle count',
    ])->assertRedirect();

    expect($variant->fresh()->stock_quantity)->toBe(4);

    $this->post(route('admin.inventory.bulk'), [
        'ids' => [$variant->id],
        'mode' => 'adjust',
        'value' => 6,
        'reason' => 'received_shipment',
    ])->assertRedirect();

    expect($variant->fresh()->stock_quantity)->toBe(10);

    $this->post(route('admin.inventory.bulk'), [
        'ids' => [$variant->id],
        'mode' => 'adjust',
        'value' => -12,
        'reason' => 'damaged',
    ])->assertRedirect();

    // Adjustments never drop below zero.
    expect($variant->fresh()->stock_quantity)->toBe(0);

    expect(InventoryMovement::where('product_variant_id', $variant->id)->count())->toBe(3);
});

test('inventory alerts can be acknowledged', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 0]);

    $alert = InventoryAlert::create([
        'product_variant_id' => $variant->id,
        'type' => 'out_of_stock',
        'threshold' => 5,
        'current_stock' => 0,
        'is_resolved' => false,
    ]);

    $this->post(route('admin.inventory.alerts.resolve', $alert))->assertRedirect();

    expect($alert->fresh()->is_resolved)->toBeTrue()
        ->and($alert->fresh()->resolved_at)->not->toBeNull();
});

test('product relations can be linked and unlinked', function () {
    $product = Product::factory()->create();
    $related = Product::factory()->create();

    $this->post(route('admin.products.relations.store', $product), [
        'related_product_id' => $related->id,
        'relation_type' => 'upsell',
    ])->assertRedirect();

    expect($product->upsells()->pluck('products.id')->all())->toContain($related->id);

    $this->delete(
        route('admin.products.relations.destroy', [$product, $related->id]).'?relation_type=upsell'
    )->assertRedirect();

    expect($product->fresh()->upsells()->pluck('products.id')->all())->not->toContain($related->id);
});

test('product and inventory exports stream CSV files', function () {
    Product::factory()->create();

    $this->get(route('admin.products.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->get(route('admin.inventory.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('catalogue admin screens render with populated records', function () {
    $category = Category::create(['name' => 'Rings', 'status' => 'active']);
    $child = Category::create(['name' => 'Solitaires', 'parent_id' => $category->id, 'status' => 'active']);
    $product = Product::factory()->withVariants()->create(['category_id' => $child->id]);
    ProductVariant::factory()->for($product)->create([
        'stock_quantity' => 3,
        'low_stock_threshold' => 5,
    ]);

    $collection = Collection::create([
        'name' => 'Gifts under 50k',
        'type' => 'rule_based',
        'rules' => ['min_price' => 1000, 'max_price' => 50000, 'is_featured' => true],
        'match_condition' => 'any',
        'status' => 'active',
    ]);

    $this->get(route('admin.products.index'))->assertOk()
        ->assertSee($product->name);
    $this->get(route('admin.products.edit', $product))->assertOk()
        ->assertSee('Generate matrix')
        ->assertSee('Activity');
    $this->get(route('admin.products.import'))->assertOk()
        ->assertSee('Upload CSV');
    $this->get(route('admin.categories.index'))->assertOk()
        ->assertSee($child->name)
        ->assertSee('Parent');
    $this->get(route('admin.collections.edit', $collection))->assertOk()
        ->assertSee('Automatic rules')
        ->assertSee('Gifts under 50k');
    $this->get(route('admin.inventory.index'))->assertOk()
        ->assertSee($product->name);
    $this->get(route('admin.inventory.import'))->assertOk()
        ->assertSee('CSV columns');
    $this->get(route('admin.inventory.movements'))->assertOk();
});
