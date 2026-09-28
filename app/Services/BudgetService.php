<?php

namespace App\Services;

use App\Models\Tenant\BudgetEntry;
use App\Models\Tenant\Project;
use App\Models\Tenant\TimeEntry;

class BudgetService
{
    /**
     * Everything the budget screen shows for one project.
     *
     * @return array<string, mixed>
     */
    public static function getSummary(Project $project): array
    {
        $budget = (float) $project->budget;

        $entries = BudgetEntry::where('project_id', $project->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $spent = (float) $entries->where('type', 'expense')->sum('amount');
        $earned = (float) $entries->where('type', 'income')->sum('amount');

        $billableHours = (float) TimeEntry::where('project_id', $project->id)
            ->where('is_billable', true)
            ->sum('hours');

        $hourlyRate = (float) ($project->hourly_rate ?? 0);
        $invoicedTotal = (float) $project->invoices()
            ->whereIn('status', ['sent', 'paid', 'partial'])
            ->sum('total');

        return [
            'budget' => $budget,
            'expenses' => round($spent, 2),
            'income' => round($earned, 2),
            'invoiced' => round($invoicedTotal, 2),
            'time_revenue' => round($billableHours * $hourlyRate, 2),
            'billable_hours' => round($billableHours, 2),
            'remaining' => round($budget - $spent, 2),
            'used_percent' => $budget > 0 ? round($spent / $budget * 100, 1) : 0,
            'entries' => $entries,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function addEntry(Project $project, array $attributes): BudgetEntry
    {
        return $project->budgetEntries()->create([
            'type' => $attributes['type'],
            'amount' => $attributes['amount'],
            'category' => $attributes['category'] ?? null,
            'description' => $attributes['description'] ?? null,
            'entry_date' => $attributes['date'] ?? now()->toDateString(),
            'created_by' => $attributes['created_by'] ?? null,
        ]);
    }
}
