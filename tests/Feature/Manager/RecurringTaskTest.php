<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskRecurring;
use App\Services\TaskRecurringService;
use Tests\TenantTestCase;

/**
 * Repeating tasks.
 *
 * The whole feature queried columns the schema has never had — is_recurring and
 * next_run_at on tasks, is_active on the schedule — so the screen was a 500 and
 * nothing ever repeated. These cover the shape it actually stores.
 */
class RecurringTaskTest extends TenantTestCase
{
    private function schedule(array $overrides = []): TaskRecurring
    {
        $task = Task::factory()->create([
            'project_id' => Project::factory()->create()->id,
        ]);

        return TaskRecurring::create(array_merge([
            'task_id' => $task->id,
            'frequency' => 'weekly',
            'interval' => 1,
            'next_occurrence' => today(),
        ], $overrides));
    }

    public function test_the_schedule_list_is_accessible(): void
    {
        $this->actingAsManager();
        $this->schedule();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.tasks.recurring.index'))
            ->assertSuccessful();
    }

    /**
     * The literal has to be declared ahead of /tasks/{task}, or Laravel matches
     * the wildcard and looks for a task called "recurring".
     */
    public function test_the_list_is_not_swallowed_by_the_task_wildcard(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()->get('/tasks/recurring')->assertSuccessful();
    }

    public function test_a_task_can_be_set_to_repeat(): void
    {
        $this->actingAsManager();

        $task = Task::factory()->create(['project_id' => Project::factory()->create()->id]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.recurring.store', $task), [
                'frequency' => 'weekly',
                'interval' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_recurring', [
            'task_id' => $task->id,
            'frequency' => 'weekly',
            'interval' => 2,
        ]);
    }

    public function test_asking_twice_changes_the_schedule_rather_than_adding_one(): void
    {
        $this->actingAsManager();

        $schedule = $this->schedule(['frequency' => 'daily']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.recurring.store', $schedule->task_id), [
                'frequency' => 'monthly',
            ]);

        $this->assertSame(1, TaskRecurring::where('task_id', $schedule->task_id)->count());
        $this->assertSame('monthly', TaskRecurring::where('task_id', $schedule->task_id)->value('frequency'));
    }

    public function test_an_unknown_frequency_is_rejected(): void
    {
        $this->actingAsManager();

        $task = Task::factory()->create(['project_id' => Project::factory()->create()->id]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.recurring.store', $task), ['frequency' => 'fortnightly'])
            ->assertSessionHasErrors('frequency');
    }

    public function test_stopping_a_task_repeating_removes_its_schedule(): void
    {
        $this->actingAsManager();

        $schedule = $this->schedule();

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.tasks.recurring.destroy', $schedule->task_id))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_recurring', ['id' => $schedule->id]);
    }

    public function test_a_due_schedule_creates_the_next_task(): void
    {
        $schedule = $this->schedule(['next_occurrence' => today()]);

        $before = Task::count();

        $this->assertSame(1, TaskRecurringService::generateDue());
        $this->assertSame($before + 1, Task::count());
    }

    public function test_a_schedule_that_is_not_due_yet_creates_nothing(): void
    {
        $this->schedule(['next_occurrence' => today()->addWeek()]);

        $this->assertSame(0, TaskRecurringService::generateDue());
    }

    public function test_a_schedule_past_its_end_date_creates_nothing(): void
    {
        $this->schedule([
            'next_occurrence' => today(),
            'ends_at' => today()->subDay(),
        ]);

        $this->assertSame(0, TaskRecurringService::generateDue());
    }

    public function test_generating_moves_the_schedule_on_by_its_interval(): void
    {
        $schedule = $this->schedule([
            'frequency' => 'weekly',
            'interval' => 3,
            'next_occurrence' => today(),
        ]);

        TaskRecurringService::generateDue();

        $this->assertTrue(
            $schedule->fresh()->next_occurrence->isSameDay(today()->addWeeks(3)),
            'the schedule should have moved on by three weeks',
        );
    }

    public function test_the_interval_multiplies_each_frequency(): void
    {
        $from = today();

        $this->assertTrue(TaskRecurringService::advance('daily', 2, $from)->isSameDay($from->copy()->addDays(2)));
        $this->assertTrue(TaskRecurringService::advance('weekly', 2, $from)->isSameDay($from->copy()->addWeeks(2)));
        $this->assertTrue(TaskRecurringService::advance('monthly', 2, $from)->isSameDay($from->copy()->addMonths(2)));
        $this->assertTrue(TaskRecurringService::advance('yearly', 2, $from)->isSameDay($from->copy()->addYears(2)));
    }

    /**
     * An interval of zero would leave the next occurrence on today forever, so
     * the schedule would clone its task on every run.
     */
    public function test_an_interval_below_one_still_moves_the_schedule_on(): void
    {
        $from = today();

        $this->assertTrue(TaskRecurringService::advance('daily', 0, $from)->isSameDay($from->copy()->addDay()));
    }
}
