<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $subtask = Task::create([
            'title' => $request->title,
            'project_id' => $task->project_id,
            'parent_task_id' => $task->id,
            'priority' => $request->input('priority', 'medium'),
            'task_status_id' => $task->task_status_id,
            'created_by' => $user->id,
        ]);

        AuditService::log('subtask_created', $task, [], ['subtask_id' => $subtask->id, 'title' => $subtask->title]);

        return back()->with('success', __('messages.subtask_created'));
    }

    public function update(Request $request, Task $task, Task $subtask)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $subtask->parent_task_id === $task->id, 403);

        $request->validate(['title' => 'required|string|max:255']);

        $old = $subtask->only(['title', 'completed_at']);
        $subtask->update($request->only(['title', 'priority']));

        if ($request->boolean('completed') && !$subtask->completed_at) {
            $subtask->update(['completed_at' => now()]);
        } elseif (!$request->boolean('completed') && $subtask->completed_at) {
            $subtask->update(['completed_at' => null]);
        }

        AuditService::log('subtask_updated', $task, $old, $subtask->fresh()->only(['title', 'completed_at']));

        return back()->with('success', __('messages.subtask_updated'));
    }

    public function destroy(Task $task, Task $subtask)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $subtask->parent_task_id === $task->id, 403);

        $subtask->delete();

        AuditService::log('subtask_deleted', $task, ['subtask_id' => $subtask->id], []);

        return back()->with('success', __('messages.subtask_deleted'));
    }
}
