<?php

namespace Tests\Unit\Services;

use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\User;
use App\Services\ProjectService;
use Tests\TenantTestCase;

class ProjectServiceTest extends TenantTestCase
{
    private function makeUser(string $role = 'admin'): User
    {
        return User::factory()->create(['workspace_role' => $role]);
    }

    public function test_create_creates_project_with_creator(): void
    {
        $user = $this->makeUser();

        $project = ProjectService::create([
            'name' => 'Projekt testowy serwisu',
            'status' => 'active',
        ], $user);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals($user->id, $project->created_by);
        $this->assertDatabaseHas('projects', ['name' => 'Projekt testowy serwisu']);
    }

    public function test_create_adds_creator_as_project_manager(): void
    {
        $user = $this->makeUser();

        $project = ProjectService::create([
            'name' => 'Projekt z managerem',
            'status' => 'active',
        ], $user);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_role' => 'project_manager',
        ]);
    }

    public function test_create_adds_additional_members(): void
    {
        $creator = $this->makeUser();
        $member = $this->makeUser('member');

        $project = ProjectService::create([
            'name' => 'Projekt z członkami',
            'status' => 'active',
            'members' => [
                ['id' => $member->id, 'role' => 'contributor'],
            ],
        ], $creator);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_update_changes_project_fields(): void
    {
        $user = $this->makeUser();
        $project = Project::factory()->create(['name' => 'Stary projekt']);

        $updated = ProjectService::update($project, ['name' => 'Nowy projekt']);

        $this->assertEquals('Nowy projekt', $updated->name);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Nowy projekt']);
    }

    public function test_archive_sets_is_archived_true(): void
    {
        $project = Project::factory()->create(['is_archived' => false]);

        ProjectService::archive($project);

        $this->assertTrue((bool) $project->fresh()->is_archived);
    }

    public function test_unarchive_sets_is_archived_false(): void
    {
        $project = Project::factory()->create(['is_archived' => true]);

        ProjectService::unarchive($project);

        $this->assertFalse((bool) $project->fresh()->is_archived);
    }

    public function test_delete_removes_project(): void
    {
        $project = Project::factory()->create();
        $id = $project->id;

        ProjectService::delete($project);

        $this->assertDatabaseMissing('projects', ['id' => $id]);
    }

    public function test_add_member_attaches_user_to_project(): void
    {
        $user = $this->makeUser();
        $project = Project::factory()->create();
        $staff = $this->makeUser('member');

        ProjectService::addMember($project, $staff->id, 'contributor', $user->id);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $staff->id,
            'project_role' => 'contributor',
        ]);
    }

    public function test_add_member_updates_role_if_already_member(): void
    {
        $user = $this->makeUser();
        $project = Project::factory()->create();
        $staff = $this->makeUser('member');

        $project->members()->attach($staff->id, ['project_role' => 'contributor', 'added_by' => $user->id]);
        ProjectService::addMember($project, $staff->id, 'project_manager', $user->id);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $staff->id,
            'project_role' => 'project_manager',
        ]);
    }

    public function test_remove_member_detaches_user(): void
    {
        $user = $this->makeUser();
        $project = Project::factory()->create();
        $staff = $this->makeUser('member');
        $project->members()->attach($staff->id, ['project_role' => 'contributor', 'added_by' => $user->id]);

        ProjectService::removeMember($project, $staff->id);

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $staff->id,
        ]);
    }

    public function test_can_access_returns_true_for_admin(): void
    {
        $admin = $this->makeUser('admin');
        $project = Project::factory()->create();

        $result = ProjectService::canAccess($project, $admin);

        $this->assertTrue($result);
    }

    public function test_can_access_returns_false_for_non_member(): void
    {
        $staff = $this->makeUser('member');
        $project = Project::factory()->create();

        $result = ProjectService::canAccess($project, $staff);

        $this->assertFalse($result);
    }

    public function test_can_access_returns_true_for_project_member(): void
    {
        $creator = $this->makeUser();
        $staff = $this->makeUser('member');
        $project = ProjectService::create(['name' => 'Projekt', 'status' => 'active'], $creator);
        $project->members()->attach($staff->id, ['project_role' => 'contributor', 'added_by' => $creator->id]);

        $result = ProjectService::canAccess($project, $staff);

        $this->assertTrue($result);
    }

    public function test_duplicate_creates_copy_with_kopia_suffix(): void
    {
        $user = $this->makeUser();
        $project = Project::factory()->create(['name' => 'Projekt oryginalny']);

        $copy = ProjectService::duplicate($project, $user);

        $this->assertDatabaseHas('projects', ['name' => 'Projekt oryginalny (kopia)']);
        $this->assertEquals($user->id, $copy->created_by);
    }

    public function test_duplicate_copies_tasks_to_new_project(): void
    {
        $user = $this->makeUser();
        $project = Project::factory()->create();
        Task::factory()->count(3)->create(['project_id' => $project->id]);

        $copy = ProjectService::duplicate($project, $user);

        $this->assertCount(3, $copy->tasks()->get());
    }
}
