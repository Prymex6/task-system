<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'color' => 'nullable|string|max:7',
        ]);

        $project->milestones()->create($validated);

        return back()->with('success', __('messages.milestone_created'));
    }

    public function update(Request $request, Project $project, ProjectMilestone $milestone)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);
        abort_unless($milestone->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'color' => 'nullable|string|max:7',
            'is_completed' => 'boolean',
        ]);

        $milestone->update($validated);

        return back()->with('success', __('messages.milestone_updated'));
    }

    public function destroy(Project $project, ProjectMilestone $milestone)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($milestone->project_id === $project->id, 404);

        $milestone->delete();

        return back()->with('success', __('messages.milestone_deleted'));
    }
}
