<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectTemplate;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Reusable project blueprints: a name, a description and a list of tasks.
 *
 * Instantiating one copies the tasks into a fresh project rather than linking
 * to them, so editing the template afterwards leaves running projects alone.
 */
class ProjectTemplateController extends Controller
{
    public function index()
    {
        $this->authorizeManager();

        return Inertia::render('Tenant/Manager/Projects/Templates', [
            'templates' => ProjectTemplate::with(['creator:id,name', 'tasks'])
                ->withCount('tasks')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->authorizeManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'tasks' => 'nullable|array',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string|max:2000',
            'tasks.*.estimated_hours' => 'nullable|integer|min:0',
            'tasks.*.priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $template = ProjectTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach (array_values($validated['tasks'] ?? []) as $order => $task) {
                $template->tasks()->create([
                    ...$task,
                    'priority' => $task['priority'] ?? 'medium',
                    'order' => $order,
                ]);
            }
        });

        return back()->with('success', __('messages.template_saved'));
    }

    public function createProject(Request $request, ProjectTemplate $template)
    {
        $user = $this->authorizeManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'start_date' => 'nullable|date',
        ]);

        $defaultStatus = TaskStatus::where('is_default', true)->value('id');

        $project = DB::transaction(function () use ($template, $validated, $user, $defaultStatus) {
            $project = Project::create([
                'name' => $validated['name'],
                'description' => $template->description,
                'client_id' => $validated['client_id'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'status' => 'planning',
                'visibility' => 'team',
                'created_by' => $user->id,
            ]);

            $project->members()->attach($user->id, [
                'project_role' => 'project_manager',
                'added_by' => $user->id,
            ]);

            foreach ($template->tasks as $order => $task) {
                $project->tasks()->create([
                    'task_status_id' => $defaultStatus,
                    'created_by' => $user->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'estimated_hours' => $task->estimated_hours,
                    'priority' => $task->priority,
                    'order' => $order,
                ]);
            }

            return $project;
        });

        return redirect()->route('tenant.manager.projects.show', $project)
            ->with('success', __('messages.project_created_from_template'));
    }

    public function destroy(ProjectTemplate $template)
    {
        $this->authorizeManager();

        $template->delete();

        return back()->with('success', __('messages.template_deleted'));
    }

    private function authorizeManager(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        return $user;
    }
}
