<?php

namespace App\Services;

use App\Models\Tenant\Project;

class ReportProjectService
{
    public static function generate(array $filters): array
    {
        $query = Project::with(['tasks', 'members', 'client']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        $projects = $query->get();

        return $projects->map(function ($project) {
            $tasks = $project->tasks;
            $totalTasks = $tasks->count();
            $done = $tasks->filter(fn ($t) => $t->isCompleted())->count();
            $overdue = $tasks->filter(fn ($t) => $t->isOverdue())->count();
            $hours = $project->timeEntries()->sum('hours');

            return [
                'project' => $project->only(['id', 'name', 'status', 'budget', 'due_date']),
                'client' => $project->client?->only(['id', 'name']),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $done,
                'overdue_tasks' => $overdue,
                'progress_percent' => $totalTasks > 0 ? round($done / $totalTasks * 100) : 0,
                'logged_hours' => round($hours, 2),
                'team_size' => $project->members->count(),
            ];
        })->values()->toArray();
    }
}
