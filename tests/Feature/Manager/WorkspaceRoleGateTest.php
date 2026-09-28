<?php

namespace Tests\Feature\Manager;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantTestCase;

/**
 * The role gate on the manager panel.
 *
 * Every manager route used to sit behind `auth:tenant` and nothing else, and
 * `workspace.role` was registered as an alias but applied to no route at all.
 * A guest could therefore open the finance reports, the audit log and the
 * role-permission screen. These tests are what stop that coming back.
 */
class WorkspaceRoleGateTest extends TenantTestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function adminOnlyRoutes(): array
    {
        return [
            'settings hub' => ['tenant.manager.settings.index'],
            'company details' => ['tenant.manager.settings.company'],
            'finance settings' => ['tenant.manager.settings.finance'],
            'integrations' => ['tenant.manager.settings.integrations.index'],
            'e-mail templates' => ['tenant.manager.settings.email-templates.index'],
            'custom fields' => ['tenant.manager.settings.custom-fields.index'],
            'audit log' => ['tenant.manager.settings.audit-log'],
            'billing' => ['tenant.manager.settings.billing'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function managerRoutes(): array
    {
        return [
            'finance overview' => ['tenant.manager.finance.overview'],
            'invoices' => ['tenant.manager.invoices.index'],
            'credit notes' => ['tenant.manager.credit-notes.index'],
            'reports' => ['tenant.manager.reports.index'],
            'leave' => ['tenant.manager.hr.leave'],
        ];
    }

    #[DataProvider('adminOnlyRoutes')]
    public function test_a_member_cannot_reach_an_admin_only_screen(string $route): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);

        $this->withoutTenantMiddleware()->get(route($route))->assertForbidden();
    }

    #[DataProvider('adminOnlyRoutes')]
    public function test_a_guest_cannot_reach_an_admin_only_screen(string $route): void
    {
        $this->actingAsManager(['workspace_role' => 'guest']);

        $this->withoutTenantMiddleware()->get(route($route))->assertForbidden();
    }

    #[DataProvider('managerRoutes')]
    public function test_a_guest_cannot_reach_a_manager_screen(string $route): void
    {
        $this->actingAsManager(['workspace_role' => 'guest']);

        $this->withoutTenantMiddleware()->get(route($route))->assertForbidden();
    }

    #[DataProvider('managerRoutes')]
    public function test_a_manager_can_reach_a_manager_screen(string $route): void
    {
        $this->actingAsManager(['workspace_role' => 'manager']);

        $this->withoutTenantMiddleware()->get(route($route))->assertSuccessful();
    }

    #[DataProvider('adminOnlyRoutes')]
    public function test_an_admin_can_reach_an_admin_only_screen(string $route): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $this->withoutTenantMiddleware()->get(route($route))->assertSuccessful();
    }

    /**
     * The owner is never listed on a route, and must still get through.
     */
    #[DataProvider('adminOnlyRoutes')]
    public function test_the_owner_passes_without_being_named(string $route): void
    {
        $this->actingAsManager(['workspace_role' => 'owner']);

        $this->withoutTenantMiddleware()->get(route($route))->assertSuccessful();
    }
}
