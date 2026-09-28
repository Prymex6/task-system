<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

class AuthTest extends TenantTestCase
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_manager_login_page_is_accessible(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.login'));

        $response->assertStatus(200);
    }

    public function test_manager_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), [
                'email' => 'manager@test.com',
                'password' => 'password',
            ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user, 'tenant');
    }

    public function test_manager_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), [
                'email' => 'manager@test.com',
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('tenant');
    }

    public function test_inactive_manager_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), [
                'email' => 'inactive@test.com',
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('tenant');
    }

    public function test_manager_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'tenant');

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.logout'));

        $response->assertRedirect();
        $this->assertGuest('tenant');
    }

    // ── Dashboard access ──────────────────────────────────────────────────────

    public function test_authenticated_manager_can_access_dashboard(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.dashboard'));

        // Without auth the request should redirect to login
        $response->assertRedirect();
    }
}
