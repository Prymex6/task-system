<?php

namespace App\Services;

use App\Models\Tenant\Ticket;
use Carbon\Carbon;

class SlaService
{
    public static function getBreachedTickets(): array
    {
        return Ticket::whereNotIn('status', ['closed', 'resolved'])
            ->where(function ($q) {
                $q->where('sla_resolution_due', '<', now())
                    ->orWhere('sla_response_due', '<', now());
            })
            ->with(['assignee', 'department'])
            ->get()
            ->toArray();
    }

    public static function getRemainingTime(Ticket $ticket): array
    {
        return [
            'response' => $ticket->sla_response_due
                ? Carbon::parse($ticket->sla_response_due)->diffInMinutes(now(), false)
                : null,
            'resolution' => $ticket->sla_resolution_due
                ? Carbon::parse($ticket->sla_resolution_due)->diffInMinutes(now(), false)
                : null,
        ];
    }

    public static function getComplianceRate(): float
    {
        $total = Ticket::whereNotNull('sla_resolution_due')->count();
        $breached = Ticket::whereNotNull('sla_resolution_due')
            ->whereColumn('closed_at', '>', 'sla_resolution_due')
            ->count();

        return $total > 0 ? round(($total - $breached) / $total * 100, 1) : 100.0;
    }
}
