<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Category;
use App\Models\Collection;
use App\Models\StoreLocation;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $sections = HomepageSection::published()->get();

        $data = [
            'sections'         => $sections,
            'heroBanner'       => Banner::atLocation('hero')->published()->first(),
            'announcement'     => Banner::atLocation('announcement')->published()->first(),
            'featuredCategories' => Category::where('show_on_homepage', true)
                ->active()->ordered()->limit(8)->get(),
            'bestSellers'      => Product::active()->bestSellers()
                ->with(['images', 'variants'])->limit(8)->get(),
            'newArrivals'      => Product::active()->newArrivals()
                ->with(['images', 'variants'])->limit(8)->get(),
            'bridalCollection' => Product::active()->bridal()
                ->with(['images', 'variants'])->limit(4)->get(),
            'menCollection'    => Product::active()->men()
                ->with(['images', 'variants'])->limit(4)->get(),
            'saleItems'        => Product::active()->where('sale_price', '>', 0)
                ->with(['images', 'variants'])->limit(4)->get(),
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
