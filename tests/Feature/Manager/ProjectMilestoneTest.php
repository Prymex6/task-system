<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMilestone;
use App\Models\Tenant\ProjectTemplate;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Milestones and the templates projects are started from.
 *
 * Both controllers were stubs, so a project could neither be given a
 * checkpoint nor created from a blueprint.
 */
class ProjectMilestoneTest extends TenantTestCase
{
    private function project(User $user): Project
    {
        $project = Project::factory()->create(['created_by' => $user->id]);
        $project->members()->attach($user->id, ['project_role' => 'project_manager', 'added_by' => $user->id]);

        return $project;
    }

    public function test_manager_can_add_a_milestone(): void
    {
        $project = $this->project($this->actingAsManager());

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.milestones.store', $project), [
                'name' => 'Wersja 1.0',
                'due_date' => '2026-12-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'name' => 'Wersja 1.0',
        ]);
    }

    public function test_milestone_requires_a_name(): void
    {
        $project = $this->project($this->actingAsManager());

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.milestones.store', $project), ['due_date' => '2026-12-01'])
            ->assertSessionHasErrors('name');
    }

    public function test_completing_a_milestone_stamps_the_time_and_reopening_clears_it(): void
    {
        $project = $this->project($this->actingAsManager());
        $milestone = ProjectMilestone::create(['project_id' => $project->id, 'name' => 'Etap']);

        $route = route('tenant.manager.projects.milestones.update', [$project, $milestone]);

        $this->withoutTenantMiddleware()->put($route, ['name' => 'Etap', 'is_completed' => true]);
        $this->assertNotNull($milestone->fresh()->completed_at);

        $this->withoutTenantMiddleware()->put($route, ['name' => 'Etap', 'is_completed' => false]);
        $this->assertNull($milestone->fresh()->completed_at);
    }

    public function test_a_milestone_from_another_project_is_not_found(): void
    {
        $user = $this->actingAsManager();
        $project = $this->project($user);
        $other = $this->project($user);
        $milestone = ProjectMilestone::create(['project_id' => $other->id, 'name' => 'Obcy']);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.projects.milestones.destroy', [$project, $milestone]))
            ->assertNotFound();
    }

    public function test_a_plain_member_cannot_add_a_milestone(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);
        $project = Project::factory()->create();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.milestones.store', $project), ['name' => 'Etap'])
            ->assertForbidden();
    }

    public function test_manager_can_save_a_template_with_its_tasks(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.project-templates.store'), [
                'name' => 'Wdrożenie sklepu',
                'description' => 'Standardowy przebieg',
                'tasks' => [
                    ['title' => 'Analiza', 'priority' => 'high'],
                    ['title' => 'Konfiguracja'],
                ],
            ])
            ->assertRedirect();

        $template = ProjectTemplate::firstWhere('name', 'Wdrożenie sklepu');

        $this->assertNotNull($template);
        $this->assertCount(2, $template->tasks);
        $this->assertSame('high', $template->tasks->first()->priority);
    }

    public function test_creating_a_project_from_a_template_copies_its_tasks(): void
    {
        $user = $this->actingAsManager();
        TaskStatus::create(['name' => 'Do zrobienia', 'is_default' => true, 'order' => 1]);

        $template = ProjectTemplate::create(['name' => 'Szablon', 'created_by' => $user->id]);
        $template->tasks()->createMany([
            ['title' => 'Pierwsze', 'priority' => 'high', 'order' => 0],
            ['title' => 'Drugie', 'priority' => 'low', 'order' => 1],
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.project-templates.use', $template), ['name' => 'Projekt z szablonu'])
            ->assertRedirect();

        $project = Project::firstWhere('name', 'Projekt z szablonu');

        $this->assertNotNull($project);
        $this->assertSame(['Pierwsze', 'Drugie'], $project->tasks()->orderBy('order')->pluck('title')->all());
        $this->assertTrue($project->members->contains($user->id));
    }

    public function test_tasks_copied_from_a_template_do_not_follow_later_edits(): void
    {
        $user = $this->actingAsManager();
        $template = ProjectTemplate::create(['name' => 'Szablon', 'created_by' => $user->id]);
        $template->tasks()->create(['title' => 'Oryginał', 'order' => 0]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.project-templates.use', $template), ['name' => 'Projekt']);

        $template->tasks()->first()->update(['title' => 'Zmienione po fakcie']);

        $this->assertSame('Oryginał', Project::firstWhere('name', 'Projekt')->tasks()->first()->title);
    }

    public function test_deleting_a_template_takes_its_tasks_with_it(): void
    {
        $user = $this->actingAsManager();
        $template = ProjectTemplate::create(['name' => 'Szablon', 'created_by' => $user->id]);
        $template->tasks()->create(['title' => 'Zadanie', 'order' => 0]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.project-templates.destroy', $template))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('project_template_tasks', ['project_template_id' => $template->id]);
    }
}
