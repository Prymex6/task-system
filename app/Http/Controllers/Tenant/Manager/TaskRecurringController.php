<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Services\TaskRecurringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskRecurringController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $tasks = Task::with(['project', 'status'])
            ->where('is_recurring', true)
            ->orderBy('next_run_at')
            ->paginate(20);

        return Inertia::render('Tenant/Manager/Tasks/Recurring', [
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,yearly',
            'recur_end_date' => 'nullable|date|after:today',
        ]);

        $task->update([
            'is_recurring' => true,
            'frequency' => $request->frequency,
            'recur_end_date' => $request->recur_end_date,
            'next_run_at' => TaskRecurringService::nextRunAt($request->frequency),
        ]);

        return back()->with('success', __('messages.recurrence_configured'));
    }

    public function destroy(Task $task)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $task->update([
            'is_recurring' => false,
            'frequency' => null,
            'recur_end_date' => null,
            'next_run_at' => null,
        ]);

        return back()->with('success', __('messages.recurrence_disabled'));
    }
}
