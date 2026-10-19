<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        $names = [
            'Haarsingharr Diamond Solitaire Ring',
            'Polki Heritage Necklace',
            'Emerald Drop Earrings',
            '22k Gold Bangles (Pair)',
            'Kundan Bridal Set',
            'Pearl Strand Choker',
            'Filigree Jhumkas',
            'Ruby Halo Pendant',
            'Platinum V-Band',
            'Gold Signet Ring',
        ];
        $name = $this->faker->unique()->randomElement($names);
        $regularPrice = $this->faker->randomElement([15000, 25000, 45000, 75000, 125000, 250000, 480000]);

        return [
            'name'        => $name,
            'sku'         => strtoupper($this->faker->unique()->bothify('AU-####??')),
            'description' => $this->faker->paragraph(5),
            'short_description' => $this->faker->sentence(8),
            'regular_price'  => $regularPrice,
            'sale_price'      => $this->faker->boolean(20) ? (int) round($regularPrice * 0.85) : null,
            'cost_price'      => (int) round($regularPrice * 0.65),
            'metal'           => $this->faker->randomElement(['Gold 22k', 'Gold 18k', 'Silver 925', 'Platinum']),
            'gemstone'        => $this->faker->optional()->randomElement(['Diamond', 'Emerald', 'Ruby', 'Sapphire', 'Pearl']),
            'weight_grams'    => $this->faker->randomFloat(2, 2, 40),
            'status'           => $this->faker->randomElement(ProductStatus::cases()),
            'is_featured'     => $this->faker->boolean(30),
            'is_best_seller'   => $this->faker->boolean(20),
            'is_new_arrival'   => $this->faker->boolean(30),
            'is_bridal'        => $this->faker->boolean(20),
            'is_men'           => $this->faker->boolean(15),
            'has_variants'     => false,
            'care_instructions'=> 'Store separately in a soft pouch. Clean with a soft, lint-free cloth.',
            'seo_title'          => $name.' | Haarsingharr',
            'seo_description'   => $this->faker->sentence(15),
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => ['status' => ProductStatus::Active]);
    }

    public function featured(): self
    {
        return $this->state(fn () => ['is_featured' => true, 'status' => ProductStatus::Active]);
    }

    public function bestSeller(): self
    {
        return $this->state(fn () => ['is_best_seller' => true, 'status' => ProductStatus::Active]);
    }

    public function withVariants(): self
    {
        return $this->state(fn () => ['has_variants' => true, 'variant_attributes' => ['Size']]);
    }
}
