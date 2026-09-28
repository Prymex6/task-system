<?php

namespace Tests\Unit\Services;

use App\Models\Tenant\Client;
use App\Models\Tenant\Ticket;
use App\Models\Tenant\User;
use App\Services\TicketService;
use Tests\TenantTestCase;

class TicketServiceTest extends TenantTestCase
{
    private function makeTicket(array $attrs = []): Ticket
    {
        return Ticket::factory()->create(array_merge([
            'client_id' => Client::factory()->create()->id,
            'subject' => 'Ticket testowy',
            'status' => 'open',
            'priority' => 'medium',
        ], $attrs));
    }

    public function test_create_creates_ticket_with_open_status(): void
    {
        $client = Client::factory()->create();

        $ticket = TicketService::create([
            'client_id' => $client->id,
            'subject' => 'Problem z logowaniem',
            'priority' => 'high',
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('open', $ticket->status);
        $this->assertNotNull($ticket->opened_at);
    }

    public function test_assign_sets_assigned_to_and_status_in_progress(): void
    {
        $ticket = $this->makeTicket(['status' => 'open']);
        $agent = User::factory()->create(['workspace_role' => 'member']);

        TicketService::assign($ticket, $agent->id);

        $this->assertEquals($agent->id, $ticket->fresh()->assigned_to);
        $this->assertEquals('in_progress', $ticket->fresh()->status);
    }

    public function test_close_sets_closed_status_and_timestamp(): void
    {
        $ticket = $this->makeTicket(['status' => 'in_progress']);

        TicketService::close($ticket);

        $this->assertEquals('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_reopen_sets_open_status_and_clears_closed_at(): void
    {
        $ticket = $this->makeTicket(['status' => 'closed', 'closed_at' => now()]);

        TicketService::reopen($ticket);

        $this->assertEquals('open', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->closed_at);
    }

    public function test_reply_creates_message_for_ticket(): void
    {
        $ticket = $this->makeTicket(['status' => 'open']);
        $agent = User::factory()->create();

        TicketService::reply($ticket, ['body' => 'Dziękujemy za zgłoszenie.'], $agent);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'Dziękujemy za zgłoszenie.',
            'user_id' => $agent->id,
        ]);
    }

    public function test_reply_changes_status_to_in_progress_when_open(): void
    {
        $ticket = $this->makeTicket(['status' => 'open']);
        $agent = User::factory()->create();

        TicketService::reply($ticket, ['body' => 'Sprawdzamy problem.'], $agent);

        $this->assertEquals('in_progress', $ticket->fresh()->status);
    }

    public function test_is_breached_returns_true_when_resolution_due_is_past(): void
    {
        $ticket = $this->makeTicket(['sla_resolution_due' => now()->subHour()]);

        $this->assertTrue(TicketService::isBreached($ticket));
    }

    public function test_is_breached_returns_false_when_resolution_due_is_future(): void
    {
        $ticket = $this->makeTicket(['sla_resolution_due' => now()->addHour()]);

        $this->assertFalse(TicketService::isBreached($ticket));
    }

    public function test_is_breached_returns_false_when_no_sla_set(): void
    {
        $ticket = $this->makeTicket(['sla_resolution_due' => null]);

        $this->assertFalse(TicketService::isBreached($ticket));
    }
}
