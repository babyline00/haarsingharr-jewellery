<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = \App\Models\ProductVariant::class;

    public function definition(): array
    {
        return [
            'sku'              => strtoupper($this->faker->unique()->bothify('V-####??')),
            'title'            => $this->faker->optional()->words(3, true),
            'attributes'       => ['Size' => $this->faker->randomElement(['14', '16', '18', '20'])],
            'regular_price'    => $this->faker->randomElement([15000, 25000, 45000, 75000]),
            'sale_price'       => null,
            'cost_price'       => $this->faker->randomElement([10000, 15000, 25000]),
            'stock_quantity'   => $this->faker->randomElement([0, 5, 10, 25, 50]),
            'low_stock_threshold' => 5,
            'is_default'       => false,
            'allow_backorder'  => false,
            'status'           => 'active',
            'weight_grams'     => $this->faker->randomFloat(2, 2, 40),
        ];
    }
}
