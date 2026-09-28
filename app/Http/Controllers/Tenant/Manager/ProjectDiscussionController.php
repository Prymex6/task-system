<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectDiscussion;
use App\Models\Tenant\ProjectDiscussionComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectDiscussionController extends Controller
{
    public function index(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $discussions = $project->discussions()
            ->with(['creator', 'comments'])
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return Inertia::render('Tenant/Manager/Projects/Discussions/Index', [
            'project' => $project,
            'discussions' => $discussions,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:10000',
        ]);

        $project->discussions()->create(array_merge($validated, [
            'created_by' => $user->id,
        ]));

        return back()->with('success', __('messages.discussion_created'));
    }

    public function show(Project $project, ProjectDiscussion $discussion)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);
        abort_unless($discussion->project_id === $project->id, 404);

        $discussion->load(['creator', 'comments.creator']);

        return Inertia::render('Tenant/Manager/Projects/Discussions/Show', [
            'project' => $project,
            'discussion' => $discussion,
        ]);
    }

    public function destroy(Project $project, ProjectDiscussion $discussion)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $discussion->created_by === $user->id, 403);

        $discussion->delete();

        return redirect()->route('tenant.manager.projects.discussions.index', $project)
            ->with('success', __('messages.discussion_deleted'));
    }

    public function addComment(Request $request, Project $project, ProjectDiscussion $discussion)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $discussion->comments()->create(array_merge($validated, [
            'created_by' => $user->id,
        ]));

        return back()->with('success', __('messages.comment_created'));
    }

    public function destroyComment(Project $project, ProjectDiscussion $discussion, ProjectDiscussionComment $comment)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $comment->created_by === $user->id, 403);

        $comment->delete();

        return back()->with('success', __('messages.comment_deleted'));
    }
}
