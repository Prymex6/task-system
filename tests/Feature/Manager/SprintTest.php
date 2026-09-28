<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Project;
use App\Models\Tenant\Sprint;
use App\Models\Tenant\Task;
use Tests\TenantTestCase;

class SprintTest extends TenantTestCase
{
    private function makeSprint(?Project $project = null, array $attrs = []): Sprint
    {
        $project ??= Project::factory()->create();

        return Sprint::create(array_merge([
            'project_id' => $project->id,
            'name' => 'Sprint ' . uniqid(),
            'status' => 'planning',
        ], $attrs));
    }

    public function test_sprints_index_is_accessible(): void
    {
        $this->actingAsManager();
        $this->makeSprint();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.sprints.index'));

        $response->assertStatus(200);
    }

    public function test_manager_can_create_sprint(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.sprints.store'), [
            'name' => 'Sprint 1',
            'project_id' => $project->id,
            'goal' => 'Dostarczyć MVP',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sprints', ['name' => 'Sprint 1', 'project_id' => $project->id, 'status' => 'planning']);
    }

    public function test_manager_can_start_sprint(): void
    {
        $this->actingAsManager();
        $sprint = $this->makeSprint();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.sprints.start', $sprint));

        $response->assertRedirect();
        $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'status' => 'active']);
    }

    public function test_manager_can_complete_sprint(): void
    {
        $this->actingAsManager();
        $sprint = $this->makeSprint(null, ['status' => 'active', 'started_at' => now()]);

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.sprints.complete', $sprint));

        $response->assertRedirect();
        $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'status' => 'completed']);
    }

    public function test_manager_can_add_task_to_sprint(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();
        $sprint = $this->makeSprint($project);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.sprints.tasks.add', $sprint), [
            'task_id' => $task->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sprint_tasks', ['sprint_id' => $sprint->id, 'task_id' => $task->id]);
    }

    public function test_manager_can_remove_task_from_sprint(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();
        $sprint = $this->makeSprint($project);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $sprint->tasks()->attach($task->id);

        $response = $this->withoutTenantMiddleware()->delete(route('tenant.manager.sprints.tasks.remove', [$sprint, $task]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sprint_tasks', ['sprint_id' => $sprint->id, 'task_id' => $task->id]);
    }

    public function test_manager_can_delete_sprint(): void
    {
        $this->actingAsManager();
        $sprint = $this->makeSprint();

        $response = $this->withoutTenantMiddleware()->delete(route('tenant.manager.sprints.destroy', $sprint));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    }
}
