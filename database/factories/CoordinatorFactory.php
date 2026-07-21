<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CoordinatorFactory extends Factory
{
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();
        $honorific = fake()->randomElement(['Sir', "Ma'am"]);

        return [
            'honorific' => $honorific,
            'first_name' => $first,
            'last_name' => $last,
            'name' => "{$honorific} {$first} {$last}",
            'role_title' => 'Portfolio Coordinator',
            'email' => fake()->safeEmail(),
            'phone' => '09'.fake()->numerify('#########'),
        ];
    }
}