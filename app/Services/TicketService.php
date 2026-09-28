<?php

namespace App\Services;

use App\Models\Tenant\SlaPolicy;
use App\Models\Tenant\Ticket;
use App\Models\Tenant\User;

class TicketService
{
    public static function create(array $data, ?User $agent = null): Ticket
    {
        $ticket = Ticket::create(array_merge($data, [
            'status' => 'open',
            'opened_at' => now(),
        ]));

        static::applySla($ticket);

        AuditService::log('ticket.created', $ticket);

        return $ticket;
    }

    public static function assign(Ticket $ticket, int $userId): void
    {
        $ticket->update(['assigned_to' => $userId, 'status' => 'in_progress']);
        AuditService::log('ticket.assigned', $ticket);
    }

    public static function close(Ticket $ticket): void
    {
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        AuditService::log('ticket.closed', $ticket);
    }

    public static function reopen(Ticket $ticket): void
    {
        $ticket->update(['status' => 'open', 'closed_at' => null]);
        AuditService::log('ticket.reopened', $ticket);
    }

    public static function applySla(Ticket $ticket): void
    {
        $sla = SlaPolicy::where('priority', $ticket->priority)->first();
        if (!$sla) {
            return;
        }

        $ticket->update([
            'sla_response_due' => now()->addHours($sla->response_hours),
            'sla_resolution_due' => now()->addHours($sla->resolution_hours),
        ]);
    }

    public static function isBreached(Ticket $ticket): bool
    {
        if ($ticket->sla_resolution_due && now()->gt($ticket->sla_resolution_due)) {
            return true;
        }

        return false;
    }

    public static function reply(Ticket $ticket, array $data, ?User $user = null): void
    {
        $ticket->messages()->create([
            'user_id' => $user?->id,
            'body' => $data['body'],
            'is_internal_note' => $data['is_internal_note'] ?? false,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }
    }
}
