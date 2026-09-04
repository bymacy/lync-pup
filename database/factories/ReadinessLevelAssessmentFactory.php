<?php

namespace Database\Factories;

use App\Models\Startup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadinessLevelAssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'startup_id' => Startup::factory(),
            'evaluated_by' => fake()->name(),
            // Decimal (not a whole-number count) — matches the weighted
            // scoring formula in ReadinessRubric::scoreFromProgress(). No
            // matching *_progress JSON is seeded here, so these are
            // freestanding test values rather than derived from checkboxes.
            'trl_score' => fake()->randomFloat(1, 1, 9),
            'mrl_score' => fake()->randomFloat(1, 1, 9),
            'tmrl_score' => fake()->randomFloat(1, 1, 9),
            'srl_score' => fake()->randomFloat(1, 1, 9),
            'overall_score' => fake()->randomFloat(1, 1, 9),
            'assessment_date' => now(),
        ];
    }
}