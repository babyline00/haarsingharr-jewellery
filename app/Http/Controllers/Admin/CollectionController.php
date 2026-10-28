<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $collections = Collection::withCount('products')
            ->search($request->input('q'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Collection::count(),
            'active' => Collection::where('status', 'active')->count(),
            'homepage' => Collection::where('show_on_homepage', true)->count(),
            'manual' => Collection::where('type', 'manual')->count(),
        ];

        return view('admin.collections.index', [
            'collections' => $collections,
            'filters' => $request->only('q'),
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        return view('admin.collections.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCollection($request);
        $validated['show_on_homepage'] = $request->boolean('show_on_homepage');

        $collection = Collection::create($validated);
        $this->audit->record('collections.create', $collection);

        return redirect()->route('admin.collections.index')->with('success', 'Collection created.');
    }

    public function show(Collection $collection)
    {
        return redirect()->route('admin.collections.edit', $collection);
    }

    public function edit(Collection $collection): View
    {
        $collection->load(['products']);

        $productOptions = Product::orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'sku']);

        $categories = Category::ordered()->get(['id', 'name']);

        $ruleMatchCount = $collection->type === 'rule_based'
            ? $this->matchingProductsQuery($collection)->count()
            : $collection->products->count();

        return view('admin.collections.edit', [
            'collection' => $collection,
            'productOptions' => $productOptions,
            'categories' => $categories,
            'ruleMatchCount' => $ruleMatchCount,
        ]);
    }

    public function update(Request $request, Collection $collection)
    {
        $validated = $this->validateCollection($request);
        $validated['show_on_homepage'] = $request->boolean('show_on_homepage');

        $collection->update($validated);
        $this->audit->record('collections.update', $collection);

        return back()->with('success', 'Collection updated.');
    }

    public function destroy(Collection $collection)
    {
        $this->audit->record('collections.delete', $collection);
        $collection->delete();

        return redirect()->route('admin.collections.index')->with('success', 'Collection archived.');
    }

    public function attachProduct(Request $request, Collection $collection): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $max = (int) DB::table('collection_product')
            ->where('collection_id', $collection->id)
            ->max('sort_order');

        $collection->products()->syncWithoutDetaching([
            $data['product_id'] => ['sort_order' => $max + 1],
        ]);

        $this->audit->record('collections.products.attach', $collection, null, $data);

        return back()->with('success', 'Product added to the collection.');
    }

    public function detachProduct(Collection $collection, Product $product): RedirectResponse
    {
        $collection->products()->detach($product->id);
        $this->audit->record('collections.products.detach', $collection, null, ['product_id' => $product->id]);

        return back()->with('success', 'Product removed from the collection.');
    }

    public function moveProduct(Collection $collection, Product $product, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $attached = $collection->products()->get();
        $index = $attached->search(fn (Product $item) => $item->id === $product->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $attached->count()) {
            return back()->with('success', 'Product is already at the edge of the collection.');
        }

        $ordered = $attached->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(function () use ($collection, $ordered) {
            foreach ($ordered as $position => $item) {
                $collection->products()->updateExistingPivot($item->id, [
                    'sort_order' => $position,
                ]);
            }
        });

        $this->audit->record('collections.products.reorder', $collection, null, [
            'product_id' => $product->id,
            'direction' => $direction,
        ]);

        return back()->with('success', 'Collection order updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCollection(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string|max:300',
            'type' => 'required|in:manual,rule_based',
            'rules' => 'nullable|array',
            'rules.min_price' => 'nullable|numeric|min:0',
            'rules.max_price' => 'nullable|numeric|min:0',
            'rules.category_id' => 'nullable|integer|exists:categories,id',
            'rules.is_featured' => 'sometimes|boolean',
            'match_condition' => 'required|in:all,any',
            'status' => 'required|in:active,inactive',
            'show_on_homepage' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'sometimes|integer|min:0',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
            'seo_image' => 'nullable|string|max:300',
        ]);
    }

    /**
     * Build the product query for a rule-based collection. Used for the admin
     * preview count; the storefront applies the same rules.
     */
    private function matchingProductsQuery(Collection $collection): Builder
    {
        $rules = is_array($collection->rules) ? $collection->rules : [];
        $query = Product::query()->where('status', ProductStatus::Active->value);
        $conditions = [];

        if (isset($rules['min_price']) && is_numeric($rules['min_price'])) {
            $conditions[] = fn (Builder $q) => $q->whereRaw(
                'COALESCE(sale_price, regular_price) >= ?',
                [(float) $rules['min_price']],
            );
        }

        if (isset($rules['max_price']) && is_numeric($rules['max_price'])) {
            $conditions[] = fn (Builder $q) => $q->whereRaw(
                'COALESCE(sale_price, regular_price) <= ?',
                [(float) $rules['max_price']],
            );
        }

        if (! empty($rules['category_id']) && is_numeric($rules['category_id'])) {
            $conditions[] = fn (Builder $q) => $q->where('category_id', (int) $rules['category_id']);
        }

        if (! empty($rules['is_featured'])) {
            $conditions[] = fn (Builder $q) => $q->where('is_featured', true);
        }

        if ($conditions === []) {
            return $query->whereRaw('0 = 1');
        }

        if (($collection->match_condition ?? 'all') === 'any') {
            $query->where(function (Builder $sub) use ($conditions) {
                foreach ($conditions as $index => $condition) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $sub->{$method}(fn (Builder $inner) => $condition($inner));
                }
            });
        } else {
            foreach ($conditions as $condition) {
                $query->where(fn (Builder $inner) => $condition($inner));
            }
        }

        return $query;
    }
}
