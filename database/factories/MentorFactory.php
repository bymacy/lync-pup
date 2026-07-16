<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MentorFactory extends Factory
{
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();
        $honorific = fake()->randomElement(['Mr.', 'Ms.', 'Dr.', 'Prof.', 'Atty.', 'Engr.']);

        return [
            'honorific' => $honorific,
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => "{$honorific} {$first} {$last}",
            'specialization' => fake()->randomElement(['Engineering', 'Business', 'Marketing', 'Legal', 'Finance', 'Technology']),
            'contact_email' => fake()->safeEmail(),
            'contact_number' => '09'.fake()->numerify('#########'),
        ];
    }
}