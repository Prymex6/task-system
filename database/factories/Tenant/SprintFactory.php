<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Project;
use App\Models\Tenant\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint ' . fake()->numberBetween(1, 50),
            'goal' => fake()->sentence(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'status' => 'planning',
        ];
    }
}
