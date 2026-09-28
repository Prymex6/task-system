<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Project;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'task_id' => null,
            'date' => now()->toDateString(),
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'description' => fake()->sentence(),
            'is_billable' => true,
        ];
    }
}
