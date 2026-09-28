<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskLabel;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $query = Task::with(['project', 'assignees', 'status', 'labels']);

        // Only tasks from projects this person can see
        if (!$user->isAdmin()) {
            $query->whereHas('project', fn ($q) => $q->whereHas('members', fn ($m) => $m->where('user_id', $user->id)));
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('assignee_id')) {
            $query->whereHas('assignees', fn ($q) => $q->where('user_id', $request->assignee_id));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->boolean('my_tasks')) {
            $query->whereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($request->boolean('overdue')) {
            $query->where('is_completed', false)->whereNotNull('due_date')->where('due_date', '<', now());
        }

        $tasks = $query->orderByRaw('ISNULL(due_date), due_date ASC')->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Tasks/Index', [
            'tasks' => $tasks,
            'statuses' => TaskStatus::orderBy('order')->get(),
            'labels' => TaskLabel::orderBy('name')->get(),
            'filters' => $request->only(['search', 'project_id', 'assignee_id', 'priority', 'status_id', 'my_tasks', 'overdue']),
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $projectsQuery = $user->isAdmin()
            ? Project::query()
            : Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id));

        return Inertia::render('Tenant/Manager/Tasks/Form', [
            'projects' => $projectsQuery->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'statuses' => TaskStatus::orderBy('order')->get(),
            'labels' => TaskLabel::orderBy('name')->get(),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'avatar']),
            'defaultProjectId' => $request->integer('project_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:10000',
            'project_id' => 'required|exists:projects,id',
            'status_id' => 'nullable|exists:task_statuses,id',
            'milestone_id' => 'nullable|exists:project_milestones,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'story_points' => 'nullable|integer|min:0|max:100',
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
            'labels' => 'nullable|array',
            'labels.*' => 'exists:task_labels,id',
        ]);

        // Check they are on the project first
        $project = Project::findOrFail($validated['project_id']);
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $task = Task::create(array_merge(
            collect($validated)->except(['assignees', 'labels'])->toArray(),
            ['created_by' => $user->id]
        ));

        if (!empty($validated['assignees'])) {
            $task->assignees()->sync($validated['assignees']);
        }

        if (!empty($validated['labels'])) {
            $task->labels()->sync($validated['labels']);
        }

        Log::info('Task: zadanie utworzone', ['task_id' => $task->id, 'by' => $user->id]);

        return redirect()->route('tenant.manager.tasks.show', $task)
            ->with('success', __('messages.task_created'));
    }

    public function show(Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);

        $task->load([
            'project',
            'status',
            'milestone',
            'creator',
            'parent',
            'subtasks.assignees',
            'subtasks.status',
            'assignees',
            'followers',
            'labels',
            'comments.creator',
            'attachments',
            'checklists.items',
            'timeEntries.user',
        ]);

        $staff = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'avatar']);

        return Inertia::render('Tenant/Manager/Tasks/Show', [
            'task' => $task,
            'staff' => $staff,
            'statuses' => TaskStatus::orderBy('order')->get(),
            'labels' => TaskLabel::orderBy('name')->get(),
            'myRole' => $task->project->members->where('id', $user->id)->first()?->pivot?->project_role,
        ]);
    }

    public function edit(Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);

        $task->load(['assignees', 'labels']);

        $projectsQuery = $user->isAdmin()
            ? Project::query()
            : Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id));

        return Inertia::render('Tenant/Manager/Tasks/Form', [
            'task' => $task,
            'projects' => $projectsQuery->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'statuses' => TaskStatus::orderBy('order')->get(),
            'labels' => TaskLabel::orderBy('name')->get(),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'avatar']),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:10000',
            'task_status_id' => 'nullable|exists:task_statuses,id',
            'milestone_id' => 'nullable|exists:project_milestones,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'story_points' => 'nullable|integer|min:0|max:100',
            'is_completed' => 'boolean',
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
            'labels' => 'nullable|array',
            'labels.*' => 'exists:task_labels,id',
        ]);

        // Ustaw completed_at
        if (isset($validated['is_completed'])) {
            if ($validated['is_completed'] && !$task->is_completed) {
                $validated['completed_at'] = now();
            } elseif (!$validated['is_completed']) {
                $validated['completed_at'] = null;
            }
        }

        $task->update(collect($validated)->except(['assignees', 'labels'])->toArray());

        if (array_key_exists('assignees', $validated)) {
            $task->assignees()->sync($validated['assignees'] ?? []);
        }

        if (array_key_exists('labels', $validated)) {
            $task->labels()->sync($validated['labels'] ?? []);
        }

        return back()->with('success', __('messages.task_updated'));
    }

    public function destroy(Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        Log::info('Task: usunięto zadanie', ['task_id' => $task->id, 'by' => $user->id]);
        $task->delete();

        return redirect()->route('tenant.manager.tasks.index')
            ->with('success', __('messages.task_deleted'));
    }

    /**
     * Szybka zmiana statusu (drag & drop z board).
     */
    public function updateStatus(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);

        $validated = $request->validate([
            'status_id' => 'required|exists:task_statuses,id',
            'is_completed' => 'nullable|boolean',
        ]);

        $task->update($validated);

        return response()->json(['success' => true]);
    }

    public function kanban(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $query = Task::with(['project', 'assignees', 'status', 'labels']);

        if (!$user->isAdmin()) {
            $query->whereHas('project', fn ($q) => $q->whereHas('members', fn ($m) => $m->where('user_id', $user->id)));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->get();

        $columns = TaskStatus::orderBy('order')->get()->map(fn ($status) => [
            'status' => $status,
            'tasks' => $tasks->where('task_status_id', $status->id)->values(),
        ]);

        return Inertia::render('Tenant/Manager/Tasks/Kanban', [
            'columns' => $columns,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'project' => $request->filled('project_id') ? Project::find($request->project_id) : null,
            'filters' => $request->only(['project_id']),
        ]);
    }
}
