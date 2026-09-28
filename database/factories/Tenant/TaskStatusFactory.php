<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskStatusFactory extends Factory
{
    protected $model = TaskStatus::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['To Do', 'In Progress', 'Done', 'Review']),
            'color' => fake()->hexColor(),
            'order' => fake()->numberBetween(0, 10),
            'is_default' => false,
            'is_closed' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function closed(): static
    {
        return $this->state(['is_closed' => true]);
    }
}
