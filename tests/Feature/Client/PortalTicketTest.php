<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Ticket;
use Tests\TenantTestCase;

class PortalTicketTest extends TenantTestCase
{
    public function test_client_can_view_ticket_list(): void
    {
        $contact = $this->actingAsClient();
        Ticket::factory()->count(2)->create(['client_id' => $contact->client_id]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.tickets'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Client/Tickets/Index')
            ->has('tickets.data', 2)
        );
    }

    public function test_client_can_create_ticket(): void
    {
        $contact = $this->actingAsClient();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.portal.tickets.store'), [
                'subject' => 'Nie mogę zalogować się do systemu',
                'message' => 'Od wczoraj moje konto nie działa, proszę o pomoc.',
                'priority' => 'high',
            ]);

        $response->assertRedirect(route('tenant.portal.tickets'));
        $this->assertDatabaseHas('tickets', [
            'client_id' => $contact->client_id,
            'subject' => 'Nie mogę zalogować się do systemu',
        ]);
    }

    public function test_client_can_reply_to_ticket(): void
    {
        $contact = $this->actingAsClient();
        $ticket = Ticket::factory()->create([
            'client_id' => $contact->client_id,
            'status' => 'pending',
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.portal.tickets.messages.store', $ticket), [
                'message' => 'Dziękuję za odpowiedź, problem nadal występuje.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'open']);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'client_contact_id' => $contact->id,
        ]);
    }

    public function test_client_cannot_view_ticket_of_another_client(): void
    {
        $this->actingAsClient();
        $otherTicket = Ticket::factory()->create(); // other client

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.tickets.show', $otherTicket));

        $response->assertStatus(403);
    }

    public function test_ticket_reply_validates_empty_message(): void
    {
        $contact = $this->actingAsClient();
        $ticket = Ticket::factory()->create(['client_id' => $contact->client_id]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.portal.tickets.messages.store', $ticket), [
                'message' => '',
            ]);

        $response->assertSessionHasErrors('message');
    }
}
