<?php

namespace Tests\Feature\Tenant\Task;

use App\Models\Tenant\Project;
use App\Models\Tenant\Sprint;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

class TaskTest extends TenantTestCase
{
    private function makeStatus(array $attrs = []): TaskStatus
    {
        return TaskStatus::factory()->create(array_merge(['name' => 'To Do', 'is_default' => false], $attrs));
    }

    /** @test */
    public function test_user_can_list_tasks(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Tasks/Index')
            ->has('tasks')
        );
    }

    /** @test */
    public function test_user_can_view_task(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create(['title' => 'Viewable Task']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.show', $task));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Tasks/Show')
            ->where('task.id', $task->id)
        );
    }

    /** @test */
    public function test_user_can_create_task(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();
        $status = $this->makeStatus();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.store'), [
                'title' => 'New Test Task',
                'project_id' => $project->id,
                'task_status_id' => $status->id,
                'priority' => 'medium',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Test Task',
            'project_id' => $project->id,
        ]);
    }

    /** @test */
    public function test_task_creation_requires_project_id(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.store'), [
                'title' => 'No Project Task',
                'priority' => 'low',
            ]);

        $response->assertSessionHasErrors('project_id');
    }

    /** @test */
    public function test_task_creation_requires_title(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.store'), [
                'project_id' => $project->id,
                'priority' => 'low',
            ]);

        $response->assertSessionHasErrors('title');
    }

    /** @test */
    public function test_user_can_update_task_status(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $newStatus = $this->makeStatus(['name' => 'Done', 'is_closed' => true]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.update', $task), [
                'title' => $task->title,
                'project_id' => $task->project_id,
                'task_status_id' => $newStatus->id,
                'priority' => $task->priority,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_status_id' => $newStatus->id,
        ]);
    }

    /** @test */
    public function test_user_can_assign_task(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $member = User::factory()->create(['is_active' => true]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.update', $task), [
                'title' => $task->title,
                'priority' => $task->priority,
                'assignees' => [$member->id],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function test_user_can_set_due_date(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $dueDate = now()->addDays(7)->toDateString();

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.update', $task), [
                'title' => $task->title,
                'priority' => $task->priority,
                'due_date' => $dueDate,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'due_date' => $dueDate,
        ]);
    }

    /** @test */
    public function test_user_can_add_comment_to_task(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.comments.store', $task), [
                'body' => 'This is a test comment.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'body' => 'This is a test comment.',
        ]);
    }

    /** @test */
    public function test_user_can_delete_task(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.tasks.destroy', $task));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function test_task_can_be_moved_between_sprints(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();
        $sprint = Sprint::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.sprints.tasks.add', $sprint), [
                'task_id' => $task->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sprint_tasks', [
            'sprint_id' => $sprint->id,
            'task_id' => $task->id,
        ]);
    }
}
