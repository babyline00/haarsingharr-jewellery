<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = \App\Models\Order::class;

    public function definition(): array
    {
        return [
            'number'           => \App\Models\Order::nextOrderNumber(),
            'user_id'          => null,
            'guest_email'      => $this->faker->safeEmail,
            'guest_phone'      => '3' . $this->faker->numberBetween(100000000, 999999999),
            'status'           => OrderStatus::Pending,
            'payment_status'   => PaymentStatus::Unpaid,
            'fulfilment_status' => 'unfulfilled',
            'currency'         => 'PKR',
            'subtotal'         => 10000,
            'discount_total'   => 0,
            'shipping_total'   => 0,
            'tax_total'        => 0,
            'grand_total'      => 10000,
            'paid_amount'      => 0,
            'refunded_amount'   => 0,
        ];
    }
}
