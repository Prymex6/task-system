<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use Tests\TenantTestCase;

/**
 * The columns a task moves through.
 *
 * Every method on the controller answered "Feature coming soon", so the
 * statuses a board is built out of could not be added, renamed or removed.
 */
class TaskStatusTest extends TenantTestCase
{
    public function test_the_page_lists_the_statuses_in_board_order(): void
    {
        $this->actingAsManager();
        TaskStatus::create(['name' => 'Done', 'color' => '#16a34a', 'order' => 2]);
        TaskStatus::create(['name' => 'Todo', 'color' => '#6b7280', 'order' => 1]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.task-statuses.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Manager/Settings/TaskStatuses')
                ->where('statuses.0.name', 'Todo')
                ->where('statuses.1.name', 'Done')
            );
    }

    public function test_a_status_is_added_at_the_end_of_the_board(): void
    {
        $this->actingAsManager();
        TaskStatus::create(['name' => 'Todo', 'color' => '#6b7280', 'order' => 5]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.task-statuses.store'), ['name' => 'Review', 'color' => '#f59e0b'])
            ->assertRedirect();

        $this->assertSame(6, TaskStatus::where('name', 'Review')->sole()->order);
    }

    public function test_two_statuses_cannot_share_a_name(): void
    {
        $this->actingAsManager();
        TaskStatus::create(['name' => 'Todo', 'color' => '#6b7280']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.task-statuses.store'), ['name' => 'Todo', 'color' => '#111111'])
            ->assertSessionHasErrors('name');
    }

    public function test_only_one_status_stays_the_default(): void
    {
        $this->actingAsManager();
        $first = TaskStatus::create(['name' => 'Todo', 'color' => '#6b7280', 'is_default' => true]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.task-statuses.store'), [
                'name' => 'Backlog',
                'color' => '#111111',
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue(TaskStatus::where('name', 'Backlog')->sole()->is_default);
    }

    public function test_a_status_with_tasks_on_it_is_not_deleted(): void
    {
        $this->actingAsManager();
        $status = TaskStatus::create(['name' => 'Todo', 'color' => '#6b7280']);
        Task::factory()->create(['task_status_id' => $status->id]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.task-statuses.destroy', $status->id))
            ->assertRedirect();

        $this->assertNotNull($status->fresh());
    }

    public function test_an_empty_status_is_deleted(): void
    {
        $this->actingAsManager();
        $status = TaskStatus::create(['name' => 'Unused', 'color' => '#6b7280']);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.task-statuses.destroy', $status->id))
            ->assertRedirect();

        $this->assertNull($status->fresh());
    }

    public function test_dragging_the_board_saves_the_new_order(): void
    {
        $this->actingAsManager();
        $a = TaskStatus::create(['name' => 'A', 'color' => '#111111', 'order' => 0]);
        $b = TaskStatus::create(['name' => 'B', 'color' => '#222222', 'order' => 1]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.task-statuses.reorder'), ['ids' => [$b->id, $a->id]])
            ->assertRedirect();

        $this->assertSame(0, $b->fresh()->order);
        $this->assertSame(1, $a->fresh()->order);
    }
}
