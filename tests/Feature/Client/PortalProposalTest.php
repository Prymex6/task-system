<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Client;
use App\Models\Tenant\Proposal;
use Tests\TenantTestCase;

class PortalProposalTest extends TenantTestCase
{
    private function makeProposal(int $clientId, array $attrs = []): Proposal
    {
        return Proposal::create(array_merge([
            'client_id' => $clientId,
            'number' => 'OFF/TEST/' . rand(1, 9999),
            'title' => 'Testowa oferta',
            'status' => 'sent',
            'currency' => 'PLN',
            'total_net' => 1000.00,
            'total_tax' => 230.00,
            'total_gross' => 1230.00,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ], $attrs));
    }

    public function test_client_can_view_proposals_list(): void
    {
        $contact = $this->actingAsClient();
        $this->makeProposal($contact->client_id);
        $this->makeProposal($contact->client_id);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.proposals'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Client/Proposals/Index')
            ->has('proposals.data', 2)
        );
    }

    public function test_client_can_view_proposal_detail(): void
    {
        $contact = $this->actingAsClient();
        $proposal = $this->makeProposal($contact->client_id);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.proposals.show', $proposal));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Client/Proposals/Show')
            ->where('proposal.id', $proposal->id)
        );
    }

    public function test_viewing_proposal_marks_it_as_viewed(): void
    {
        $contact = $this->actingAsClient();
        $proposal = $this->makeProposal($contact->client_id, ['status' => 'sent']);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.proposals.show', $proposal));

        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'status' => 'viewed']);
        $this->assertDatabaseHas('proposal_views', ['proposal_id' => $proposal->id]);
    }

    public function test_client_can_accept_proposal(): void
    {
        $contact = $this->actingAsClient();
        $proposal = $this->makeProposal($contact->client_id, ['status' => 'viewed']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.portal.proposals.accept', $proposal));

        $response->assertRedirect();
        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'accepted',
        ]);
        $this->assertNotNull(Proposal::find($proposal->id)->accepted_at);
    }

    public function test_client_can_reject_proposal_with_reason(): void
    {
        $contact = $this->actingAsClient();
        $proposal = $this->makeProposal($contact->client_id, ['status' => 'sent']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.portal.proposals.reject', $proposal), [
                'reason' => 'Cena jest zbyt wysoka.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'rejected',
            'rejection_reason' => 'Cena jest zbyt wysoka.',
        ]);
    }

    public function test_client_cannot_accept_already_accepted_proposal(): void
    {
        $contact = $this->actingAsClient();
        $proposal = $this->makeProposal($contact->client_id, ['status' => 'accepted']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.portal.proposals.accept', $proposal));

        $response->assertStatus(422);
    }

    public function test_client_cannot_view_another_clients_proposal(): void
    {
        $this->actingAsClient();
        $otherClient = Client::factory()->create();
        $otherProposal = $this->makeProposal($otherClient->id);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.proposals.show', $otherProposal));

        $response->assertStatus(403);
    }

    public function test_expired_proposal_cannot_be_accepted(): void
    {
        $contact = $this->actingAsClient();
        $proposal = $this->makeProposal($contact->client_id, [
            'status' => 'sent',
            'valid_until' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertTrue($proposal->isExpired());
    }
}
