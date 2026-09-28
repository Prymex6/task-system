<?php

namespace Tests\Feature\Tenant\Auth;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

class LoginTest extends TenantTestCase
{
    /** @test */
    public function test_manager_can_view_login_page(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.login'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Auth/Login'));
    }

    /** @test */
    public function test_manager_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test-login@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), [
                'email' => 'test-login@example.com',
                'password' => 'password',
            ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user, 'tenant');
    }

    /** @test */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'wrong-pw@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), [
                'email' => 'wrong-pw@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('tenant');
    }

    /** @test */
    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'tenant');

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.login'));

        $response->assertRedirect(route('tenant.manager.dashboard'));
    }

    /** @test */
    public function test_manager_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'tenant');

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.logout'));

        $response->assertRedirect(route('tenant.login'));
        $this->assertGuest('tenant');
    }

    /** @test */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), [
                'email' => 'inactive@example.com',
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('tenant');
    }

    /** @test */
    public function test_login_requires_email(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), ['password' => 'password']);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function test_login_requires_password(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.login'), ['email' => 'foo@bar.com']);

        $response->assertSessionHasErrors('password');
    }
}
