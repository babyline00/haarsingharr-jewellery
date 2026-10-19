<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'first_name'        => $this->faker->firstName,
            'last_name'         => $this->faker->lastName,
            'email'             => $this->faker->unique()->safeEmail,
            'phone_country'     => '+92',
            'phone'             => '3' . $this->faker->numberBetween(100000000, 999999999),
            'password'          => Hash::make('Haarsingharr@2025'),
            'type'               => 'customer',
            'status'             => 'active',
            'email_verified_at' => now(),
            'marketing_consent' => $this->faker->boolean(40),
        ];
    }

    public function staff(): self
    {
        return $this->state(fn () => ['type' => 'staff']);
    }

    public function admin(): self
    {
        return $this->state(fn () => ['type' => 'admin']);
    }

    public function suspended(): self
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }
}
