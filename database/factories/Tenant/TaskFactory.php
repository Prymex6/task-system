<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_status_id' => TaskStatus::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'is_billable' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(['completed_at' => now()]);
    }
}
