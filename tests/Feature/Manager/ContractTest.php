<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Contract;
use Tests\TenantTestCase;

class ContractTest extends TenantTestCase
{
    private function makeContract(array $attrs = []): Contract
    {
        $client = Client::factory()->create();

        return Contract::create(array_merge([
            'client_id' => $client->id,
            'subject' => 'Umowa testowa',
            'status' => 'draft',
            'currency' => 'PLN',
            'value' => 10000.00,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ], $attrs));
    }

    public function test_contracts_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.contracts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Contracts/Index'));
    }

    public function test_manager_can_create_contract(): void
    {
        $user = $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.contracts.store'), [
                'client_id' => $client->id,
                'subject' => 'Umowa serwisowa 2026',
                'status' => 'draft',
                'currency' => 'PLN',
                'value' => 24000.00,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', [
            'client_id' => $client->id,
            'subject' => 'Umowa serwisowa 2026',
        ]);
    }

    public function test_manager_can_update_contract(): void
    {
        $this->actingAsManager();
        $contract = $this->makeContract(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.contracts.update', $contract), [
                'client_id' => $contract->client_id,
                'subject' => 'Zaktualizowana umowa',
                'status' => 'draft',
                'currency' => 'PLN',
                'value' => 15000.00,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'subject' => 'Zaktualizowana umowa']);
    }

    public function test_manager_can_activate_contract(): void
    {
        $this->actingAsManager();
        $contract = $this->makeContract(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.contracts.activate', $contract));

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'status' => 'active']);
    }

    public function test_manager_can_terminate_contract(): void
    {
        $this->actingAsManager();
        $contract = $this->makeContract(['status' => 'active']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.contracts.terminate', $contract));

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'status' => 'terminated']);
    }

    public function test_manager_can_delete_draft_contract(): void
    {
        $this->actingAsManager();
        $contract = $this->makeContract(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.contracts.destroy', $contract));

        $response->assertRedirect();
        $this->assertDatabaseMissing('contracts', ['id' => $contract->id]);
    }

    public function test_contract_show_page_loads(): void
    {
        $this->actingAsManager();
        $contract = $this->makeContract();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.contracts.show', $contract));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('contract.id', $contract->id));
    }

    public function test_contract_requires_client(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.contracts.store'), [
                'subject' => 'Umowa bez klienta',
                'status' => 'draft',
                'currency' => 'PLN',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ]);

        $response->assertSessionHasErrors('client_id');
    }
}
