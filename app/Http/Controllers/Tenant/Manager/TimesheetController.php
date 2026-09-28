<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    public function reports(Request $request)
    {
        $query = TimeEntry::query();

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $byUser = (clone $query)
            ->selectRaw('user_id, SUM(hours) as total_hours')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'name' => $row->user?->name,
                'total_hours' => (float) $row->total_hours,
            ]);

        $byProject = (clone $query)
            ->selectRaw('project_id, SUM(hours) as total_hours')
            ->groupBy('project_id')
            ->with('project:id,name')
            ->get()
            ->map(fn ($row) => [
                'project_id' => $row->project_id,
                'name' => $row->project?->name,
                'total_hours' => (float) $row->total_hours,
            ]);

        return Inertia::render('Tenant/Manager/Reports/TimeReport', [
            'byUser' => $byUser,
            'byProject' => $byProject,
            'summary' => [
                'total_hours' => (float) (clone $query)->sum('hours'),
                'users_count' => $byUser->count(),
                'projects_count' => $byProject->count(),
            ],
            'users' => User::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['date_from', 'date_to', 'user_id', 'project_id']),
        ]);
    }
}
