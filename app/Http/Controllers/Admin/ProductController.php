<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use App\Services\InventoryService;
<<<<<<< HEAD
use App\Services\ProductImportService;
use App\Support\ImageCompressor;
=======
use Illuminate\Support\Facades\Storage;
>>>>>>> origin/main
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'category', 'stock']);

        $products = Product::with(['category', 'images', 'variants'])
<<<<<<< HEAD
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category'] ?? null, function ($q, $slug) {
=======
            ->withCount('videos')
            ->when($request->input('q'), fn ($q, $term) => $q->search($term))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('category'), function ($q, $slug) {
>>>>>>> origin/main
                $cat = Category::where('slug', $slug)->first();
                if ($cat) {
                    $q->where('category_id', $cat->id);
                }
            })
            ->when(($filters['stock'] ?? null) === 'low', function ($q) {
                $q->whereHas('variants', function ($variants) {
                    $variants->whereRaw('(stock_quantity - reserved_stock - safety_stock) > 0')
                        ->whereRaw('(stock_quantity - reserved_stock - safety_stock) <= COALESCE(low_stock_threshold, 5)');
                });
            })
            ->when(($filters['stock'] ?? null) === 'out', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereDoesntHave('variants')
                        ->orWhereHas('variants', fn ($variants) => $variants->whereRaw('(stock_quantity - reserved_stock - safety_stock) <= 0'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = Category::active()->root()->ordered()->get();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('status', ProductStatus::Active->value)->count(),
            'draft' => Product::where('status', ProductStatus::Draft->value)->count(),
            'low_stock' => ProductVariant::whereRaw('(stock_quantity - reserved_stock - safety_stock) > 0')
                ->whereRaw('(stock_quantity - reserved_stock - safety_stock) <= COALESCE(low_stock_threshold, 5)')
                ->count(),
        ];

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'statuses' => ProductStatus::cases(),
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $categories = Category::active()->root()->ordered()->get();

        return view('admin.products.create', [
            'categories' => $categories,
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
<<<<<<< HEAD
        $validated = $this->normaliseFlags($request, $validated);
=======
        $inventory = $this->pullInventoryPayload($validated);

>>>>>>> origin/main
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $images = $request->file('images', []);
        $altText = $validated['image_alt_text'] ?? null;
        unset($validated['images'], $validated['image_alt_text']);

        $storedPaths = [];
        try {
            $product = DB::transaction(function () use ($validated, $images, $altText, &$storedPaths) {
                $product = Product::create($validated);

                // Every product needs at least one variant: stock, cart lines
                // and the storefront price all live on variants.
                $product->variants()->create([
                    'sku' => $this->uniqueVariantSku($product->sku ?: $product->slug ?: $product->name),
                    'title' => 'Default',
                    'regular_price' => $product->regular_price,
                    'sale_price' => $product->sale_price,
                    'cost_price' => $product->cost_price,
                    'stock_quantity' => 0,
                    'low_stock_threshold' => 5,
                    'is_default' => true,
                    'status' => 'active',
                ]);

                foreach ($images as $index => $file) {
                    $path = ImageCompressor::store($file, 'products');
                    $storedPaths[] = $path;
                    $product->images()->create([
                        'path' => $path,
                        'alt_text' => $altText,
                        'is_primary' => $index === 0,
                        'sort_order' => $index + 1,
                        'type' => 'gallery',
                    ]);
                }

                return $product;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }
        $this->audit->record('products.create', $product);

        $this->syncProductInventory($product, $inventory, true);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product '{$product->name}' created".($storedPaths ? ' with '.count($storedPaths).' image(s).' : '. Add product photos below to show it in the storefront.'));
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product): View
    {
<<<<<<< HEAD
        $product->load(['images', 'variants', 'category', 'tags']);

=======
        $product->load(['images', 'variants', 'videos', 'category', 'tags']);
>>>>>>> origin/main
        $categories = Category::active()->root()->ordered()->get();
        $productOptions = Product::where('id', '!=', $product->id)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'sku']);

        $variantIds = $product->variants->pluck('id');

        $activity = AuditLog::query()
            ->where(function ($query) use ($product, $variantIds) {
                $query->where(function ($inner) use ($product) {
                    $inner->where('target_type', Product::class)
                        ->where('target_id', $product->id);
                });

                if ($variantIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($variantIds) {
                        $inner->where('target_type', ProductVariant::class)
                            ->whereIn('target_id', $variantIds);
                    });
                }
            })
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'statuses' => ProductStatus::cases(),
            'productOptions' => $productOptions,
            'relatedProducts' => $product->relatedProducts()->get(),
            'upsellProducts' => $product->upsells()->get(),
            'crossSellProducts' => $product->crossSells()->get(),
            'activity' => $activity,
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
<<<<<<< HEAD
        $validated = $this->normaliseFlags($request, $validated);
=======
        $inventory = $this->pullInventoryPayload($validated);
>>>>>>> origin/main
        $validated['updated_by'] = auth()->id();

        $product->update($validated);
        $this->audit->record('products.update', $product);

        $this->syncProductInventory($product, $inventory, false);

        return back()->with('success', "Product '{$product->name}' updated.");
    }

    /**
     * Remove inventory-only keys from the validated product payload.
     */
    private function pullInventoryPayload(array &$validated): array
    {
        $inventory = [];

        foreach (['stock_quantity', 'low_stock_threshold', 'allow_backorder'] as $key) {
            if (array_key_exists($key, $validated)) {
                $inventory[$key] = $validated[$key];
                unset($validated[$key]);
            }
        }

        return $inventory;
    }

    /**
     * Keep the product's default variant in sync with the inventory card.
     *
     * Products with multiple variants are managed from the Inventory screen:
     * changing one aggregate stock number here would be ambiguous.
     */
    private function syncProductInventory(Product $product, array $inventory, bool $creating): void
    {
        if ($inventory === []) {
            return;
        }

        $stock = array_key_exists('stock_quantity', $inventory) && $inventory['stock_quantity'] !== null
            ? (int) $inventory['stock_quantity']
            : null;
        $threshold = array_key_exists('low_stock_threshold', $inventory) && $inventory['low_stock_threshold'] !== null
            ? (int) $inventory['low_stock_threshold']
            : null;
        $backorder = (bool) ($inventory['allow_backorder'] ?? false);

        $variants = $product->variants()->orderByDesc('is_default')->orderBy('sort_order')->get();

        if ($variants->count() > 1) {
            return;
        }

        $variant = $variants->first();

        if (! $variant) {
            if ($stock === null && $threshold === null && ! $backorder) {
                return;
            }

            $variant = $product->variants()->create([
                'sku'                 => $product->sku,
                'title'               => 'Default',
                'regular_price'       => $product->regular_price,
                'sale_price'          => $product->sale_price,
                'cost_price'          => $product->cost_price,
                'stock_quantity'      => 0,
                'low_stock_threshold' => $threshold ?? 5,
                'allow_backorder'     => $backorder,
                'is_default'          => true,
                'status'              => 'active',
            ]);
        } else {
            $variant->fill([
                'low_stock_threshold' => $threshold ?? $variant->low_stock_threshold,
                'allow_backorder'     => $backorder,
            ])->save();
        }

        if ($stock === null || $stock === $variant->fresh()->stock_quantity) {
            return;
        }

        // Inventory adjustments are a separate permission from product editing.
        if (! auth()->user()?->can('inventory.update')) {
            return;
        }

        $this->inventoryService->adjust(
            $variant->fresh(),
            $stock,
            $creating ? 'Initial stock' : 'manual',
            auth()->user(),
            $creating ? 'Set while creating the product' : 'Updated from the product form',
        );
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->audit->record('products.delete', $product);
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Product '{$product->name}' archived.");
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:products,id',
            'action' => 'required|in:activate,draft,archive,delete',
        ]);

        if ($data['action'] === 'delete' && ! $request->user()->can('products.delete')) {
            abort(403);
        }

        if ($data['action'] !== 'delete' && ! $request->user()->can('products.update')) {
            abort(403);
        }

        $products = Product::whereIn('id', $data['ids'])->get();
        $status = match ($data['action']) {
            'activate' => ProductStatus::Active,
            'draft' => ProductStatus::Draft,
            'archive' => ProductStatus::Archived,
            default => null,
        };

        foreach ($products as $product) {
            if ($data['action'] === 'delete') {
                $this->audit->record('products.bulk_delete', $product);
                $product->delete();

                continue;
            }

            $product->update(['status' => $status, 'updated_by' => $request->user()->id]);
            $this->audit->record('products.bulk_status', $product, null, ['status' => $status?->value]);
        }

        $verb = $data['action'] === 'delete' ? 'archived' : 'updated';

        return back()->with('success', $products->count()." product(s) {$verb}.");
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $product->load(['variants', 'images']);

        $copy = $product->replicate([
            'uuid', 'slug', 'sku', 'created_at', 'updated_at', 'deleted_at',
        ]);

        $copy->name = Str::limit($product->name.' (Copy)', 200, '');
        $copy->slug = null;
        $copy->sku = $product->sku ? $this->uniqueProductSku($product->sku.'-COPY') : null;
        $copy->status = ProductStatus::Draft;
        $copy->has_variants = $product->variants->isNotEmpty();
        $copy->created_by = auth()->id();
        $copy->updated_by = auth()->id();

        if (! auth()->user()->can('products.adjust_cost')) {
            $copy->cost_price = null;
        }

        $copy->save();

        foreach ($product->variants as $variant) {
            $variantCopy = $variant->replicate([
                'uuid', 'sku', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $variantCopy->product_id = $copy->id;
            $variantCopy->sku = $variant->sku ? $this->uniqueVariantSku($variant->sku.'-COPY') : null;
            $variantCopy->stock_quantity = 0;
            $variantCopy->reserved_stock = 0;

            if (! auth()->user()->can('products.adjust_cost')) {
                $variantCopy->cost_price = null;
            }

            $variantCopy->save();
        }

        foreach ($product->images as $image) {
            $newPath = $image->path;

            if ($image->path && Storage::disk('public')->exists($image->path)) {
                $extension = pathinfo($image->path, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = 'products/'.Str::uuid().'.'.$extension;
                Storage::disk('public')->copy($image->path, $newPath);
            }

            $copy->images()->create([
                'path' => $newPath,
                'thumbnail_path' => $image->thumbnail_path,
                'card_path' => $image->card_path,
                'gallery_path' => $image->gallery_path,
                'alt_text' => $image->alt_text,
                'caption' => $image->caption,
                'type' => $image->type,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
                'metadata' => $image->metadata,
            ]);
        }

        $this->audit->record('products.duplicate', $copy, null, ['source_id' => $product->id]);

        return redirect()->route('admin.products.edit', $copy)
            ->with('success', "Product duplicated as '{$copy->name}'. Stock starts at zero.");
    }

    public function storeVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => 'nullable|unique:product_variants,sku',
            'title' => 'nullable|string|max:120',
            'attributes' => 'nullable|array',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:regular_price',
            'cost_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight_grams' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive,discontinued',
            'is_default' => 'sometimes|boolean',
            'allow_backorder' => 'sometimes|boolean',
        ]);

        $stock = (int) $validated['stock_quantity'];
        $validated['stock_quantity'] = 0;
        $validated['attributes'] = $this->cleanAttributes($validated['attributes'] ?? []);

        if (! auth()->user()->can('products.adjust_cost')) {
            unset($validated['cost_price']);
        }

        $variant = $product->variants()->create($validated);

        // Create the variant with zero stock, then apply the opening balance
        // through the ledger so there is an auditable movement.
        if ($stock > 0) {
            $this->inventoryService->adjust(
                $variant,
                $stock,
                'received_shipment',
                auth()->user(),
                'Initial stock for new variant.',
            );
        }

        if (! empty($validated['is_default'])) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        } elseif (! $product->variants()->where('is_default', true)->exists()) {
            $variant->update(['is_default' => true]);
        }

        $this->syncVariantAttributeNames($product, $variant);
        $product->update(['has_variants' => true]);
        $this->audit->record('products.variants.create', $variant);

        return back()->with('success', "Variant '{$variant->title}' added.");
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        abort_unless((int) $variant->product_id === (int) $product->id, 404);

        $validated = $request->validate([
            'sku' => 'nullable|unique:product_variants,sku,'.$variant->id,
            'title' => 'nullable|string|max:120',
            'attributes' => 'nullable|array',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:regular_price',
            'cost_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight_grams' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive,discontinued',
            'is_default' => 'sometimes|boolean',
            'allow_backorder' => 'sometimes|boolean',
        ]);

        $validated['attributes'] = $this->cleanAttributes($validated['attributes'] ?? []);

        if (! auth()->user()->can('products.adjust_cost')) {
            unset($validated['cost_price']);
        }

        $variant->update($validated);
        $this->audit->record('products.variants.update', $variant);

        if (! empty($validated['is_default'])) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $this->syncVariantAttributeNames($product, $variant);
        $product->update(['has_variants' => $product->variants()->exists()]);

        return back()->with('success', "Variant '{$variant->title}' updated.");
    }

