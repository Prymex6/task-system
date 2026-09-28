<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TenantTestCase;

class UserTest extends TenantTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_it_has_many_tasks(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $task->assignees()->attach($user->id);

        $this->assertTrue($user->assignedTasks->contains($task->id));
    }

    /** @test */
    public function test_it_has_many_time_entries(): void
    {
        $user = User::factory()->create();

        TimeEntry::factory()->count(3)->create([
            'user_id' => $user->id,
            'hours' => 2.0,
        ]);

        $this->assertCount(3, $user->timeEntries);
    }

    /** @test */
    public function test_it_has_workspace_role(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['workspace_role' => 'member']);

        $this->assertEquals('admin', $admin->workspace_role);
        $this->assertEquals('member', $member->workspace_role);
    }

    /** @test */
    public function test_it_checks_admin_status(): void
    {
        $owner = User::factory()->create(['workspace_role' => 'owner']);
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['workspace_role' => 'member']);

        $this->assertTrue($owner->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());
    }

    /** @test */
    public function test_it_hides_password(): void
    {
        $user = User::factory()->create();

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function test_owner_role_is_manager(): void
    {
        $owner = User::factory()->create(['workspace_role' => 'owner']);

        $this->assertTrue($owner->isOwner());
        $this->assertTrue($owner->isManager());
    }

    /** @test */
    public function test_member_cannot_access_admin_resources(): void
    {
        $member = User::factory()->create(['workspace_role' => 'member']);

        $this->assertFalse($member->isAdmin());
        $this->assertFalse($member->isOwner());
    }

    /** @test */
    public function test_admin_can_access_all_projects(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->assertTrue($admin->canAccessProject($project));
    }

    /** @test */
    public function test_member_cannot_access_unassigned_project(): void
    {
        $member = User::factory()->create(['workspace_role' => 'member']);
        $project = Project::factory()->create();

        $this->assertFalse($member->canAccessProject($project));
    }

    /** @test */
    public function test_scope_active_returns_only_active_users(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->inactive()->count(2)->create();

        $active = User::active()->get();

        $this->assertGreaterThanOrEqual(3, $active->count());
        $active->each(fn ($u) => $this->assertTrue((bool) $u->is_active));
    }
}
