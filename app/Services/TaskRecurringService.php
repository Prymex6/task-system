<?php

namespace App\Services;

use App\Models\Tenant\Task;
use App\Models\Tenant\TaskRecurring;
use Carbon\Carbon;

class TaskRecurringService
{
    public static function generateDue(): void
    {
        $recurring = TaskRecurring::with('task')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', today());
            })
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($recurring as $config) {
            static::createNextTask($config);
            $config->update(['next_run_at' => static::calcNextRun($config)]);
        }
    }

    public static function createNextTask(TaskRecurring $config): Task
    {
        $source = $config->task;

        $clone = $source->replicate(['completed_at', 'created_at', 'updated_at']);
        $clone->created_by = $source->created_by;
        $clone->due_date = static::calcDueDate($config);
        $clone->start_date = null;
        $clone->save();

        $clone->assignees()->sync($source->assignees->pluck('id'));
        $clone->labels()->sync($source->labels->pluck('id'));

        return $clone;
    }

    /**
     * When a task repeating at this frequency comes round again.
     */
    public static function nextRunAt(string $frequency, ?Carbon $from = null): Carbon
    {
        $from ??= now();

        return match ($frequency) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonth(),
            'yearly' => $from->copy()->addYear(),
            default => $from->copy()->addDay(),
        };
    }

    private static function calcNextRun(TaskRecurring $config): Carbon
    {
        return match ($config->frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addDay(),
        };
    }

    private static function calcDueDate(TaskRecurring $config): ?string
    {
        $task = $config->task;
        if (!$task->due_date) {
            return null;
        }

        return match ($config->frequency) {
            'daily' => today()->toDateString(),
            'weekly' => today()->addWeek()->toDateString(),
            'monthly' => today()->addMonth()->toDateString(),
            'yearly' => today()->addYear()->toDateString(),
            default => null,
        };
    }
}
