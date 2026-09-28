<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Tests\TenantTestCase;

class StaffTest extends TenantTestCase
{
    public function test_staff_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.staff.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Staff/Index'));
    }

    public function test_admin_can_invite_staff_member(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.staff.store'), [
                'name' => 'Nowy Pracownik',
                'email' => 'nowy@firma.pl',
                'workspace_role' => 'member',
            ]);

        $response->assertRedirect();
    }

    public function test_admin_can_deactivate_staff_member(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);
        $staff = User::factory()->create(['workspace_role' => 'member', 'is_active' => true]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.staff.deactivate', $staff));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'is_active' => false]);
    }

    public function test_admin_can_activate_staff_member(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);
        $staff = User::factory()->create(['workspace_role' => 'member', 'is_active' => false]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.staff.activate', $staff));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'is_active' => true]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->actingAsManager(['workspace_role' => 'admin']);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.staff.destroy', $admin));

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_non_admin_cannot_access_staff_management(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.staff.store'), [
                'name' => 'Nieuprawniony',
                'email' => 'nieuprawniony@firma.pl',
                'workspace_role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    public function test_staff_profile_page_loads(): void
    {
        $user = $this->actingAsManager();
        $staff = User::factory()->create(['workspace_role' => 'member']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.hr.staff.profile', $staff));

        $response->assertStatus(200);
    }

    public function test_admin_can_change_staff_role(): void
    {
        $this->actingAsManager(['workspace_role' => 'admin']);
        $staff = User::factory()->create(['workspace_role' => 'member']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.hr.staff.role', $staff), [
                'workspace_role' => 'manager',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'workspace_role' => 'manager']);
    }
}
