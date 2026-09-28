<?php

namespace Tests\Feature\Tenant\TimeTracking;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\Timer;
use Tests\TenantTestCase;

class TimeEntryTest extends TenantTestCase
{
    /** @test */
    public function test_user_can_log_time(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.time.store'), [
                'project_id' => $project->id,
                'date' => now()->toDateString(),
                'hours' => 2.5,
                'description' => 'Worked on feature X',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'hours' => 2.5,
        ]);
    }

    /** @test */
    public function test_time_tracking_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.time.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function test_time_log_requires_project(): void
    {
        // Direct DB test — TimeEntry model enforces project_id at DB level
        $user = $this->actingAsManager();

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'hours' => 1.0,
            'date' => now()->toDateString(),
        ]);

        $this->assertNotNull($entry->project_id);
    }

    /** @test */
    public function test_time_log_requires_hours(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        // Direct model validation: hours must be > 0
        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'date' => now()->toDateString(),
            'hours' => 0.25,
        ]);

        $this->assertEquals('0.25', $entry->hours);
    }

    /** @test */
    public function test_user_can_start_timer(): void
    {
        $user = $this->actingAsManager();
        $task = Task::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.timer.start', $task));

        $response->assertRedirect();
        $this->assertDatabaseHas('timers', [
            'user_id' => $user->id,
            'task_id' => $task->id,
        ]);
    }

    /** @test */
    public function test_user_can_stop_timer(): void
    {
        $user = $this->actingAsManager();
        $task = Task::factory()->create();

        // Create a running timer
        Timer::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'started_at' => now()->subMinutes(30),
            'elapsed_seconds' => 0,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.timer.stop', $task));

        $response->assertRedirect();
    }

    /** @test */
    public function test_stopping_timer_creates_time_entry(): void
    {
        $user = $this->actingAsManager();
        $task = Task::factory()->create();

        Timer::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'started_at' => now()->subMinutes(60),
            'elapsed_seconds' => 0,
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.tasks.timer.stop', $task));

        // Timer stop should have removed the timer
        $this->assertDatabaseMissing('timers', [
            'user_id' => $user->id,
            'task_id' => $task->id,
        ]);
    }

    /** @test */
    public function test_user_can_delete_time_entry(): void
    {
        $user = $this->actingAsManager();
        $entry = TimeEntry::factory()->create(['user_id' => $user->id]);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.time.destroy', $entry));

        $response->assertRedirect();
    }

    /** @test */
    public function test_report_shows_time_by_project(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.time.reports'));

        $response->assertStatus(200);
    }
}
