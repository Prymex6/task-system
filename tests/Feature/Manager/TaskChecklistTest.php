<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskChecklist;
use App\Models\Tenant\TaskChecklistItem;
use Tests\TenantTestCase;

/**
 * Checklists on a task, and the items inside them.
 *
 * All six verbs answered "Feature coming soon" before, so a checklist could
 * be neither created nor ticked off.
 */
class TaskChecklistTest extends TenantTestCase
{
    private function task(): Task
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $project->members()->attach($user->id, ['project_role' => 'project_manager', 'added_by' => $user->id]);

        return Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
    }

    public function test_manager_can_add_a_checklist_to_a_task(): void
    {
        $task = $this->task();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.checklists.store', $task), ['title' => 'Przed wdrożeniem'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_checklists', ['task_id' => $task->id, 'title' => 'Przed wdrożeniem']);
    }

    public function test_checklist_requires_a_title(): void
    {
        $task = $this->task();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.checklists.store', $task), [])
            ->assertSessionHasErrors('title');
    }

    public function test_new_checklists_are_appended_rather_than_renumbered(): void
    {
        $task = $this->task();

        foreach (['Pierwsza', 'Druga', 'Trzecia'] as $title) {
            $this->withoutTenantMiddleware()
                ->post(route('tenant.manager.tasks.checklists.store', $task), ['title' => $title]);
        }

        $this->assertSame([1, 2, 3], TaskChecklist::orderBy('order')->pluck('order')->all());
    }

    public function test_manager_can_rename_a_checklist(): void
    {
        $task = $this->task();
        $checklist = TaskChecklist::create(['task_id' => $task->id, 'title' => 'Stara', 'order' => 1]);

        $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.checklists.update', [$task, $checklist]), ['title' => 'Nowa'])
            ->assertRedirect();

        $this->assertSame('Nowa', $checklist->fresh()->title);
    }

    public function test_a_checklist_from_another_task_is_not_found(): void
    {
        $task = $this->task();
        $other = $this->task();
        $checklist = TaskChecklist::create(['task_id' => $other->id, 'title' => 'Obca', 'order' => 1]);

        $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.tasks.checklists.update', [$task, $checklist]), ['title' => 'Podmiana'])
            ->assertNotFound();
    }

    public function test_deleting_a_checklist_takes_its_items_with_it(): void
    {
        $task = $this->task();
        $checklist = TaskChecklist::create(['task_id' => $task->id, 'title' => 'Lista', 'order' => 1]);
        TaskChecklistItem::create(['task_checklist_id' => $checklist->id, 'content' => 'Pozycja', 'order' => 1]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.tasks.checklists.destroy', [$task, $checklist]))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_checklists', ['id' => $checklist->id]);
        $this->assertDatabaseMissing('task_checklist_items', ['task_checklist_id' => $checklist->id]);
    }

    public function test_manager_can_add_an_item(): void
    {
        $task = $this->task();
        $checklist = TaskChecklist::create(['task_id' => $task->id, 'title' => 'Lista', 'order' => 1]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.checklists.items.store', [$task, $checklist]), [
                'content' => 'Sprawdzić migracje',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_checklist_items', [
            'task_checklist_id' => $checklist->id,
            'content' => 'Sprawdzić migracje',
        ]);
    }

    public function test_ticking_an_item_stamps_the_time_and_unticking_clears_it(): void
    {
        $task = $this->task();
        $checklist = TaskChecklist::create(['task_id' => $task->id, 'title' => 'Lista', 'order' => 1]);
        $item = TaskChecklistItem::create([
            'task_checklist_id' => $checklist->id,
            'content' => 'Pozycja',
            'order' => 1,
        ]);

        $route = route('tenant.manager.tasks.checklists.items.update', [$task, $checklist, $item]);

        $this->withoutTenantMiddleware()->put($route, ['is_completed' => true]);
        $this->assertNotNull($item->fresh()->completed_at);

        $this->withoutTenantMiddleware()->put($route, ['is_completed' => false]);
        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_manager_can_delete_an_item(): void
    {
        $task = $this->task();
        $checklist = TaskChecklist::create(['task_id' => $task->id, 'title' => 'Lista', 'order' => 1]);
        $item = TaskChecklistItem::create([
            'task_checklist_id' => $checklist->id,
            'content' => 'Pozycja',
            'order' => 1,
        ]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.tasks.checklists.items.destroy', [$task, $checklist, $item]))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_checklist_items', ['id' => $item->id]);
    }
}
