<?php

namespace Tests\Feature\Manager;

use Tests\TenantTestCase;

class ReportsAndSettingsTest extends TenantTestCase
{
    /**
     * /reports used to redirect straight to the time report, which left the
     * other four with no way in. It is a hub now.
     */
    public function test_reports_index_lists_the_reports(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.reports.index'))
            ->assertSuccessful();
    }

    public function test_projects_report_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.reports.projects'));

        $response->assertStatus(200);
    }

    public function test_finance_report_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.reports.finance'));

        $response->assertStatus(200);
    }

    public function test_staff_report_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.reports.staff'));

        $response->assertStatus(200);
    }

    public function test_clients_report_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.reports.clients'));

        $response->assertStatus(200);
    }

    public function test_time_report_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.reports.time'));

        $response->assertStatus(200);
    }

    public function test_settings_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.settings.index'));

        $response->assertStatus(200);
    }

    public function test_audit_log_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->get(route('tenant.manager.settings.audit-log'));

        $response->assertStatus(200);
    }
}
