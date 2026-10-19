<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = \App\Models\Address::class;

    public function definition(): array
    {
        return [
            'label'             => $this->faker->randomElement(['Home', 'Office', 'Family']),
            'first_name'        => $this->faker->firstName,
            'last_name'         => $this->faker->lastName,
            'phone_country'     => '+92',
            'phone'             => '3' . $this->faker->numberBetween(100000000, 999999999),
            'address_line_1'    => $this->faker->streetAddress,
            'address_line_2'    => null,
            'landmark'          => $this->faker->optional()->streetName,
            'city'              => $this->faker->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan']),
            'province'          => 'punjab',
            'country'           => 'PK',
            'postal_code'       => $this->faker->numerify('#####'),
            'is_default_shipping' => true,
            'is_default_billing'  => true,
        ];
    }
}