<<<<<<< HEAD
    public function setDefaultVariant(Product $product, ProductVariant $variant): RedirectResponse
=======
    public function storeImage(Request $request, Product $product): RedirectResponse
    {
        // Check for PHP upload errors directly from $_FILES
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            if (is_array($files['error'])) {
                foreach ($files['error'] as $errorCode) {
                    if ($errorCode !== UPLOAD_ERR_NO_FILE && $errorCode !== UPLOAD_ERR_OK) {
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Try a smaller file or contact your host.',
                            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                        ];
                        return back()->withErrors(['images' => $errorMessages[$errorCode] ?? 'Upload failed (error code: ' . $errorCode . ').'])->withInput();
                    }
                }
            }
        }

        $validated = $request->validate([
            'images'     => 'required|array|min:1',
            'images.*'   => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'alt_text'   => 'nullable|string|max:200',
            'is_primary' => 'sometimes|boolean',
        ]);

        $maxSort = (int) $product->images()->max('sort_order');
        $created = 0;

        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images', []) as $i => $file) {
            $path = ImageCompressor::store($file, 'products');
            $product->images()->create([
                'path'       => $path,
                'alt_text'   => $validated['alt_text'] ?? null,
                'is_primary' => ! $hasPrimary && $i === 0,
                'sort_order' => ++$maxSort,
                'type'       => 'gallery',
            ]);
            $created++;
            if (! $hasPrimary && $i === 0) {
                $hasPrimary = true;
            }
        }

        $this->audit->record('products.images.create', $product);

        return back()->with('success', $created.' image(s) uploaded.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $this->audit->record('products.images.delete', $image);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    public function setPrimaryImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        $this->audit->record('products.images.primary', $image);

        return back()->with('success', 'Primary image updated.');
    }

    public function storeVideo(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'videos'        => 'required|array|min:1',
            'videos.*'      => 'file|mimes:mp4,webm,mov,m4v|max:102400',
            'thumbnails'    => 'nullable|array',
            'thumbnails.*'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'title'         => 'nullable|string|max:150',
            'type'          => 'required|in:video,reel',
        ]);

        $maxSort = (int) $product->videos()->max('sort_order');
        $thumbnails = $request->file('thumbnails', []);
        $created = 0;

        foreach ($request->file('videos', []) as $index => $file) {
            $thumbnailPath = isset($thumbnails[$index])
                ? ImageCompressor::store($thumbnails[$index], 'products/videos/thumbnails')
                : null;

            $product->videos()->create([
                'path'           => $file->store('products/videos', 'public'),
                'thumbnail_path' => $thumbnailPath,
                'title'          => $validated['title'] ?? null,
                'type'           => $validated['type'],
                'sort_order'     => ++$maxSort,
                'is_primary'     => ! $product->videos()->where('is_primary', true)->exists(),
            ]);

            $created++;
        }

        $this->audit->record('products.videos.create', $product);

        return back()->with('success', $created.' video(s) uploaded.');
    }

    public function destroyVideo(Product $product, ProductVideo $video): RedirectResponse
    {
        abort_unless($video->product_id === $product->id, 404);

        Storage::disk('public')->delete($video->path);
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }

        $this->audit->record('products.videos.delete', $video);
        $video->delete();

        return back()->with('success', 'Video removed.');
    }

    public function setPrimaryVideo(Product $product, ProductVideo $video): RedirectResponse
    {
        abort_unless($video->product_id === $product->id, 404);

        $product->videos()->update(['is_primary' => false]);
        $video->update(['is_primary' => true]);

        $this->audit->record('products.videos.primary', $video);

        return back()->with('success', 'Primary video updated.');
    }
    public function destroyVariant(Product $product, ProductVariant $variant): RedirectResponse
