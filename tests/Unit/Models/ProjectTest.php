<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Client;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TenantTestCase;

class ProjectTest extends TenantTestCase
{
    use DatabaseTransactions;

    public function it_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $project->client);
        $this->assertEquals($client->id, $project->client->id);
    }

    /** @test */
    public function test_it_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $project->client);
        $this->assertEquals($client->id, $project->client->id);
    }

    /** @test */
    public function test_it_has_many_tasks(): void
    {
        $project = Project::factory()->create();
        $status = TaskStatus::factory()->create();
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'task_status_id' => $status->id,
        ]);

        $this->assertCount(3, $project->tasks);
        $this->assertInstanceOf(Task::class, $project->tasks->first());
    }

    /** @test */
    public function test_it_has_many_members(): void
    {
        $project = Project::factory()->create();
        $users = User::factory()->count(2)->create();

        $project->members()->attach($users->pluck('id')->toArray(), ['project_role' => 'contributor']);

        $this->assertCount(2, $project->fresh()->members);
    }

    /** @test */
    public function test_it_has_many_time_entries(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        TimeEntry::factory()->count(4)->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'hours' => 2.5,
        ]);

        $this->assertCount(4, $project->timeEntries);
        $this->assertEquals(10.0, (float) $project->timeEntries()->sum('hours'));
    }

    /** @test */
    public function test_it_calculates_completion_percentage(): void
    {
        $project = Project::factory()->create();
        $openStatus = TaskStatus::factory()->create(['is_closed' => false]);
        $closedStatus = TaskStatus::factory()->create(['is_closed' => true]);

        // 2 closed, 2 open => 50%
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'task_status_id' => $closedStatus->id,
        ]);
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'task_status_id' => $openStatus->id,
        ]);

        $this->assertEquals(50, $project->progress);
    }

    /** @test */
    public function test_it_calculates_completion_percentage_with_no_tasks(): void
    {
        $project = Project::factory()->create();

        $this->assertEquals(0, $project->progress);
    }

    /** @test */
    public function test_it_filters_active_projects(): void
    {
        Project::factory()->count(3)->create(['is_archived' => false]);
        Project::factory()->count(2)->create(['is_archived' => true]);

        $active = Project::active()->get();

        $this->assertCount(3, $active);
        $active->each(fn ($p) => $this->assertFalse((bool) $p->is_archived));
    }

    /** @test */
    public function test_scope_archived_returns_only_archived(): void
    {
        Project::factory()->count(2)->create(['is_archived' => false]);
        Project::factory()->count(3)->create(['is_archived' => true]);

        $archived = Project::archived()->get();

        $this->assertCount(3, $archived);
        $archived->each(fn ($p) => $this->assertTrue((bool) $p->is_archived));
    }
}
