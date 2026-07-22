<?php

namespace Database\Factories;

use App\Models\InformationSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

class StartupReferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'info_sheet_id' => InformationSheet::factory(),
            'name' => fake()->name(),
            'contact' => '09'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
        ];
    }
}