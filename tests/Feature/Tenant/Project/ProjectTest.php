<?php

namespace Tests\Feature\Tenant\Project;

use App\Models\Tenant\Client;
use App\Models\Tenant\Project;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

class ProjectTest extends TenantTestCase
{
    /** @test */
    public function test_user_can_list_projects(): void
    {
        $this->actingAsManager();
        Project::factory()->count(3)->create(['is_archived' => false]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Projects/Index')
            ->has('projects')
        );
    }

    /** @test */
    public function test_user_can_view_project(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create(['name' => 'Test Project Alpha']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.show', $project));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Projects/Show')
            ->where('project.id', $project->id)
        );
    }

    /** @test */
    public function test_admin_can_create_project(): void
    {
        $user = $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.store'), [
                'name' => 'Brand New Project',
                'client_id' => $client->id,
                'status' => 'planning',
                'visibility' => 'team',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Brand New Project',
            'client_id' => $client->id,
        ]);
    }

    /** @test */
    public function test_project_creation_requires_name(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.store'), [
                'status' => 'planning',
                'visibility' => 'team',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function test_project_creation_requires_status(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.store'), [
                'name' => 'No Status Project',
                'visibility' => 'team',
            ]);

        $response->assertSessionHasErrors('status');
    }

    /** @test */
    public function test_project_can_be_assigned_to_members(): void
    {
        $user = $this->actingAsManager();
        $member = User::factory()->create(['workspace_role' => 'member', 'is_active' => true]);
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.store'), [
                'name' => 'Project With Members',
                'client_id' => $client->id,
                'status' => 'in_progress',
                'visibility' => 'team',
                'members' => [
                    ['user_id' => $member->id, 'project_role' => 'contributor'],
                ],
            ]);

        $response->assertRedirect();

        $project = Project::where('name', 'Project With Members')->first();
        $this->assertNotNull($project);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'project_role' => 'contributor',
        ]);
    }

    /** @test */
    public function test_admin_can_update_project(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create([
            'name' => 'Old Name',
            'status' => 'planning',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.projects.update', $project), [
                'name' => 'Updated Name',
                'client_id' => $project->client_id,
                'status' => 'in_progress',
                'visibility' => 'team',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function test_admin_can_delete_project(): void
    {
        $user = $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.projects.destroy', $project));

        $response->assertRedirect();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    /** @test */
    public function test_member_without_permission_cannot_delete_project(): void
    {
        $member = User::factory()->create(['workspace_role' => 'member', 'is_active' => true]);
        $this->actingAs($member, 'tenant');

        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.projects.destroy', $project));

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    /** @test */
    public function test_project_create_page_lists_clients(): void
    {
        $this->actingAsManager();
        Client::factory()->count(2)->create(['is_active' => true]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Manager/Projects/Form')
            ->has('clients')
        );
    }
}