>>>>>>> origin/main
    {
        abort_unless((int) $variant->product_id === (int) $product->id, 404);

        DB::transaction(function () use ($product, $variant) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
            $variant->update(['is_default' => true]);
        });

        $this->audit->record('products.variants.update', $variant, null, ['is_default' => true]);

        return back()->with('success', "“{$variant->displayLabel()}” is now the default option.");
    }

    public function destroyVariant(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless((int) $variant->product_id === (int) $product->id, 404);

        $this->audit->record('products.variants.delete', $variant);
        $variant->delete();

        if (! $product->variants()->exists()) {
            $product->update(['has_variants' => false]);
        }

        return back()->with('success', 'Variant removed.');
    }

    public function generateVariants(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'attributes' => 'required|array|min:1|max:3',
            'attributes.*.name' => 'nullable|string|max:40',
            'attributes.*.values' => 'nullable|string|max:500',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:regular_price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0|max:1000000',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'sku_prefix' => 'nullable|string|max:40',
        ]);

        if (! auth()->user()->can('products.adjust_cost')) {
            unset($validated['cost_price']);
        }

        $attributes = collect($validated['attributes'])
            ->map(function (array $attribute) {
                $values = collect(explode(',', (string) ($attribute['values'] ?? '')))
                    ->map(fn ($value) => trim($value))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return ['name' => trim((string) ($attribute['name'] ?? '')), 'values' => $values];
            })
            ->filter(fn (array $attribute) => $attribute['name'] !== '' && $attribute['values'] !== [])
            ->values();

        if ($attributes->isEmpty()) {
            return back()->with('error', 'Add at least one attribute name and a comma-separated list of values.');
        }

        $combinations = [[]];
        foreach ($attributes as $attribute) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($attribute['values'] as $value) {
                    $next[] = $combination + [$attribute['name'] => $value];
                }
            }
            $combinations = $next;
        }

        if (count($combinations) > 100) {
            return back()->with('error', 'That matrix would create more than 100 variants. Reduce the values and try again.');
        }

        $existing = $product->variants()->get()
            ->map(fn (ProductVariant $variant) => $this->attributeKey($variant->attributes ?? []))
            ->flip();

        $prefix = trim((string) ($validated['sku_prefix'] ?? '')) ?: ($product->sku ?: $product->slug);
        $stock = (int) $validated['stock_quantity'];
        $created = 0;

        DB::transaction(function () use ($product, $combinations, $existing, $attributes, $prefix, $validated, $stock, &$created) {
            foreach ($combinations as $combination) {
                $key = $this->attributeKey($combination);

                if ($existing->has($key)) {
                    continue;
                }

                $title = implode(' / ', array_values($combination));
                $skuBase = $prefix.'-'.Str::slug(implode('-', array_values($combination)), '-');

                $variant = $product->variants()->create([
                    'sku' => $this->uniqueVariantSku($skuBase),
                    'title' => $title,
                    'attributes' => $combination,
                    'regular_price' => $validated['regular_price'],
                    'sale_price' => $validated['sale_price'] ?? null,
                    'cost_price' => $validated['cost_price'] ?? null,
                    'stock_quantity' => 0,
                    'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
                    'is_default' => false,
                    'status' => 'active',
                ]);

                if ($stock > 0) {
                    $this->inventoryService->adjust(
                        $variant,
                        $stock,
                        'received_shipment',
                        auth()->user(),
                        'Initial stock from variant matrix.',
                    );
                }

                $this->audit->record('products.variants.generate', $variant);
                $created++;
            }

            if ($created > 0 && ! $product->variants()->where('is_default', true)->exists()) {
                $product->variants()->first()?->update(['is_default' => true]);
            }

            $product->update([
                'has_variants' => true,
                'variant_attributes' => $attributes->pluck('name')->all(),
            ]);
        });

        if ($created === 0) {
            return back()->with('success', 'All combinations already exist. No variants were created.');
        }

        return back()->with('success', "{$created} variant(s) generated.");
    }

    public function storeRelation(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'related_product_id' => 'required|integer|exists:products,id|different:'.$product->id,
            'relation_type' => 'required|in:related,upsell,cross_sell',
        ]);

        $max = (int) DB::table('product_relations')
            ->where('product_id', $product->id)
            ->where('relation_type', $data['relation_type'])
            ->max('sort_order');

        DB::table('product_relations')->updateOrInsert(
            [
                'product_id' => $product->id,
                'related_product_id' => $data['related_product_id'],
                'relation_type' => $data['relation_type'],
            ],
            [
                'sort_order' => $max + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->audit->record('products.relations.create', $product, null, $data);

        return back()->with('success', 'Related product added.');
    }

    public function destroyRelation(Request $request, Product $product, int $related): RedirectResponse
    {
        $type = $request->validate([
            'relation_type' => 'required|in:related,upsell,cross_sell',
        ])['relation_type'];

        DB::table('product_relations')
            ->where('product_id', $product->id)
            ->where('related_product_id', $related)
            ->where('relation_type', $type)
            ->delete();

        $this->audit->record('products.relations.delete', $product, null, [
            'related_product_id' => $related,
            'relation_type' => $type,
        ]);

        return back()->with('success', 'Related product removed.');
    }

    public function importForm(): View
    {
        return view('admin.products.import');
    }

    public function importPreview(Request $request, ProductImportService $imports): View|RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        try {
            $parsed = $imports->parse($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($parsed['truncated']) {
            return back()->with('error', 'The CSV contains more than '.ProductImportService::MAX_ROWS.' data rows. Split it into smaller files.');
        }

        if ($parsed['total'] === 0) {
            return back()->with('error', 'The CSV contained no data rows.');
        }

        $result = $imports->validate($parsed['rows']);
        $token = (string) Str::uuid();

        session()->put('product_import', [
            'token' => $token,
            'valid' => $result['valid'],
            'errors' => $result['errors'],
            'filename' => $request->file('file')->getClientOriginalName(),
            'total' => $result['total'],
        ]);

        return view('admin.products.import-preview', [
            'token' => $token,
            'filename' => $request->file('file')->getClientOriginalName(),
            'total' => $result['total'],
            'valid' => $result['valid'],
            'errors' => $result['errors'],
        ]);
    }

    public function importStore(Request $request, ProductImportService $imports): RedirectResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $payload = session()->pull('product_import');

        if (! is_array($payload) || ! isset($payload['token']) || ! hash_equals($payload['token'], $data['token'])) {
            return redirect()->route('admin.products.import')
                ->with('error', 'The import preview expired. Please upload the CSV again.');
        }

        $count = $imports->import($payload['valid'] ?? [], $request->user());

        return redirect()->route('admin.products.index')
            ->with('success', "{$count} product(s) imported.");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Product::with(['category', 'variants'])
            ->when($request->input('q'), fn ($q, $term) => $q->search($term))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('category'), function ($q, $slug) {
                $category = Category::where('slug', $slug)->first();
                if ($category) {
                    $q->where('category_id', $category->id);
                }
            })
            ->orderBy('id');

        $includeCost = $request->user()?->can('products.adjust_cost') ?? false;

        return response()->streamDownload(function () use ($query, $includeCost) {
            $handle = fopen('php://output', 'w');

            $header = ['id', 'name', 'sku', 'category', 'status', 'regular_price', 'sale_price'];
            if ($includeCost) {
                $header[] = 'cost_price';
            }
            $header = array_merge($header, ['stock', 'variants', 'created_at']);

            fputcsv($handle, $header);

            $query->chunk(200, function ($products) use ($handle, $includeCost) {
                foreach ($products as $product) {
                    $row = [
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->category?->name,
                        $product->status?->value,
                        $product->regular_price,
                        $product->sale_price,
                    ];

                    if ($includeCost) {
                        $row[] = $product->cost_price;
                    }

                    $row = array_merge($row, [
                        $product->variants->sum(fn (ProductVariant $variant) => $variant->availableStock()),
                        $product->variants->count(),
                        $product->created_at?->toDateTimeString(),
                    ]);

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 'products-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeImage(Request $request, Product $product): RedirectResponse
    {
        // Check for PHP upload errors directly from $_FILES
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            if (is_array($files['error'])) {
                foreach ($files['error'] as $errorCode) {
                    if ($errorCode !== UPLOAD_ERR_NO_FILE && $errorCode !== UPLOAD_ERR_OK) {
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit ('.ini_get('upload_max_filesize').'). Try a smaller file or contact your host.',
                            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                        ];

                        return back()->withErrors(['images' => $errorMessages[$errorCode] ?? 'Upload failed (error code: '.$errorCode.').'])->withInput();
                    }
                }
            }
        }

        $validated = $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'alt_text' => 'nullable|string|max:200',
            'is_primary' => 'sometimes|boolean',
        ]);

        $maxSort = (int) $product->images()->max('sort_order');
        $created = 0;

        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images', []) as $i => $file) {
            $path = ImageCompressor::store($file, 'products');
            $product->images()->create([
                'path' => $path,
                'alt_text' => $validated['alt_text'] ?? null,
                'is_primary' => ! $hasPrimary && $i === 0,
                'sort_order' => ++$maxSort,
                'type' => 'gallery',
            ]);
            $created++;
            if (! $hasPrimary && $i === 0) {
                $hasPrimary = true;
            }
        }

        $this->audit->record('products.images.create', $product);

        return back()->with('success', $created.' image(s) uploaded.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $this->audit->record('products.images.delete', $image);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    public function setPrimaryImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        $this->audit->record('products.images.primary', $image);

        return back()->with('success', 'Primary image updated.');
    }

    /**
     * Normalise checkbox flags on updates so unchecking a box actually clears it.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normaliseFlags(Request $request, array $validated): array
    {
        if (! $request->isMethod('PATCH') && ! $request->isMethod('PUT')) {
            return $validated;
        }

        foreach ([
            'is_featured', 'is_best_seller', 'is_new_arrival', 'is_bridal',
            'is_men', 'allow_reviews', 'allow_backorders',
        ] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        return $validated;
    }

    /**
     * Normalise a key/value attribute map, dropping empty rows.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>|null
     */
    private function cleanAttributes(array $attributes): ?array
    {
        $clean = collect($attributes)
            ->mapWithKeys(fn ($value, $key) => [trim((string) $key) => trim((string) $value)])
            ->filter(fn ($value, $key) => $key !== '' && $value !== '')
            ->all();

        return $clean ?: null;
    }

    /**
     * Keep product.variant_attributes in sync with the attribute names that
     * actually exist on the variants, so the storefront can group options.
     */
    private function syncVariantAttributeNames(Product $product, ProductVariant $variant): void
    {
        if ($variant->attributes === null) {
            return;
        }

        $names = $product->variants()
            ->get()
            ->flatMap(fn (ProductVariant $productVariant) => array_keys($productVariant->attributes ?? []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($names !== []) {
            $product->update(['variant_attributes' => array_values($names)]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attributeKey(array $attributes): string
    {
        ksort($attributes);

        return json_encode($attributes) ?: '';
    }

    private function uniqueProductSku(string $base): string
    {
        $base = Str::limit(Str::upper(Str::slug($base, '-')), 55, '');
        $sku = $base;
        $suffix = 1;

        while (Product::withTrashed()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.(++$suffix);
        }

        return $sku;
    }

    private function uniqueVariantSku(string $base): string
    {
        $base = Str::limit(Str::upper(Str::slug($base, '-')), 55, '');
        $base = $base !== '' ? $base : 'VARIANT';
        $sku = $base;
        $suffix = 1;

        while (ProductVariant::withTrashed()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.(++$suffix);
        }

        return $sku;
    }
}
