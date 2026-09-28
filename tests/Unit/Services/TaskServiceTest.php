<?php

namespace Tests\Unit\Services;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use App\Services\TaskService;
use Tests\TenantTestCase;

class TaskServiceTest extends TenantTestCase
{
    private function makeUser(): User
    {
        return User::factory()->create(['workspace_role' => 'admin']);
    }

    private function makeStatus(bool $isClosed = false): TaskStatus
    {
        return TaskStatus::factory()->create([
            'name' => $isClosed ? 'Done' : 'To Do',
            'is_closed' => $isClosed,
        ]);
    }

    public function test_create_creates_task_with_creator(): void
    {
        $user = $this->makeUser();
        $status = $this->makeStatus();
        $project = Project::factory()->create();

        $task = TaskService::create([
            'title' => 'Nowe zadanie przez serwis',
            'project_id' => $project->id,
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ], $user);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($user->id, $task->created_by);
        $this->assertDatabaseHas('tasks', ['title' => 'Nowe zadanie przez serwis']);
    }

    public function test_create_assigns_default_status_when_none_given(): void
    {
        $user = $this->makeUser();
        $status = TaskStatus::factory()->create(['name' => 'Domyślny', 'order' => 1]);
        $project = Project::factory()->create();

        $task = TaskService::create([
            'title' => 'Zadanie bez statusu',
            'project_id' => $project->id,
            'priority' => 'low',
        ], $user);

        $this->assertEquals($status->id, $task->task_status_id);
    }

    public function test_create_syncs_assignees(): void
    {
        $user = $this->makeUser();
        $assignee = User::factory()->create(['workspace_role' => 'member']);
        $status = $this->makeStatus();
        $project = Project::factory()->create();

        $task = TaskService::create([
            'title' => 'Zadanie z przypisaniem',
            'project_id' => $project->id,
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'assignees' => [$assignee->id],
        ], $user);

        $this->assertTrue($task->assignees()->where('user_id', $assignee->id)->exists());
    }

    public function test_update_changes_task_fields(): void
    {
        $user = $this->makeUser();
        $task = Task::factory()->create(['title' => 'Stary tytuł', 'priority' => 'low']);

        $updated = TaskService::update($task, [
            'title' => 'Nowy tytuł',
            'priority' => 'high',
        ]);

        $this->assertEquals('Nowy tytuł', $updated->title);
        $this->assertEquals('high', $updated->priority);
    }

    public function test_update_sets_completed_at_when_marked_complete(): void
    {
        $user = $this->makeUser();
        $task = Task::factory()->create(['completed_at' => null]);

        TaskService::update($task, ['is_completed' => true]);

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_update_clears_completed_at_when_reopened(): void
    {
        $user = $this->makeUser();
        $task = Task::factory()->create(['completed_at' => now()]);

        TaskService::update($task, ['is_completed' => false]);

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_change_status_updates_task_status_id(): void
    {
        $task = Task::factory()->create();
        $newStatus = $this->makeStatus();

        TaskService::changeStatus($task, $newStatus->id);

        $this->assertEquals($newStatus->id, $task->fresh()->task_status_id);
    }

    public function test_change_status_marks_complete_when_status_is_closed(): void
    {
        $task = Task::factory()->create(['completed_at' => null]);
        $doneStatus = $this->makeStatus(true);

        TaskService::changeStatus($task, $doneStatus->id);

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_change_status_clears_completed_at_when_status_is_open(): void
    {
        $task = Task::factory()->create(['completed_at' => now()]);
        $openStatus = $this->makeStatus(false);

        TaskService::changeStatus($task, $openStatus->id);

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_delete_removes_task_from_database(): void
    {
        $task = Task::factory()->create();
        $id = $task->id;

        TaskService::delete($task);

        $this->assertDatabaseMissing('tasks', ['id' => $id]);
    }

    public function test_duplicate_creates_copy_with_kopia_suffix(): void
    {
        $user = $this->makeUser();
        $task = Task::factory()->create(['title' => 'Oryginalne zadanie']);

        $clone = TaskService::duplicate($task, $user);

        $this->assertDatabaseHas('tasks', ['title' => 'Oryginalne zadanie (kopia)']);
        $this->assertEquals($user->id, $clone->created_by);
        $this->assertNull($clone->completed_at);
    }
}
