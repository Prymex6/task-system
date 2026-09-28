<?php

namespace App\Services;

use App\Models\Tenant\Client;

class ReportClientService
{
    public static function generate(array $filters): array
    {
        $query = Client::with(['invoices', 'projects', 'tickets', 'deals']);

        if (!empty($filters['is_active'])) {
            $query->where('is_active', true);
        }

        return $query->get()->map(function ($client) {
            $invoices = $client->invoices;
            $totalBilled = $invoices->sum('total');
            $totalPaid = $invoices->where('status', 'paid')->sum('total');

            return [
                'client' => $client->only(['id', 'name', 'email', 'is_active']),
                'total_projects' => $client->projects->count(),
                'open_projects' => $client->projects->where('is_archived', false)->count(),
                'total_invoiced' => round($totalBilled, 2),
                'total_paid' => round($totalPaid, 2),
                'outstanding' => round($totalBilled - $totalPaid, 2),
                'open_tickets' => $client->tickets->whereNotIn('status', ['closed', 'resolved'])->count(),
                'deals' => $client->deals->count(),
            ];
        })->sortByDesc('total_invoiced')->values()->toArray();
    }
}
