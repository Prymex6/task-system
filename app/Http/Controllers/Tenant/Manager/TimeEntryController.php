<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\Timer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $query = TimeEntry::with(['project', 'task'])
            ->where('user_id', $user->id)
            ->orderByDesc('date');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $entries = $query->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Time/Index', [
            'entries' => $entries,
            'activeTimer' => Timer::with('task')->where('user_id', $user->id)->first(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'tasks' => Task::whereHas('assignees', fn ($q) => $q->where('user_id', $user->id))->orderBy('title')->get(['id', 'title', 'project_id']),
            'totalHours' => (clone $query)->sum('hours'),
            'filters' => $request->only(['project_id', 'date_from', 'date_to']),
        ]);
    }

    public function team(Request $request)
    {
        $entries = TimeEntry::with(['user', 'project', 'task'])
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Time/Team', [
            'entries' => $entries,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_billable' => ['boolean'],
        ]);

        $validated['user_id'] = $user->id;
        $validated['is_billable'] = $validated['is_billable'] ?? true;

        TimeEntry::create($validated);

        return back()->with('success', __('messages.time_entry_saved'));
    }

    public function update(Request $request, TimeEntry $entry)
    {
        $validated = $request->validate([
            'hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_billable' => ['boolean'],
        ]);

        $entry->update($validated);

        return back()->with('success', __('messages.time_entry_updated'));
    }

    public function destroy(TimeEntry $entry)
    {
        $entry->delete();

        return back()->with('success', __('messages.time_entry_deleted'));
    }
}
