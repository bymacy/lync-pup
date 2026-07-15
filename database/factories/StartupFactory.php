<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StartupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'industry_sector' => fake()->randomElement(['AgriTech', 'FinTech', 'HealthTech']),
            'cohort_number' => fake()->numberBetween(1, 5),
            'contact_phone' => fake()->phoneNumber(),
            'location' => fake()->city().', PH',
        ];
    }
}