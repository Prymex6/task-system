<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskChecklist;
use App\Models\Tenant\TaskChecklistItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Checklists hanging off a task, and the items inside them.
 *
 * Both levels keep their own `order`, and a new row is appended rather than
 * inserted, so adding one never renumbers what is already there.
 */
class TaskChecklistController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate(['title' => 'required|string|max:255']);

        $task->checklists()->create([
            'title' => $validated['title'],
            'order' => $this->nextOrder($task->checklists()),
        ]);

        return back()->with('success', __('messages.checklist_created'));
    }

    public function update(Request $request, Task $task, TaskChecklist $checklist)
    {
        $this->authorizeTask($task);
        $this->assertBelongsToTask($checklist, $task);

        $validated = $request->validate(['title' => 'required|string|max:255']);
        $checklist->update($validated);

        return back()->with('success', __('messages.checklist_updated'));
    }

    public function destroy(Task $task, TaskChecklist $checklist)
    {
        $this->authorizeTask($task);
        $this->assertBelongsToTask($checklist, $task);

        $checklist->delete();

        return back()->with('success', __('messages.checklist_deleted'));
    }

    public function storeItem(Request $request, Task $task, TaskChecklist $checklist)
    {
        $this->authorizeTask($task);
        $this->assertBelongsToTask($checklist, $task);

        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $checklist->items()->create([
            ...$validated,
            'order' => $this->nextOrder($checklist->items()),
        ]);

        return back()->with('success', __('messages.item_created'));
    }

    public function updateItem(Request $request, Task $task, TaskChecklist $checklist, TaskChecklistItem $item)
    {
        $this->authorizeTask($task);
        $this->assertBelongsToTask($checklist, $task);
        abort_unless($item->task_checklist_id === $checklist->id, 404);

        $validated = $request->validate([
            'content' => 'sometimes|required|string|max:500',
            'is_completed' => 'sometimes|boolean',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        // completed_at is derived from the tick, never sent by the client.
        if (array_key_exists('is_completed', $validated)) {
            $validated['completed_at'] = $validated['is_completed'] ? now() : null;
        }

        $item->update($validated);

        return back()->with('success', __('messages.item_updated'));
    }

    public function destroyItem(Task $task, TaskChecklist $checklist, TaskChecklistItem $item)
    {
        $this->authorizeTask($task);
        $this->assertBelongsToTask($checklist, $task);
        abort_unless($item->task_checklist_id === $checklist->id, 404);

        $item->delete();

        return back()->with('success', __('messages.item_deleted'));
    }

    private function authorizeTask(Task $task): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $task->project->members->contains($user->id), 403);
    }

    private function assertBelongsToTask(TaskChecklist $checklist, Task $task): void
    {
        abort_unless($checklist->task_id === $task->id, 404);
    }

    /**
     * @param HasMany<Model, Model> $relation
     */
    private function nextOrder($relation): int
    {
        return (int) $relation->max('order') + 1;
    }
}
