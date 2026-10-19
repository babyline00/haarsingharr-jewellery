<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditService;
use App\Services\InventoryService;
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
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $product = Product::create($validated);
        $this->audit->record('products.create', $product);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product '{$product->name}' created.");
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'variants', 'category', 'tags']);
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
        $validated['updated_by'] = auth()->id();

        $product->update($validated);
        $this->audit->record('products.update', $product);

        return back()->with('success', "Product '{$product->name}' updated.");
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

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        $this->audit->record('products.variants.delete', $variant);
        $variant->delete();
        return back()->with('success', "Variant removed.");
    }
}
