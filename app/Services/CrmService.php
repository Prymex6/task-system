<?php

namespace App\Services;

use App\Models\Tenant\Client;
use App\Models\Tenant\Deal;
use App\Models\Tenant\DealStage;

class CrmService
{
    public static function getPipelineSummary(): array
    {
        $stages = DealStage::orderBy('order')->get();

        return $stages->map(fn ($stage) => [
            'stage' => $stage,
            'deals' => Deal::where('stage_id', $stage->id)->with(['client'])->get(),
            'total_value' => Deal::where('stage_id', $stage->id)->sum('value'),
        ])->toArray();
    }

    public static function moveDeal(Deal $deal, int $stageId): void
    {
        $old = ['stage_id' => $deal->stage_id];
        $deal->update(['stage_id' => $stageId]);
        AuditService::log('deal.stage_changed', $deal, $old, ['stage_id' => $stageId]);
    }

    public static function getClientActivity(Client $client): array
    {
        return [
            'projects' => $client->projects()->count(),
            'open_invoices' => $client->invoices()->whereNotIn('status', ['paid', 'cancelled'])->count(),
            'total_invoiced' => $client->invoices()->sum('total'),
            'total_paid' => $client->invoices()->where('status', 'paid')->sum('total'),
            'open_tickets' => $client->tickets()->whereNotIn('status', ['closed', 'resolved'])->count(),
            'open_deals' => $client->deals()->count(),
        ];
    }
}
