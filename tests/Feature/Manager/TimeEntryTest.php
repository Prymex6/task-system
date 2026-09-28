<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TimeEntry;
use Tests\TenantTestCase;

class TimeEntryTest extends TenantTestCase
{
    public function test_time_entries_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.time.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Time/Index'));
    }

    public function test_manager_can_log_time(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.time.store'), [
                'project_id' => $project->id,
                'hours' => 2.5,
                'date' => now()->toDateString(),
                'description' => 'Praca nad modułem płatności',
                'is_billable' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'hours' => 2.5,
        ]);
    }

    public function test_manager_can_log_time_for_task(): void
    {
        $user = $this->actingAsManager();
        $task = Task::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.time.store'), [
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'hours' => 1.0,
                'date' => now()->toDateString(),
                'description' => 'Implementacja feature',
                'is_billable' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'task_id' => $task->id,
        ]);
    }

    public function test_manager_can_update_time_entry(): void
    {
        $user = $this->actingAsManager();
        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => Project::factory()->create()->id,
            'hours' => 1.0,
            'date' => now()->toDateString(),
            'description' => 'Stary opis',
            'is_billable' => false,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.time.update', $entry), [
                'hours' => 3.0,
                'date' => now()->toDateString(),
                'description' => 'Zaktualizowany opis',
                'is_billable' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id, 'hours' => 3.0]);
    }

    public function test_manager_can_delete_time_entry(): void
    {
        $user = $this->actingAsManager();
        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => Project::factory()->create()->id,
            'hours' => 1.0,
            'date' => now()->toDateString(),
            'description' => 'Do usunięcia',
            'is_billable' => false,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.time.destroy', $entry));

        $response->assertRedirect();
        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_time_entry_requires_hours(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.time.store'), [
                'project_id' => $project->id,
                'date' => now()->toDateString(),
                'description' => 'Brak godzin',
            ]);

        $response->assertSessionHasErrors('hours');
    }

    public function test_time_entries_can_be_filtered_by_project(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.time.index', ['project_id' => $project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('entries'));
    }
}
