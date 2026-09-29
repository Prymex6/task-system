<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use Tests\TenantTestCase;

class TaskTest extends TenantTestCase
{
    private function makeProject(): Project
    {
        return Project::factory()->create();
    }

    private function makeStatus(): TaskStatus
    {
        return TaskStatus::factory()->create(['name' => 'To Do', 'is_default' => true]);
    }

    public function test_manager_can_create_task(): void
    {
        $this->actingAsManager();
        $project = $this->makeProject();
        $status = $this->makeStatus();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.store'), [
                'project_id' => $project->id,
                'task_status_id' => $status->id,
                'title' => 'Nowe zadanie testowe',
                'priority' => 'medium',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', ['title' => 'Nowe zadanie testowe']);
    }

    public function test_manager_can_update_task(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create(['title' => 'Stary tytuł']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.update', $task), [
                'title' => 'Nowy tytuł',
                'project_id' => $task->project_id,
                'task_status_id' => $task->task_status_id,
                'priority' => 'high',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Nowy tytuł']);
    }

    public function test_manager_can_change_task_status(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $newStatus = TaskStatus::factory()->create(['name' => 'In Progress']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.update', $task), [
                'title' => $task->title,
                'project_id' => $task->project_id,
                'task_status_id' => $newStatus->id,
                'priority' => $task->priority,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'task_status_id' => $newStatus->id]);
    }

    public function test_manager_can_delete_task(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.tasks.destroy', $task));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_show_page_loads(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create(['title' => 'Zadanie do podglądu']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.show', $task));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page->where('task.id', $task->id)
        );
    }

    public function test_tasks_index_returns_paginated_results(): void
    {
        $this->actingAsManager();
        Task::factory()->count(5)->create();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('tasks'));
    }

    /**
     * The project is in the path, not the query string. Reading only the query
     * string left the field empty, so a task started from inside a project came
     * straight back demanding the project it was started from.
     */
    public function test_the_create_form_preselects_the_project_it_was_opened_from(): void
    {
        $this->actingAsManager();

        $project = Project::factory()->create();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.tasks.create', $project))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('defaultProjectId', $project->id));
    }

    public function test_the_create_form_preselects_nothing_when_opened_on_its_own(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.create'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('defaultProjectId', null));
    }
}
