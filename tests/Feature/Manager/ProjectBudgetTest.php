<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\BudgetEntry;
use App\Models\Tenant\Project;
use Tests\TenantTestCase;

/**
 * The budget screen of a project.
 *
 * It could not open and it could not save. getSummary() was declared to take
 * a Project and the controller handed it an integer, so the page died on a
 * type error before rendering; the service then summed a column called
 * "spent" on a table whose columns were planned_amount and actual_amount.
 * Adding an entry went through addEntry($id, $array) against a signature of
 * (int, string, float, float). Nothing here had ever run.
 *
 * The screen itself was finished: it posts a type, an amount, a description
 * and a date, and lists entries as money in and money out. That is what the
 * table holds now.
 */
class ProjectBudgetTest extends TenantTestCase
{
    public function test_the_budget_page_opens(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create(['budget' => 10000]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.budget', $project->id))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Manager/Projects/Budget')
                ->has('summary.entries')
                ->where('summary.budget', 10000)
            );
    }

    public function test_an_expense_is_recorded_against_the_project(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create(['budget' => 10000]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.budget.store', $project->id), [
                'type' => 'expense',
                'amount' => 250.50,
                'description' => 'Server hosting',
                'date' => '2026-05-04',
                'category' => 'infrastructure',
            ])
            ->assertRedirect();

        $entry = BudgetEntry::where('project_id', $project->id)->sole();

        $this->assertSame('expense', $entry->type);
        $this->assertSame('250.50', (string) $entry->amount);
        $this->assertSame('Server hosting', $entry->description);
        $this->assertSame('2026-05-04', $entry->entry_date->toDateString());
    }

    public function test_the_summary_separates_money_in_from_money_out(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create(['budget' => 1000]);

        foreach ([['income', 400], ['expense', 250], ['expense', 100]] as [$type, $amount]) {
            $this->withoutTenantMiddleware()->post(
                route('tenant.manager.projects.budget.store', $project->id),
                ['type' => $type, 'amount' => $amount]
            );
        }

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.projects.budget', $project->id))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('summary.income', 400)
                ->where('summary.expenses', 350)
                ->where('summary.remaining', 650)
                ->where('summary.used_percent', 35)
                ->has('summary.entries', 3)
            );
    }

    public function test_an_entry_needs_a_type_and_an_amount(): void
    {
        $this->actingAsManager();
        $project = Project::factory()->create();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.projects.budget.store', $project->id), [])
            ->assertSessionHasErrors(['type', 'amount']);
    }
}
