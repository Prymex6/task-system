<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskRecurring;
use App\Services\TaskRecurringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TaskRecurringController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        // A task repeats because a schedule row points at it, so the list is
        // over the schedules. Reading a flag off tasks, as this did, queried a
        // column the table has never had.
        $schedules = TaskRecurring::with(['task:id,title,project_id', 'task.project:id,name'])
            ->orderBy('next_occurrence')
            ->paginate(20);

        return Inertia::render('Tenant/Manager/Tasks/Recurring', [
            'schedules' => $schedules,
            'frequencies' => TaskRecurringService::FREQUENCIES,
        ]);
    }

    public function store(Request $request, Task $task)
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        $validated = $request->validate([
            'frequency' => ['required', Rule::in(TaskRecurringService::FREQUENCIES)],
            'interval' => 'nullable|integer|min:1|max:52',
            'ends_at' => 'nullable|date|after:today',
        ]);

        $interval = $validated['interval'] ?? 1;

        // One schedule per task: asking again changes the existing one rather
        // than leaving the task cloning itself twice over.
        $task->recurring()->updateOrCreate([], [
            'frequency' => $validated['frequency'],
            'interval' => $interval,
            'next_occurrence' => TaskRecurringService::advance($validated['frequency'], $interval),
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return back()->with('success', __('messages.recurrence_configured'));
    }

    public function destroy(Task $task)
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        $task->recurring()->delete();

        return back()->with('success', __('messages.recurrence_disabled'));
    }
}
