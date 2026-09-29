<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskLabel;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * The form is reached two ways: /tasks/create, where the project is picked
     * from the list, and /projects/{project}/tasks/create, where it is already
     * known. Only the query string used to be read, so arriving from inside a
     * project left the field empty and the save came back demanding it.
     */
    public function create(Request $request, ?Project $project = null)
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
            'defaultProjectId' => $project?->id ?: ($request->integer('project_id') ?: null),
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

    /**
     * Tasks on a timeline, for one project at a time.
     *
     * A Gantt chart of every project at once is unreadable, so a project has
     * to be chosen before there is anything to draw.
     */
    public function gantt(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $projects = $this->visibleProjects($user);

        $projectId = $request->integer('project_id') ?: $projects->first()?->id;

        $tasks = $projectId
            ? Task::where('project_id', $projectId)
                ->whereNotNull('start_date')
                ->orWhere(fn ($q) => $q->where('project_id', $projectId)->whereNotNull('due_date'))
                ->orderBy('start_date')
                ->orderBy('due_date')
                ->get(['id', 'title', 'priority', 'start_date', 'due_date', 'completed_at'])
            : collect();

        return Inertia::render('Tenant/Manager/Tasks/Gantt', [
            'tasks' => $tasks->map(fn (Task $task) => [
                ...$task->only(['id', 'title', 'priority', 'start_date', 'due_date']),
                'is_completed' => $task->completed_at !== null,
            ])->values(),
            'projects' => $projects,
            'filters' => ['project_id' => $projectId],
        ]);
    }

    /**
     * Tasks by due date, for the month the calendar is showing.
     */
    public function calendar(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $month = $request->filled('month')
            ? Carbon::parse($request->query('month'))
            : now();

        $tasks = Task::with('status:id,name,color')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->when(!$user->isAdmin(), fn ($q) => $q->whereHas(
                'project',
                fn ($p) => $p->whereHas('members', fn ($m) => $m->where('user_id', $user->id))
            ))
            ->get(['id', 'title', 'due_date', 'task_status_id', 'completed_at']);

        return Inertia::render('Tenant/Manager/Tasks/Calendar', [
            'tasks' => $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date?->toDateString(),
                'status' => $task->status?->only(['id', 'name', 'color']),
                'is_completed' => $task->completed_at !== null,
            ])->values(),
            'month' => $month->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, Project>
     */
    private function visibleProjects($user)
    {
        return Project::query()
            ->when(!$user->isAdmin(), fn ($q) => $q->whereHas('members', fn ($m) => $m->where('user_id', $user->id)))
            ->where('is_archived', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Move a task between board columns, and within one.
     *
     * The board sends the whole column after the drop rather than one index,
     * so the order is rewritten from that list: deriving it from a single
     * position leaves gaps the next drag has to guess at.
     */
    public function move(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && ($user->isAdmin() || $task->project->members->contains($user->id)), 403);

        $validated = $request->validate([
            'task_status_id' => 'required|integer|exists:task_statuses,id',
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer|exists:tasks,id',
        ]);

        DB::transaction(function () use ($task, $validated) {
            $task->update(['task_status_id' => $validated['task_status_id']]);

            foreach (array_values($validated['ordered_ids']) as $position => $id) {
                Task::where('id', $id)
                    ->where('project_id', $task->project_id)
                    ->update(['order' => $position]);
            }
        });

        return back();
    }
}
