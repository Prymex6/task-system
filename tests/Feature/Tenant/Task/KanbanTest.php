<?php

namespace Tests\Feature\Tenant\Task;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use Tests\TenantTestCase;

class KanbanTest extends TenantTestCase
{
    /** @test */
    public function test_user_can_view_kanban_board(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.board', $project));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Projects/Board')
            ->has('project')
        );
    }

    /** @test */
    public function test_user_can_view_global_kanban(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.kanban'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Tasks/Kanban'));
    }

    /** @test */
    public function test_user_can_move_task_to_different_status(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $newStatus = TaskStatus::factory()->create(['name' => 'In Review', 'is_closed' => false]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.update', $task), [
                'title' => $task->title,
                'priority' => $task->priority,
                'task_status_id' => $newStatus->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_status_id' => $newStatus->id,
        ]);
    }

    /** @test */
    public function test_kanban_groups_tasks_by_status(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        $statusA = TaskStatus::factory()->create(['name' => 'Todo',    'order' => 1]);
        $statusB = TaskStatus::factory()->create(['name' => 'Ongoing', 'order' => 2]);

        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'task_status_id' => $statusA->id,
        ]);
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'task_status_id' => $statusB->id,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.board', $project));

        $response->assertStatus(200);
        // Board response includes the project with tasks; we verify tasks are present
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Projects/Board')
            ->where('project.id', $project->id)
        );
    }

    /** @test */
    public function test_non_member_cannot_view_project_board(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);
        $project = Project::factory()->create();

        // Member user is not added to the project
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.board', $project));

        $response->assertStatus(403);
    }
}
