<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Client;
use App\Models\Tenant\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => 'in_progress',
            'color' => '#6366f1',
            'visibility' => 'team',
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(['is_archived' => true]);
    }
}
