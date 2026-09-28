<?php

namespace App\Services;

use App\Models\Tenant\Task;
use App\Models\Tenant\TimeEntry;
use App\Models\Tenant\Timer;
use App\Models\Tenant\User;

class TimerService
{
    public static function start(User $user, Task $task): Timer
    {
        $existing = Timer::where('user_id', $user->id)->whereNull('stopped_at')->first();
        if ($existing) {
            static::stop($user, $existing->task_id);
        }

        return Timer::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'started_at' => now(),
        ]);
    }

    public static function stop(User $user, ?int $taskId = null): ?TimeEntry
    {
        $query = Timer::where('user_id', $user->id)->whereNull('stopped_at');
        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        $timer = $query->latest('started_at')->first();
        if (!$timer) {
            return null;
        }

        $timer->update(['stopped_at' => now()]);

        $seconds = now()->diffInSeconds($timer->started_at);
        $hours = round($seconds / 3600, 2);

        if ($hours < 0.01) {
            $timer->delete();

            return null;
        }

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'task_id' => $timer->task_id,
            'project_id' => $timer->project_id,
            'hours' => $hours,
            'date' => today(),
            'description' => null,
            'is_billable' => $timer->task?->is_billable ?? false,
        ]);

        $timer->delete();

        return $entry;
    }

    public static function getActive(User $user): ?Timer
    {
        return Timer::where('user_id', $user->id)->whereNull('stopped_at')->with('task.project')->first();
    }

    public static function getElapsedSeconds(Timer $timer): int
    {
        return (int) now()->diffInSeconds($timer->started_at);
    }
}
