<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Project;
use Tests\TenantTestCase;

class ProjectTest extends TenantTestCase
{
    public function test_projects_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Projects/Index'));
    }

    public function test_manager_can_create_project(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.store'), [
                'name' => 'Nowy projekt testowy',
                'client_id' => $client->id,
                'visibility' => 'team',
                'status' => 'planning',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Nowy projekt testowy']);
    }

    public function test_manager_can_update_project(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create(['name' => 'Stara nazwa', 'status' => 'planning']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.projects.update', $project), [
                'name' => 'Nowa nazwa',
                'client_id' => $project->client_id,
                'visibility' => 'team',
                'status' => 'in_progress',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Nowa nazwa']);
    }

    public function test_manager_can_archive_project(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create(['is_archived' => false]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.archive', $project));

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_archived' => true]);
    }

    public function test_manager_can_delete_project(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.projects.destroy', $project));

        $response->assertRedirect();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_show_returns_correct_project(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create(['name' => 'Projekt do podglądu']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.show', $project));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Manager/Projects/Show')
                ->where('project.id', $project->id)
        );
    }

    public function test_project_create_page_passes_clients(): void
    {
        $this->actingAsManager();
        Client::factory()->count(3)->create();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Manager/Projects/Form')
                ->has('clients')
        );
    }
}
