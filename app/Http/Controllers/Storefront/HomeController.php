<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Order;
use App\Models\StoreLocation;
<<<<<<< HEAD
use App\Models\StoreSetting;
use Illuminate\Http\Request;
=======
use App\Models\User;
>>>>>>> origin/main
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $preview = (bool) $request->boolean('preview') && auth()->user()?->isStaff();

        $sections = $preview
            ? HomepageSection::orderBy('sort_order')->orderBy('id')->get()
            : HomepageSection::published()->get();

        $heroBanners = Banner::atLocation('hero')->published()->get();
        $hero = $heroBanners->first();
        $announcement = Banner::atLocation('announcement')->published()->first();

        if (! $preview) {
            $heroBanners->each->recordImpression();
            $announcement?->recordImpression();
        }

        $data = [
            'sections'         => $sections,
            'isPreview'        => $preview,
            'heroBanner'       => $hero,
            'heroBanners'      => $heroBanners,
            'heroAutoplay'     => filter_var(StoreSetting::get('hero_autoplay', true), FILTER_VALIDATE_BOOLEAN),
            'heroAutoplaySeconds' => max(3, min(15, (int) StoreSetting::get('hero_autoplay_seconds', 6))),
            'announcement'     => $announcement,
            'featuredCategories' => Category::where('show_on_homepage', true)
                ->active()->ordered()->limit(8)->get(),
            'bestSellers'      => Product::active()->bestSellers()
                ->with(['images', 'variants'])->withCount('wishlistItems')->limit(8)->get(),
            'newArrivals'      => Product::active()->newArrivals()
                ->with(['images', 'variants'])->withCount('wishlistItems')->limit(8)->get(),
            'bridalCollection' => Product::active()->bridal()
                ->with(['images', 'variants'])->withCount('wishlistItems')->limit(4)->get(),
            'menCollection'    => Product::active()->men()
                ->with(['images', 'variants'])->withCount('wishlistItems')->limit(4)->get(),
            'saleItems'        => Product::active()->where('sale_price', '>', 0)
                ->with(['images', 'variants'])->withCount('wishlistItems')->limit(4)->get(),
            'trending'         => Product::active()
                ->with(['images', 'variants'])->withCount('wishlistItems')
                ->orderByDesc('view_count')->orderByDesc('units_sold')->limit(4)->get(),
            'homeStats'        => [
                'products'  => Product::active()->count(),
                'customers' => User::where('type', 'customer')->count(),
                'orders'    => Order::where('status', 'delivered')->count(),
                'views'     => (int) Product::sum('view_count'),
            ],
            'collections'       => Collection::active()->shownOnHomepage()->limit(6)->get(),
            'storeLocations'   => StoreLocation::active()->limit(3)->get(),
            'budgetCategories' => [
                ['min' => 0,       'max' => 15000,  'label' => 'Under Rs. 15,000'],
                ['min' => 15000,   'max' => 50000,  'label' => 'Rs. 15k – 50k'],
                ['min' => 50000,   'max' => 150000, 'label' => 'Rs. 50k – 150k'],
                ['min' => 150000,  'max' => 9999999, 'label' => 'Rs. 150k +'],
            ],
        ];

        return view('storefront.home.index', $data);
    }
}
