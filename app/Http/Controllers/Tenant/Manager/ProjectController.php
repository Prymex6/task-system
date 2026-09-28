<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Project;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $query = Project::visibleTo($user)->with(['client', 'members', 'status']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('archived')) {
            $query->where('is_archived', (bool) $request->archived);
        } else {
            $query->where('is_archived', false);
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tenant/Manager/Projects/Index', [
            'projects' => $projects,
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'filters' => $request->only(['search', 'status', 'client_id', 'archived']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Projects/Form', [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'avatar', 'workspace_role']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
            'visibility' => 'required|in:private,team,client',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:7',
            'members' => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.project_role' => 'required|in:project_manager,contributor,viewer',
        ]);

        $user = Auth::guard('tenant')->user();
        $project = Project::create(array_merge(
            collect($validated)->except('members')->toArray(),
            ['created_by' => $user->id]
        ));

        // The creator always joins as project_manager, whatever the payload said.
        $members = collect($validated['members'] ?? [])
            ->keyBy('user_id')
            ->map(fn ($m) => ['project_role' => $m['project_role'], 'added_by' => $user->id])
            ->toArray();

        $members[$user->id] = ['project_role' => 'project_manager', 'added_by' => $user->id];
        $project->members()->sync($members);

        Log::info('Project: utworzono projekt', ['project_id' => $project->id, 'by' => $user->id]);

        return redirect()->route('tenant.manager.projects.show', $project)
            ->with('success', __('messages.project_created'));
    }

    public function show(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $project->load([
            'client',
            'members.staffProfile',
            'milestones',
            'files',
            'discussions.creator',
            'tasks.assignees',
            'tasks.status',
        ]);

        return Inertia::render('Tenant/Manager/Projects/Show', [
            'project' => $project,
            'myRole' => $project->members->where('id', $user->id)->first()?->pivot?->project_role ?? null,
            'progress' => $project->progress,
            'totalHours' => $project->total_logged_hours,
        ]);
    }

    public function edit(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        return Inertia::render('Tenant/Manager/Projects/Form', [
            'project' => $project->load('members'),
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'avatar', 'workspace_role']),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
            'visibility' => 'required|in:private,team,client',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:7',
        ]);

        $project->update($validated);
        Log::info('Project: zaktualizowano projekt', ['project_id' => $project->id, 'by' => $user->id]);

        return back()->with('success', __('messages.project_updated'));
    }

    public function destroy(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        Log::info('Project: usunięto projekt', ['project_id' => $project->id, 'name' => $project->name, 'by' => $user->id]);
        $project->delete();

        return redirect()->route('tenant.manager.projects.index')
            ->with('success', __('messages.project_deleted'));
    }

    public function archive(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $project->update(['is_archived' => true]);

        return back()->with('success', __('messages.project_archived'));
    }

    public function restore(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $project->update(['is_archived' => false]);

        return back()->with('success', __('messages.project_restored'));
    }

    // ── Project members ───────────────────────────────────────────────────────

    public function members(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        return Inertia::render('Tenant/Manager/Projects/Members', [
            'project' => $project->load('members'),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'avatar', 'workspace_role']),
        ]);
    }

    public function addMember(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_role' => 'required|in:project_manager,contributor,viewer',
        ]);

        $project->members()->syncWithoutDetaching([
            $validated['user_id'] => [
                'project_role' => $validated['project_role'],
                'added_by' => $user->id,
            ],
        ]);

        return back()->with('success', __('messages.member_added'));
    }

    public function updateMember(Request $request, Project $project, User $member)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $validated = $request->validate([
            'project_role' => 'required|in:project_manager,contributor,viewer',
        ]);

        $project->members()->updateExistingPivot($member->id, $validated);

        return back()->with('success', __('messages.role_updated'));
    }

    public function removeMember(Project $project, User $member)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        if ($member->id === $project->created_by) {
            return back()->withErrors(['error' => __('messages.cannot_remove_project_creator')]);
        }

        $project->members()->detach($member->id);

        return back()->with('success', __('messages.member_removed'));
    }

    // ── Widok Kanban ──────────────────────────────────────────────────────────

    public function board(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $project->load(['tasks.assignees', 'tasks.status', 'tasks.labels']);

        return Inertia::render('Tenant/Manager/Projects/Board', [
            'project' => $project,
        ]);
    }
}
