<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Task;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\Timer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskTimerController extends Controller
{
    public function start(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();

        Timer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'started_at' => now(),
                'paused_at' => null,
                'elapsed_seconds' => 0,
            ]
        );

        return back()->with('success', __('messages.timer_started'));
    }

    public function stop(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();
        $timer = Timer::where('user_id', $user->id)->where('task_id', $task->id)->first();

        if ($timer) {
            $hours = round($timer->total_seconds / 3600, 2);

            if ($hours > 0) {
                TimeEntry::create([
                    'user_id' => $user->id,
                    'project_id' => $timer->project_id,
                    'task_id' => $timer->task_id,
                    'date' => now()->toDateString(),
                    'hours' => $hours,
                    'description' => $timer->description,
                    'is_billable' => true,
                ]);
            }

            $timer->delete();
        }

        return back()->with('success', __('messages.timer_stopped'));
    }

    public function logManual(Request $request, Task $task)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'date' => $validated['date'],
            'hours' => $validated['hours'],
            'description' => $validated['description'] ?? null,
            'is_billable' => true,
        ]);

        return back()->with('success', __('messages.time_logged'));
    }

    public function destroyLog(Request $request, Task $task, TimeEntry $log)
    {
        abort_unless($log->task_id === $task->id, 404);

        $log->delete();

        return back()->with('success', __('messages.time_entry_deleted'));
    }
}
