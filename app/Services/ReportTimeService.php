<?php

namespace App\Services;

use App\Models\Tenant\TimeEntry;

class ReportTimeService
{
    public static function generate(array $filters): array
    {
        $query = TimeEntry::with(['user', 'task', 'project']);

        if (!empty($filters['from'])) {
            $query->where('date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('date', '<=', $filters['to']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (isset($filters['billable'])) {
            $query->where('is_billable', (bool) $filters['billable']);
        }

        $entries = $query->orderBy('date')->get();

        $byUser = $entries->groupBy('user_id');
        $byProject = $entries->groupBy('project_id');
        $byDay = $entries->groupBy(fn ($e) => $e->date->toDateString());

        return [
            'entries' => $entries,
            'total_hours' => round($entries->sum('hours'), 2),
            'billable_hours' => round($entries->where('is_billable', true)->sum('hours'), 2),
            'by_user' => $byUser->map(fn ($g) => [
                'user' => $g->first()->user?->only(['id', 'name', 'avatar']),
                'hours' => round($g->sum('hours'), 2),
                'billable' => round($g->where('is_billable', true)->sum('hours'), 2),
            ])->values(),
            'by_project' => $byProject->map(fn ($g) => [
                'project' => $g->first()->project?->only(['id', 'name']),
                'hours' => round($g->sum('hours'), 2),
                'billable' => round($g->where('is_billable', true)->sum('hours'), 2),
            ])->values(),
            'by_day' => $byDay->map(fn ($g, $date) => [
                'date' => $date,
                'hours' => round($g->sum('hours'), 2),
            ])->values(),
        ];
    }

    public static function exportCsv(array $filters): string
    {
        $data = static::generate($filters);
        $lines = ['Data,Użytkownik,Projekt,Zadanie,Godziny,Billable,Opis'];

        foreach ($data['entries'] as $entry) {
            $lines[] = implode(',', [
                $entry->date->toDateString(),
                $entry->user?->name ?? '',
                $entry->project?->name ?? '',
                $entry->task?->title ?? '',
                $entry->hours,
                $entry->is_billable ? 'Tak' : 'Nie',
                '"' . str_replace('"', '""', $entry->description ?? '') . '"',
            ]);
        }

        return implode("\n", $lines);
    }
}
