<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Proposal;
use Tests\TenantTestCase;

class ProposalTest extends TenantTestCase
{
    private function makeProposal(array $attrs = []): Proposal
    {
        $client = Client::factory()->create();

        return Proposal::create(array_merge([
            'client_id' => $client->id,
            'number' => 'OFF/TEST/' . rand(1, 9999),
            'title' => 'Oferta testowa',
            'status' => 'draft',
            'currency' => 'PLN',
            'total_net' => 1000.00,
            'total_tax' => 230.00,
            'total_gross' => 1230.00,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ], $attrs));
    }

    public function test_proposals_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.proposals.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Proposals/Index'));
    }

    public function test_manager_can_create_proposal(): void
    {
        $user = $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.proposals.store'), [
                'client_id' => $client->id,
                'title' => 'Oferta na system CRM',
                'number' => 'OFF/2026/001',
                'status' => 'draft',
                'currency' => 'PLN',
                'total_net' => 5000.00,
                'total_tax' => 1150.00,
                'total_gross' => 6150.00,
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('proposals', ['number' => 'OFF/2026/001', 'client_id' => $client->id]);
    }

    public function test_manager_can_update_proposal(): void
    {
        $this->actingAsManager();
        $proposal = $this->makeProposal(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.proposals.update', $proposal), [
                'client_id' => $proposal->client_id,
                'title' => 'Zaktualizowana oferta',
                'number' => $proposal->number,
                'status' => 'draft',
                'currency' => 'PLN',
                'total_net' => 8000.00,
                'total_tax' => 1840.00,
                'total_gross' => 9840.00,
                'issue_date' => $proposal->issue_date->toDateString(),
                'valid_until' => $proposal->valid_until->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'title' => 'Zaktualizowana oferta']);
    }

    public function test_manager_can_send_proposal(): void
    {
        $this->actingAsManager();
        $proposal = $this->makeProposal(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.proposals.send', $proposal));

        $response->assertRedirect();
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'status' => 'sent']);
    }

    public function test_manager_can_delete_draft_proposal(): void
    {
        $this->actingAsManager();
        $proposal = $this->makeProposal(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.proposals.destroy', $proposal));

        $response->assertRedirect();
        $this->assertDatabaseMissing('proposals', ['id' => $proposal->id]);
    }

    public function test_proposal_show_page_loads(): void
    {
        $this->actingAsManager();
        $proposal = $this->makeProposal();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.proposals.show', $proposal));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('proposal.id', $proposal->id));
    }

    public function test_accepted_proposal_can_be_converted_to_invoice(): void
    {
        $this->actingAsManager();
        $proposal = $this->makeProposal(['status' => 'accepted']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.proposals.convert', $proposal));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['client_id' => $proposal->client_id]);
    }

    public function test_proposal_is_expired_when_past_valid_until(): void
    {
        $proposal = $this->makeProposal([
            'status' => 'sent',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($proposal->isExpired());
    }

    public function test_accepted_proposal_is_not_expired(): void
    {
        $proposal = $this->makeProposal([
            'status' => 'accepted',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($proposal->isExpired());
    }
}
