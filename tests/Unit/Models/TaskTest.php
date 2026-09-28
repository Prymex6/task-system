<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskComment;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TenantTestCase;

class TaskTest extends TenantTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_it_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    /** @test */
    public function test_it_belongs_to_assignee(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $task->assignees()->attach($user->id);

        $this->assertTrue($task->assignees->contains($user->id));
        $this->assertInstanceOf(User::class, $task->assignees->first());
    }

    /** @test */
    public function test_it_has_many_comments(): void
    {
        $task = Task::factory()->create();
        $user = User::factory()->create();

        TaskComment::factory()->count(3)->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $task->comments);
    }

    /** @test */
    public function test_it_has_many_time_entries(): void
    {
        $task = Task::factory()->create();
        $user = User::factory()->create();

        TimeEntry::factory()->count(2)->create([
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'user_id' => $user->id,
            'hours' => 1.5,
        ]);

        $this->assertCount(2, $task->timeEntries);
        $this->assertEquals(3.0, (float) $task->timeEntries()->sum('hours'));
    }

    /** @test */
    public function test_it_can_be_assigned_to_user(): void
    {
        $task = Task::factory()->create();
        $user = User::factory()->create();

        $task->assignees()->sync([$user->id]);

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function test_it_has_status_transitions(): void
    {
        $statusA = TaskStatus::factory()->create(['name' => 'To Do',      'is_closed' => false]);
        $statusB = TaskStatus::factory()->create(['name' => 'In Progress', 'is_closed' => false]);
        $statusC = TaskStatus::factory()->create(['name' => 'Done',       'is_closed' => true]);

        $task = Task::factory()->create(['task_status_id' => $statusA->id]);

        $this->assertEquals($statusA->id, $task->task_status_id);
        $this->assertFalse($task->isCompleted());

        $task->update(['task_status_id' => $statusC->id]);
        $task->load('status');

        $this->assertTrue($task->isCompleted());
    }

    /** @test */
    public function test_it_calculates_overdue_status(): void
    {
        $task = Task::factory()->create([
            'due_date' => now()->subDays(3)->toDateString(),
            'completed_at' => null,
        ]);

        $this->assertTrue($task->isOverdue());
    }

    /** @test */
    public function test_completed_task_is_not_overdue(): void
    {
        $task = Task::factory()->create([
            'due_date' => now()->subDays(3)->toDateString(),
            'completed_at' => now()->subDay(),
        ]);

        $this->assertFalse($task->isOverdue());
    }

    /** @test */
    public function test_task_without_due_date_is_not_overdue(): void
    {
        $task = Task::factory()->create(['due_date' => null, 'completed_at' => null]);

        $this->assertFalse($task->isOverdue());
    }

    /** @test */
    public function test_scope_overdue_returns_past_due_incomplete_tasks(): void
    {
        Task::factory()->create(['due_date' => now()->subDay(), 'completed_at' => null]);
        Task::factory()->create(['due_date' => now()->addDay(), 'completed_at' => null]);
        Task::factory()->create(['due_date' => now()->subDay(), 'completed_at' => now()]);

        $overdue = Task::overdue()->get();

        $this->assertCount(1, $overdue);
    }
}
