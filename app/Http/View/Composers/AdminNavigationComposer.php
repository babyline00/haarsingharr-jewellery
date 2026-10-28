<?php

namespace App\Http\View\Composers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminNavigationComposer
{
    /**
     * Counts for the sidebar badges. Cached briefly because the shell renders
     * on every admin request and these are global (not per-user) numbers.
     */
    public function compose(View $view): void
    {
        if (! auth()->check()) {
            return;
        }

        $counts = Cache::remember('admin.nav.counts', 60, function (): array {
            return [
                'orders_pending' => Order::whereIn('status', ['pending', 'confirmed', 'processing', 'packed'])->count(),
                'low_stock' => ProductVariant::query()
                    ->where('status', 'active')
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->count(),
                'reviews_pending' => Review::where('is_approved', false)->count(),
                'refunds_pending' => Refund::where('status', 'pending')->count(),
            ];
        });

        $view->with('navCounts', $counts);
    }
}
