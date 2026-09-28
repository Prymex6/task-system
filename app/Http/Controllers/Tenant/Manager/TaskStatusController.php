<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TaskStatus;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskStatusController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/TaskStatuses', [
            'statuses' => TaskStatus::withCount('tasks')->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:task_statuses,name',
            'color' => 'required|string|max:7',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ]);

        $status = TaskStatus::create([
            ...$validated,
            'order' => (int) TaskStatus::max('order') + 1,
        ]);

        if ($status->is_default) {
            $this->makeSoleDefault($status);
        }

        AuditService::log('task_status_created', $status, [], $status->only(['name', 'color']));

        return back()->with('success', __('messages.task_status_created'));
    }

    public function update(Request $request, TaskStatus $taskStatus)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:task_statuses,name,' . $taskStatus->id,
            'color' => 'required|string|max:7',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ]);

        $old = $taskStatus->only(['name', 'color', 'is_default', 'is_closed']);
        $taskStatus->update($validated);

        if ($taskStatus->is_default) {
            $this->makeSoleDefault($taskStatus);
        }

        AuditService::log('task_status_updated', $taskStatus, $old, $taskStatus->fresh()->only(['name', 'color', 'is_default', 'is_closed']));

        return back()->with('success', __('messages.task_status_updated'));
    }

    public function destroy(TaskStatus $taskStatus)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        // Deleting a status the tasks board still points at would leave those
        // tasks in no column at all.
        if ($taskStatus->tasks()->exists()) {
            return back()->with('error', __('messages.status_has_tasks'));
        }

        if ($taskStatus->is_default) {
            return back()->with('error', __('messages.cannot_delete_default_status'));
        }

        $taskStatus->delete();

        AuditService::log('task_status_deleted', $taskStatus, ['name' => $taskStatus->name], []);

        return back()->with('success', __('messages.task_status_deleted'));
    }

    /**
     * Save the order the board was dragged into.
     */
    public function reorder(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:task_statuses,id',
        ]);

        foreach ($validated['ids'] as $position => $id) {
            TaskStatus::where('id', $id)->update(['order' => $position]);
        }

        return back()->with('success', __('messages.status_order_saved'));
    }

    /**
     * Exactly one status is the default; setting a new one clears the rest.
     */
    private function makeSoleDefault(TaskStatus $status): void
    {
        TaskStatus::where('id', '!=', $status->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
