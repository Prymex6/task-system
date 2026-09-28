<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        $task->comments()->create(array_merge($validated, [
            'user_id' => $user->id,
        ]));

        return back()->with('success', __('messages.comment_created'));
    }

    public function update(Request $request, Task $task, TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id, 404);
        abort_unless(Auth::guard('tenant')->id() === $comment->user_id, 403);

        $validated = $request->validate(['body' => 'required|string|max:5000']);
        $comment->update($validated);

        return back()->with('success', __('messages.comment_updated'));
    }

    public function destroy(Task $task, TaskComment $comment)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($comment->task_id === $task->id, 404);
        abort_unless($user->isAdmin() || $user->id === $comment->user_id, 403);

        $comment->delete();

        return back()->with('success', __('messages.comment_deleted'));
    }

    public function react(Request $request, Task $task, TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id, 404);
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate(['emoji' => 'required|string|max:10']);

        $existing = $comment->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $comment->reactions()->create([
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
            ]);
        }

        return response()->json(['success' => true]);
    }
}
