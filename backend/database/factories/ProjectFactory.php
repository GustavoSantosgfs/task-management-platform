<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'manager_id' => User::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'status' => fake()->randomElement(['planning', 'active', 'on_hold', 'completed']),
            'visibility' => fake()->randomElement(['public', 'private']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
