<?php

namespace App\Services;

use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;

class ReportStaffService
{
    public static function generate(array $filters): array
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();

        $users = User::where('is_active', true)->get();

        return $users->map(function ($user) use ($from, $to) {
            $entries = TimeEntry::where('user_id', $user->id)
                ->whereBetween('date', [$from, $to])
                ->get();

            $totalTasks = $user->assignedTasks()->count();
            $completedTasks = $user->assignedTasks()->whereNotNull('completed_at')->count();

            return [
                'user' => $user->only(['id', 'name', 'avatar']),
                'total_hours' => round($entries->sum('hours'), 2),
                'billable_hours' => round($entries->where('is_billable', true)->sum('hours'), 2),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round($completedTasks / $totalTasks * 100, 1) : 0,
                'projects' => $entries->pluck('project_id')->unique()->count(),
            ];
        })->sortByDesc('total_hours')->values()->toArray();
    }
}
