<?php

namespace Database\Factories;

use App\Models\Roadblock;
use App\Models\Startup;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoadblockFactory extends Factory
{
    protected $model = Roadblock::class;

    public function definition(): array
    {
        return [
            'startup_id' => Startup::factory(),
            'problem_category' => $this->faker->randomElement([
                'Business Development',
                'Technical Support',
                'Market Research',
                'Strategy Consultant',
                'Others',
            ]),
            'description' => $this->faker->paragraph(),
            'status' => 'Pending',
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);
    }
}