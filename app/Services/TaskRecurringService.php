<?php

namespace App\Services;

use App\Models\Tenant\Task;
use App\Models\Tenant\TaskRecurring;
use Carbon\Carbon;

/**
 * Repeating tasks.
 *
 * A task repeats because a row exists for it in task_recurring, not because of
 * a flag on the task itself: stopping the repetition deletes the row, which is
 * why nothing here looks for an "active" column.
 *
 * Every schedule carries a frequency and an interval, so "every second week" is
 * weekly with an interval of two rather than a frequency of its own.
 */
class TaskRecurringService
{
    /** @var list<string> */
    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];

    /**
     * Clone every task whose next occurrence has come round.
     *
     * @return int how many tasks were created
     */
    public static function generateDue(): int
    {
        $due = TaskRecurring::with('task')
            ->whereNotNull('next_occurrence')
            ->whereDate('next_occurrence', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->get();

        $created = 0;

        foreach ($due as $schedule) {
            // The task can be gone even though the cascade should have taken
            // the schedule with it; skipping beats a null reference in a job
            // that has other rows to get through.
            if (!$schedule->task) {
                continue;
            }

            static::createNextTask($schedule);

            $schedule->update([
                'next_occurrence' => static::advance($schedule->frequency, $schedule->interval, $schedule->next_occurrence),
            ]);

            $created++;
        }

        return $created;
    }

    public static function createNextTask(TaskRecurring $schedule): Task
    {
        $source = $schedule->task;

        $clone = $source->replicate(['completed_at', 'created_at', 'updated_at']);
        $clone->created_by = $source->created_by;
        $clone->due_date = $source->due_date ? $schedule->next_occurrence?->toDateString() : null;
        $clone->start_date = null;
        $clone->save();

        $clone->assignees()->sync($source->assignees->pluck('id'));
        $clone->labels()->sync($source->labels->pluck('id'));

        return $clone;
    }

    /**
     * When a schedule at this frequency next comes round.
     */
    public static function advance(string $frequency, int $interval = 1, ?Carbon $from = null): Carbon
    {
        $from = $from ? $from->copy() : today();
        $interval = max(1, $interval);

        return match ($frequency) {
            'weekly' => $from->addWeeks($interval),
            'monthly' => $from->addMonths($interval),
            'yearly' => $from->addYears($interval),
            default => $from->addDays($interval),
        };
    }
}
