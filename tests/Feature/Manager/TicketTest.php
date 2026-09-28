<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Ticket;
use Tests\TenantTestCase;

class TicketTest extends TenantTestCase
{
    public function test_support_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.support.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('tickets'));
    }

    public function test_support_index_lists_tickets(): void
    {
        $this->actingAsManager();
        Ticket::factory()->count(3)->create(['status' => 'open']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.support.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('tickets'));
    }

    public function test_manager_can_view_ticket(): void
    {
        $this->actingAsManager();
        $ticket = Ticket::factory()->create(['subject' => 'Problem z fakturą']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.support.show', $ticket));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('ticket.id', $ticket->id));
    }

    public function test_manager_can_close_ticket(): void
    {
        $this->actingAsManager();
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.support.close', $ticket));

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'closed']);
    }

    public function test_manager_can_reopen_ticket(): void
    {
        $this->actingAsManager();
        $ticket = Ticket::factory()->closed()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.support.reopen', $ticket));

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'open']);
    }
}
