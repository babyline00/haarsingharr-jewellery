<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = \App\Models\Coupon::class;

    public function definition(): array
    {
        return [
            'code'     => strtoupper($this->faker->unique()->bothify('PROMO####')),
            'slug'     => \Illuminate\Support\Str::slug($this->faker->unique()->word.'-'.$this->faker->randomNumber(4)),
            'type'     => 'percentage',
            'value'    => 10,
            'status'    => 'active',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ];
    }
}
