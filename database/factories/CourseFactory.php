<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'level'       => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', null]),
            'duration'    => $this->faker->randomFloat(1, 1, 40),
            'status'      => 'published',
            'privacy'     => 'public',
            'created_by'  => User::factory(),
        ];
    }
}
