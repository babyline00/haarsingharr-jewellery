<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $products = Product::with(['category', 'images', 'variants'])
            ->withCount('videos')
            ->when($request->input('q'), fn ($q, $term) => $q->search($term))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('category'), function ($q, $slug) {
                $cat = Category::where('slug', $slug)->first();
                if ($cat) $q->where('category_id', $cat->id);
            })
            ->latest()
            ->paginate(20);

        $categories = Category::active()->root()->ordered()->get();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['q', 'status', 'category']),
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $categories = Category::active()->root()->ordered()->get();
        return view('admin.products.create', [
            'categories' => $categories,
            'statuses'   => ProductStatus::cases(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $inventory = $this->pullInventoryPayload($validated);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $product = Product::create($validated);
        $this->audit->record('products.create', $product);

        $this->syncProductInventory($product, $inventory, true);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product '{$product->name}' created.");
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'variants', 'videos', 'category', 'tags']);
        $categories = Category::active()->root()->ordered()->get();
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $inventory = $this->pullInventoryPayload($validated);
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

    public function storeVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku'              => 'nullable|unique:product_variants,sku',
            'title'            => 'nullable|string|max:120',
            'attributes'       => 'nullable|array',
            'regular_price'    => 'required|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0|lt:regular_price',
            'cost_price'       => 'nullable|numeric|min:0',
            'stock_quantity'   => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight_grams'     => 'nullable|numeric|min:0',
            'is_default'       => 'sometimes|boolean',
            'allow_backorder'  => 'sometimes|boolean',
        ]);

        $variant = $product->variants()->create($validated);
        $this->audit->record('products.variants.create', $variant);

        // Set the initial stock via the inventory service so the ledger stays auditable.
        if ($variant->stock_quantity > 0) {
            $this->inventoryService->adjust($variant, $variant->stock_quantity,
                'Initial stock for new variant',
                auth()->user(),
            );
        }

        if (! empty($validated['is_default'])) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        return back()->with('success', "Variant '{$variant->title}' added.");
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        $validated = $request->validate([
            'sku'              => 'nullable|unique:product_variants,sku,'.$variant->id,
            'title'            => 'nullable|string|max:120',
            'attributes'       => 'nullable|array',
            'regular_price'    => 'required|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0|lt:regular_price',
            'cost_price'       => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight_grams'     => 'nullable|numeric|min:0',
            'is_default'       => 'sometimes|boolean',
            'allow_backorder'  => 'sometimes|boolean',
        ]);

        $variant->update($validated);
        $this->audit->record('products.variants.update', $variant);

        if (! empty($validated['is_default'])) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        return back()->with('success', "Variant '{$variant->title}' updated.");
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
    {
        $this->audit->record('products.variants.delete', $variant);
        $variant->delete();
        return back()->with('success', "Variant removed.");
    }
}
