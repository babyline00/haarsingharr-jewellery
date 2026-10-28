<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\CmsPage;
use App\Models\Product;
use App\Models\StoreLocation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect();

        // Static pages
        $urls->push(['loc' => route('home'), 'priority' => '1.0']);
        $urls->push(['loc' => route('shop.index'), 'priority' => '0.9']);
        $urls->push(['loc' => route('shop.best_sellers'), 'priority' => '0.7']);
        $urls->push(['loc' => route('shop.new_arrivals'), 'priority' => '0.7']);
        $urls->push(['loc' => route('shop.sale'), 'priority' => '0.8']);
        $urls->push(['loc' => route('shop.bridal'), 'priority' => '0.7']);
        $urls->push(['loc' => route('shop.men'), 'priority' => '0.7']);
        $urls->push(['loc' => route('about'), 'priority' => '0.6']);
        $urls->push(['loc' => route('contact'), 'priority' => '0.6']);
        $urls->push(['loc' => route('faq'), 'priority' => '0.5']);
        $urls->push(['loc' => route('locations'), 'priority' => '0.5']);

        // Categories
        foreach (Category::active()->get() as $cat) {
            $urls->push(['loc' => route('categories.show', $cat), 'priority' => '0.8']);
        }

        // Collections
        foreach (Collection::active()->get() as $col) {
            $urls->push(['loc' => route('collections.show', $col), 'priority' => '0.7']);
        }

        // Products
        foreach (Product::active()->where('status', 'active')->get() as $prod) {
            $urls->push([
                'loc'      => route('products.show', $prod),
                'priority' => '0.9',
                'images'   => $prod->images->map(fn ($img) => \App\Support\Media::url($img->path))->all(),
                'lastmod'   => $prod->updated_at?->toDateString(),
            ]);
        }

        // CMS pages
        foreach (CmsPage::published()->get() as $page) {
            $urls->push(['loc' => route('pages.show', $page), 'priority' => '0.6']);
        }

        $xml = view('storefront.sitemap.xml', ['urls' => $urls])->render();
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
