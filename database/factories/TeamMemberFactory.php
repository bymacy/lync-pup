<?php

namespace Database\Factories;

use App\Models\Startup;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'startup_id' => Startup::factory(),
            'full_name' => fake()->name(),
            'role' => fake()->randomElement(['CEO', 'CTO', 'COO', 'Finance']),
            'email' => fake()->safeEmail(),
        ];
    }
}