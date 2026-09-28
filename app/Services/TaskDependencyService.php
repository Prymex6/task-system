<?php

namespace App\Services;

use App\Models\Tenant\Task;
use App\Models\Tenant\TaskDependency;

class TaskDependencyService
{
    public static function add(Task $task, int $dependsOnId, string $type = 'finish_to_start'): TaskDependency
    {
        static::validateNoCycle($task->id, $dependsOnId);

        return TaskDependency::firstOrCreate([
            'task_id' => $task->id,
            'depends_on_id' => $dependsOnId,
        ], ['type' => $type]);
    }

    public static function remove(int $dependencyId): void
    {
        TaskDependency::findOrFail($dependencyId)->delete();
    }

    public static function canStart(Task $task): bool
    {
        foreach ($task->dependencies as $dep) {
            $parent = Task::find($dep->depends_on_id);
            if (!$parent) {
                continue;
            }

            if ($dep->type === 'finish_to_start' && !$parent->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    private static function validateNoCycle(int $taskId, int $dependsOnId): void
    {
        if ($taskId === $dependsOnId) {
            abort(422, __('messages.task_cannot_depend_on_itself'));
        }

        $ancestors = static::getAncestors($dependsOnId);
        if (in_array($taskId, $ancestors)) {
            abort(422, __('messages.dependency_would_cycle'));
        }
    }

    private static function getAncestors(int $taskId, array $visited = []): array
    {
        if (in_array($taskId, $visited)) {
            return $visited;
        }
        $visited[] = $taskId;

        $deps = TaskDependency::where('task_id', $taskId)->pluck('depends_on_id');
        foreach ($deps as $depId) {
            $visited = static::getAncestors($depId, $visited);
        }

        return $visited;
    }
}
