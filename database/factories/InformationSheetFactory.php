<?php

namespace Database\Factories;

use App\Models\Startup;
use Illuminate\Database\Eloquent\Factories\Factory;

class InformationSheetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'startup_id' => Startup::factory(),
            'business_description' => fake()->sentence(15),
            'target_market' => fake()->sentence(10),
            'problem_statement' => fake()->sentence(10),
            'solution_offered' => fake()->sentence(10),
            'submission_date' => now(),
            'approval_status' => 'Pending',
        ];
    }
}