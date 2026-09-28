<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\Sprint;
use App\Models\Tenant\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SprintController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $query = Sprint::with(['project', 'tasks.assignees', 'tasks.status']);

        if (!$user->isAdmin()) {
            $query->whereHas('project', fn ($q) => $q->whereHas('members', fn ($m) => $m->where('user_id', $user->id)));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sprints = $query->latest()->get();

        $projectsQuery = $user->isAdmin()
            ? Project::query()
            : Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id));

        return Inertia::render('Tenant/Manager/Sprints/Index', [
            'sprints' => $sprints,
            'projects' => $projectsQuery->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['project_id', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'goal' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        Sprint::create(array_merge($validated, ['status' => 'planning']));

        return back()->with('success', __('messages.sprint_created'));
    }

    public function show(Sprint $sprint)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $sprint->project->members->contains($user->id), 403);

        $sprint->load(['project', 'tasks.assignees', 'tasks.status', 'tasks.labels', 'storyPointsLog']);

        return Inertia::render('Tenant/Manager/Sprints/Show', [
            'sprint' => $sprint,
        ]);
    }

    public function update(Request $request, Sprint $sprint)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $sprint->project->members->contains($user->id), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planning,active,completed',
        ]);

        $sprint->update($validated);

        return back()->with('success', __('messages.sprint_updated'));
    }

    public function destroy(Sprint $sprint)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $sprint->delete();

        return back()->with('success', __('messages.sprint_deleted'));
    }

    /**
     * Dodaje zadanie do sprintu.
     */
    public function addTask(Request $request, Sprint $sprint)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $sprint->project->members->contains($user->id), 403);

        $validated = $request->validate(['task_id' => 'required|exists:tasks,id']);

        $sprint->tasks()->syncWithoutDetaching([$validated['task_id']]);

        return back()->with('success', __('messages.task_added_to_sprint'));
    }

    /**
     * Usuwa zadanie ze sprintu.
     */
    public function removeTask(Sprint $sprint, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $sprint->project->members->contains($user->id), 403);

        $sprint->tasks()->detach($task->id);

        return back()->with('success', __('messages.task_removed_from_sprint'));
    }

    /**
     * Uruchamia sprint.
     */
    public function start(Sprint $sprint)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $sprint->update(['status' => 'active', 'start_date' => $sprint->start_date ?? now()->toDateString()]);

        return back()->with('success', __('messages.sprint_started'));
    }

    /**
     * Closes a sprint.
     */
    public function complete(Sprint $sprint)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $sprint->update(['status' => 'completed', 'end_date' => $sprint->end_date ?? now()->toDateString()]);

        return back()->with('success', __('messages.sprint_finished'));
    }
}
