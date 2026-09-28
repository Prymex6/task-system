<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use Tests\TenantTestCase;

class ClientTest extends TenantTestCase
{
    public function test_clients_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.clients.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/CRM/Clients/Index'));
    }

    public function test_manager_can_create_client(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.clients.store'), [
                'name' => 'Jan Kowalski',
                'company_name' => 'Kowalski Sp. z o.o.',
                'email' => 'jan@kowalski.pl',
                'phone' => '+48 123 456 789',
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', [
            'email' => 'jan@kowalski.pl',
            'company_name' => 'Kowalski Sp. z o.o.',
        ]);
    }

    public function test_client_creation_requires_name(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.clients.store'), [
                'email' => 'test@test.pl',
                'currency' => 'PLN',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_manager_can_update_client(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create(['company_name' => 'Stara firma']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.clients.update', $client), [
                'name' => $client->name,
                'company_name' => 'Nowa firma Sp. z o.o.',
                'email' => $client->email,
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'company_name' => 'Nowa firma Sp. z o.o.']);
    }

    public function test_manager_can_delete_client(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.clients.destroy', $client));

        $response->assertRedirect();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_client_show_page_loads(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.clients.show', $client));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('client.id', $client->id));
    }

    public function test_clients_list_filtered_by_search(): void
    {
        $this->actingAsManager();
        Client::factory()->create(['company_name' => 'Firma Alpha']);
        Client::factory()->create(['company_name' => 'Firma Beta']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.clients.index', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('clients'));
    }

    public function test_manager_can_deactivate_client(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create(['is_active' => true]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.clients.update', $client), [
                'name' => $client->name,
                'email' => $client->email,
                'currency' => 'PLN',
                'is_active' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'is_active' => false]);
    }

    public function test_client_contact_can_be_added(): void
    {
        $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.clients.contacts.add', $client), [
                'name' => 'Piotr Nowak',
                'email' => 'piotr@nowak.pl',
                'position' => 'Dyrektor',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('client_contacts', [
            'client_id' => $client->id,
            'email' => 'piotr@nowak.pl',
        ]);
    }
}
