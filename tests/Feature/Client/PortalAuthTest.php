<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

class PortalAuthTest extends TenantTestCase
{
    public function test_portal_login_page_is_accessible(): void
    {
        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.client.login'));

        $response->assertStatus(200);
    }

    public function test_contact_can_login_to_portal(): void
    {
        $client = Client::factory()->create();
        $contact = ClientContact::factory()->create([
            'client_id' => $client->id,
            'email' => 'contact@test.com',
            'password' => Hash::make('password'),
            'portal_access' => true,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.login'), [
                'email' => 'contact@test.com',
                'password' => 'password',
            ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($contact, 'customer');
    }

    public function test_contact_cannot_login_without_portal_access(): void
    {
        $client = Client::factory()->create();
        ClientContact::factory()->create([
            'client_id' => $client->id,
            'email' => 'noaccess@test.com',
            'password' => Hash::make('password'),
            'portal_access' => false,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.login'), [
                'email' => 'noaccess@test.com',
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_contact_cannot_login_with_wrong_password(): void
    {
        $client = Client::factory()->create();
        ClientContact::factory()->create([
            'client_id' => $client->id,
            'email' => 'contact2@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.login'), [
                'email' => 'contact2@test.com',
                'password' => 'wrong',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_authenticated_contact_can_access_portal_dashboard(): void
    {
        $this->actingAsClient();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.dashboard'));

        $response->assertStatus(200);
    }

    public function test_contact_can_logout(): void
    {
        $this->actingAsClient();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.client.logout'));

        $response->assertRedirect();
        $this->assertGuest('customer');
    }
}
