<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Models\ProductVariant;
use App\Models\StoreLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce([
            RolesAndPermissionsSeeder::class,
        ]);

        $this->seedCategories();
        $this->seedCollections();
        $this->seedProducts();
        $this->seedBanners();
        $this->seedHomepageSections();
        $this->seedCmsPages();
        $this->seedFaqs();
        $this->seedStoreLocations();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Rings', 'description' => 'Engagement, solitaire and statement rings.'],
            ['name' => 'Necklaces', 'description' => 'Chains, pendants and statement necklaces.'],
            ['name' => 'Earrings', 'description' => 'Studs, jhumkas and chandbalis.'],
            ['name' => 'Bracelets', 'description' => 'Kadas, cuffs and tennis bracelets.'],
            ['name' => 'Bangles', 'description' => 'Stackable and bridal bangles.'],
            ['name' => 'Mangalsutra', 'description' => 'Traditional South-Asian bridal chains.'],
            ['name' => 'Nose Pins', 'description' => 'Studs and hoop nose jewellery.'],
            ['name' => 'Anklets', 'description' => 'Payal and chain anklets.'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                array_merge($cat, [
                    'show_in_menu'     => true,
                    'show_on_homepage' => true,
                    'status'           => 'active',
                ])
            );
        }
    }

    private function seedCollections(): void
    {
        $collections = [
            ['name' => 'The Bridal Atelier', 'description' => 'Hand-crafted 22k gold for the bride.'],
            ['name' => 'Polki Heritage', 'description' => 'Uncut diamond polki pieces.'],
            ['name' => 'Modern Minimalist', 'description' => 'Subtle everyday jewellery.'],
            ['name' => 'Statement Pieces', 'description' => 'Bold, eye-catching designs.'],
        ];

        foreach ($collections as $col) {
            Collection::firstOrCreate(
                ['slug' => Str::slug($col['name'])],
                array_merge($col, [
                    'status'           => 'active',
                    'show_on_homepage' => true,
                ])
            );
        }
    }

    private function seedProducts(): void
    {
        $categories = Category::all();
        $collections = Collection::all();

        // Generate 30 products
        Product::factory()->count(30)->active()->create()->each(function ($product) use ($categories, $collections) {
            $product->update(['category_id' => $categories->random()->id]);
            $product->collections()->attach($collections->random(2)->pluck('id'));

            // Add 3-4 images per product (using fake paths)
            foreach (range(1, 4) as $i) {
                ProductImage::create([
                    'product_id'  => $product->id,
                    'path'        => 'products/sample-'.$product->id.'-'.$i.'.jpg',
                    'alt_text'    => $product->name.' view '.$i,
                    'is_primary' => $i === 1,
                    'sort_order'  => $i,
                    'type'        => 'gallery',
                ]);
            }

            // Add a default variant with stock
            ProductVariant::factory()->create([
                'product_id'  => $product->id,
                'is_default'  => true,
                'regular_price' => $product->regular_price,
                'sale_price'    => $product->sale_price,
            ]);
        });
    }

    private function seedBanners(): void
    {
        Banner::create([
            'name'        => 'Hero — Bridal Collection',
            'location'    => 'hero',
            'image'       => 'banners/hero-bridal.jpg',
            'heading'     => 'Jewelry For Every You',
            'subheading'  => 'New Bridal Collection',
            'link_url'    => '/shop/bridal',
            'link_text'   => 'Discover the Collection',
            'is_published'=> true,
            'sort_order'  => 1,
        ]);

        Banner::create([
            'name'        => 'Announcement — Free Shipping',
            'location'    => 'announcement',
            'image'       => 'banners/announcement.jpg',
            'heading'     => 'Complimentary shipping & free returns within 7 days',
            'link_url'    => '/pages/shipping-policy',
            'is_published'=> true,
        ]);
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            ['key' => 'hero_banner',     'title' => 'Hero Banner',           'view' => 'storefront.home.sections.hero'],
            ['key' => 'featured_categories', 'title' => 'Featured Categories', 'view' => 'storefront.home.sections.featured-categories'],
            ['key' => 'best_sellers',   'title' => 'Bestsellers',            'view' => 'storefront.home.sections.best-sellers'],
            ['key' => 'new_arrivals',   'title' => 'New Arrivals',            'view' => 'storefront.home.sections.new-arrivals'],
            ['key' => 'bridal',          'title' => 'Bridal Atelier',         'view' => 'storefront.home.sections.bridal'],
            ['key' => 'shop_by_budget', 'title' => 'Shop by Budget',         'view' => 'storefront.home.sections.budget'],
            ['key' => 'testimonials',   'title' => 'Customer Testimonials',   'view' => 'storefront.home.sections.testimonials'],
            ['key' => 'service_highlights', 'title' => 'Service Highlights', 'view' => 'storefront.home.sections.services'],
            ['key' => 'store_locations','title' => 'Our Boutiques',           'view' => 'storefront.home.sections.stores'],
        ];

        foreach ($sections as $i => $section) {
            HomepageSection::create(array_merge($section, [
                'is_published' => true,
                'sort_order'   => $i + 1,
            ]));
        }
    }

    private function seedCmsPages(): void
    {
        $pages = [
            ['slug' => 'about', 'title' => 'About Haarsingharr', 'content' => 'Haarsingharr was founded on the belief that fine jewellery should last a lifetime — and then be passed on.'],
            ['slug' => 'shipping-policy', 'title' => 'Shipping Policy', 'content' => 'Complimentary nationwide shipping on every order. Standard delivery 2–4 business days via TCS/Leopards.'],
            ['slug' => 'exchange-policy', 'title' => 'Exchange & Refund Policy', 'content' => '7-day exchange on unworn pieces in original packaging. Refunds processed within 5–7 business days.'],
            ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'content' => 'We protect your data with industry-standard security measures.'],
            ['slug' => 'terms', 'title' => 'Terms & Conditions', 'content' => 'By using haarsingharr.example you agree to these terms.'],
            ['slug' => 'jewellery-care', 'title' => 'Jewellery Care Guide', 'content' => 'Store separately, clean with a soft cloth, avoid contact with perfumes and chemicals.'],
            ['slug' => 'size-guides', 'title' => 'Size Guides', 'content' => 'Find your ring size, bangle size and necklace length.'],
        ];

        foreach ($pages as $page) {
            CmsPage::firstOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, [
                    'status'        => 'published',
                    'published_at'  => now(),
                ])
            );
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            ['category' => 'Orders', 'q' => 'How long does delivery take?', 'a' => 'Standard delivery takes 2–4 business days via TCS or Leopards. Express delivery (next business day) is available at checkout.'],
            ['category' => 'Returns', 'q' => 'Can I return or exchange an item?', 'a' => 'Yes — within 7 days of delivery, unworn pieces in their original packaging can be exchanged or refunded. See our Exchange Policy for details.'],
            ['category' => 'Authenticity', 'q' => 'Is your gold hallmarked?', 'a' => 'Every piece comes with a hallmark certificate verifying 22k or 18k gold purity.'],
            ['category' => 'Payments', 'q' => 'Which payment methods are accepted?', 'a' => 'We accept Cash on Delivery nationwide, JazzCash, and Easypaisa wallets. International cards via Stripe (where enabled).'],
            ['category' => 'Custom', 'q' => 'Do you offer bespoke designs?', 'a' => 'Yes — book a private consultation via WhatsApp or our contact page to discuss your bespoke piece.'],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::create([
                'question'    => $faq['q'],
                'answer'      => $faq['a'],
                'category'    => $faq['category'],
                'is_published'=> true,
                'sort_order'  => $i + 1,
            ]);
        }
    }

    private function seedStoreLocations(): void
    {
        $locations = [
            ['name' => 'Lahore — MM Alam', 'address' => 'MM Alam Road, Gulberg III, Lahore', 'phone' => '+92 42 111 111', 'hours' => 'Mon–Sat, 10:00–20:00', 'is_main' => true],
            ['name' => 'Karachi — Dolmen', 'address' => 'Dolmen Mall Clifton, Karachi', 'phone' => '+92 21 111 111', 'hours' => 'Mon–Sun, 11:00–22:00'],
            ['name' => 'Islamabad — Centaurus', 'address' => 'Centaurus Mall, F-8, Islamabad', 'phone' => '+92 51 111 111', 'hours' => 'Mon–Sun, 11:00–22:00'],
        ];

        foreach ($locations as $i => $loc) {
            StoreLocation::create(array_merge($loc, [
                'slug' => Str::slug($loc['name']),
                'status' => 'active',
                'sort_order' => $i + 1,
            ]));
        }
    }
}
