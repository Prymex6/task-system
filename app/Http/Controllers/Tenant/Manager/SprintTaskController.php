<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Sprint;
use App\Models\Tenant\Task;
use Illuminate\Http\Request;

class SprintTaskController extends Controller
{
    public function store(Request $request, Sprint $sprint)
    {
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'story_points' => ['nullable', 'integer', 'min:0'],
        ]);

        $sprint->tasks()->syncWithoutDetaching([
            $validated['task_id'] => ['story_points' => $validated['story_points'] ?? null],
        ]);

        return back()->with('success', __('messages.task_added_to_sprint'));
    }

    public function destroy(Sprint $sprint, Task $task)
    {
        $sprint->tasks()->detach($task->id);

        return back()->with('success', __('messages.task_removed_from_sprint'));
    }
}
