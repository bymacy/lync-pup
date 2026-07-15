<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CoordinatorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Sir '.fake()->firstName(),
            'role_title' => 'Portfolio Coordinator',
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}