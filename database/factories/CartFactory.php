<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = \App\Models\Cart::class;

    public function definition(): array
    {
        return [
            'user_id'   => null,
            'session_id' => \Illuminate\Support\Str::uuid()->toString(),
            'currency' => 'PKR',
            'subtotal' => 0,
            'discount' => 0,
            'shipping' => 0,
            'tax'      => 0,
            'total'    => 0,
            'expires_at' => now()->addDays(7),
        ];
    }
}
