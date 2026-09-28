<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Task;
use App\Models\Tenant\TaskComment;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskCommentFactory extends Factory
{
    protected $model = TaskComment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'client_contact_id' => null,
            'body' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }
}
