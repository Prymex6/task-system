<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Tag;
use App\Models\Tenant\Task;
use Tests\TenantTestCase;

class SubtaskAndTagTest extends TenantTestCase
{
    public function test_manager_can_add_subtask(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.tasks.subtasks.store', $task), [
            'title' => 'Podzadanie A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'title' => 'Podzadanie A',
            'parent_task_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }

    public function test_manager_can_complete_subtask(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $subtask = Task::factory()->create([
            'project_id' => $task->project_id,
            'parent_task_id' => $task->id,
            'title' => 'Do zrobienia',
        ]);

        $response = $this->withoutTenantMiddleware()->put(
            route('tenant.manager.tasks.subtasks.update', [$task, $subtask]),
            ['title' => 'Do zrobienia', 'completed' => true]
        );

        $response->assertRedirect();
        $this->assertNotNull($subtask->fresh()->completed_at);
    }

    public function test_manager_can_delete_subtask(): void
    {
        $this->actingAsManager();
        $task = Task::factory()->create();
        $subtask = Task::factory()->create([
            'project_id' => $task->project_id,
            'parent_task_id' => $task->id,
        ]);

        $response = $this->withoutTenantMiddleware()->delete(
            route('tenant.manager.tasks.subtasks.destroy', [$task, $subtask])
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('tasks', ['id' => $subtask->id]);
    }

    public function test_tags_index_is_accessible(): void
    {
        $this->actingAsManager();
        Tag::create(['name' => 'backend', 'color' => '#ff0000']);

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.tags.index'));

        $response->assertStatus(200);
    }

    public function test_manager_can_create_tag(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.tags.store'), [
            'name' => 'frontend',
            'color' => '#00ff00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['name' => 'frontend']);
    }

    public function test_manager_can_update_tag(): void
    {
        $this->actingAsManager();
        $tag = Tag::create(['name' => 'old', 'color' => '#000000']);

        $response = $this->withoutTenantMiddleware()->put(route('tenant.manager.tags.update', $tag), [
            'name' => 'new-name',
            'color' => '#ffffff',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'new-name']);
    }

    public function test_manager_can_delete_tag(): void
    {
        $this->actingAsManager();
        $tag = Tag::create(['name' => 'temp', 'color' => '#123456']);

        $response = $this->withoutTenantMiddleware()->delete(route('tenant.manager.tags.destroy', $tag));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
