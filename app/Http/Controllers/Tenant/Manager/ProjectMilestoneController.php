<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Milestones marking the dated checkpoints of a project.
 *
 * Anybody on the project can read them; only an admin or a manager moves one,
 * because a milestone date is what the client is held to.
 */
class ProjectMilestoneController extends Controller
{
    public function index(Project $project)
    {
        $this->authorizeMember($project);

        return Inertia::render('Tenant/Manager/Projects/Milestones', [
            'project' => $project->only(['id', 'name', 'deadline']),
            'milestones' => $project->milestones()->orderBy('due_date')->get(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeManager();

        $project->milestones()->create($request->validate($this->rules()));

        return back()->with('success', __('messages.milestone_created'));
    }

    public function update(Request $request, Project $project, ProjectMilestone $milestone)
    {
        $this->authorizeManager();
        abort_unless($milestone->project_id === $project->id, 404);

        $validated = $request->validate($this->rules());

        // completed_at follows the tick rather than arriving from the client.
        if (array_key_exists('is_completed', $validated)) {
            $validated['completed_at'] = $validated['is_completed'] ? now() : null;
        }

        $milestone->update($validated);

        return back()->with('success', __('messages.milestone_updated'));
    }

    public function destroy(Project $project, ProjectMilestone $milestone)
    {
        $this->authorizeManager();
        abort_unless($milestone->project_id === $project->id, 404);

        $milestone->delete();

        return back()->with('success', __('messages.milestone_deleted'));
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'is_completed' => 'sometimes|boolean',
        ];
    }

    private function canManage(): bool
    {
        $user = Auth::guard('tenant')->user();

        return (bool) $user && ($user->isAdmin() || $user->isManager());
    }

    private function authorizeManager(): void
    {
        abort_unless($this->canManage(), 403);
    }

    private function authorizeMember(Project $project): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);
    }
}
