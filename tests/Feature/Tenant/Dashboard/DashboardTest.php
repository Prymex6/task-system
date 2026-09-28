<?php

namespace Tests\Feature\Tenant\Dashboard;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Project;
use Tests\TenantTestCase;

class DashboardTest extends TenantTestCase
{
    /** @test */
    public function test_authenticated_user_can_view_dashboard(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Dashboard'));
    }

    /** @test */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertRedirect();
    }

    /** @test */
    public function test_dashboard_shows_project_stats(): void
    {
        $user = $this->actingAsManager();

        Project::factory()->count(2)->create(['status' => 'in_progress', 'is_archived' => false]);
        Project::factory()->create(['status' => 'completed', 'is_archived' => false]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('stats.projects')
            ->where('stats.projects.active', fn ($v) => $v >= 2)
        );
    }

    /** @test */
    public function test_dashboard_shows_task_stats(): void
    {
        $user = $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('stats.tasks')
            ->has('stats.tasks.my_open')
            ->has('stats.tasks.overdue')
            ->has('stats.tasks.due_today')
        );
    }

    /** @test */
    public function test_dashboard_shows_recent_activity(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('activityChart')
            ->has('upcomingTasks')
            ->has('myProjects')
        );
    }

    /** @test */
    public function test_dashboard_has_finance_stats(): void
    {
        $this->actingAsManager();

        Invoice::factory()->sent()->create(['total' => 500.00]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('stats.finance')
            ->has('stats.finance.pending_invoices')
        );
    }

    /** @test */
    public function test_dashboard_has_ticket_stats(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('stats.tickets')
            ->has('stats.tickets.open')
        );
    }
}
