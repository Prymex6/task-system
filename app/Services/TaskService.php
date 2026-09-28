<?php

namespace App\Services;

use App\Models\Tenant\Task;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\User;

class TaskService
{
    public static function create(array $data, User $creator): Task
    {
        if (empty($data['task_status_id'])) {
            $data['task_status_id'] = TaskStatus::orderBy('order')->value('id');
        }

        $task = Task::create(array_merge(
            collect($data)->except(['assignees', 'labels'])->toArray(),
            ['created_by' => $creator->id]
        ));

        if (!empty($data['assignees'])) {
            $task->assignees()->sync($data['assignees']);
        }

        if (!empty($data['labels'])) {
            $task->labels()->sync($data['labels']);
        }

        AuditService::log('task.created', $task, [], $task->toArray());

        return $task;
    }

    public static function update(Task $task, array $data): Task
    {
        $old = $task->toArray();

        if (isset($data['is_completed'])) {
            $data['completed_at'] = $data['is_completed'] ? now() : null;
        }

        $task->update(collect($data)->except(['assignees', 'labels'])->toArray());

        if (array_key_exists('assignees', $data)) {
            $task->assignees()->sync($data['assignees'] ?? []);
        }
        if (array_key_exists('labels', $data)) {
            $task->labels()->sync($data['labels'] ?? []);
        }

        AuditService::log('task.updated', $task, $old, $task->fresh()->toArray());

        return $task;
    }

    public static function changeStatus(Task $task, int $statusId): void
    {
        $old = ['task_status_id' => $task->task_status_id];
        $task->update(['task_status_id' => $statusId]);

        $status = TaskStatus::find($statusId);
        if ($status?->is_closed && !$task->completed_at) {
            $task->update(['completed_at' => now()]);
        } elseif (!$status?->is_closed) {
            $task->update(['completed_at' => null]);
        }

        AuditService::log('task.status_changed', $task, $old, ['task_status_id' => $statusId]);
    }

    public static function delete(Task $task): void
    {
        AuditService::log('task.deleted', $task);
        $task->delete();
    }

    public static function duplicate(Task $task, User $by): Task
    {
        $clone = $task->replicate(['completed_at', 'created_at', 'updated_at']);
        $clone->title = $task->title . ' (kopia)';
        $clone->created_by = $by->id;
        $clone->save();

        $clone->assignees()->sync($task->assignees->pluck('id'));
        $clone->labels()->sync($task->labels->pluck('id'));

        foreach ($task->checklists as $checklist) {
            $newList = $clone->checklists()->create(['title' => $checklist->title, 'order' => $checklist->order]);
            foreach ($checklist->items as $item) {
                $newList->items()->create(['content' => $item->content, 'order' => $item->order, 'is_completed' => false]);
            }
        }

        AuditService::log('task.duplicated', $clone);

        return $clone;
    }

    public static function getKanbanData(int $projectId, User $user): array
    {
        $statuses = TaskStatus::orderBy('order')->get();
        $tasks = Task::with(['assignees', 'labels', 'status'])
            ->where('project_id', $projectId)
            ->whereNull('parent_task_id')
            ->get();

        return $statuses->map(function ($status) use ($tasks) {
            return [
                'status' => $status,
                'tasks' => $tasks->where('task_status_id', $status->id)->sortBy('order')->values(),
            ];
        })->toArray();
    }

    public static function moveKanban(Task $task, int $statusId, int $order): void
    {
        static::changeStatus($task, $statusId);
        $task->update(['order' => $order]);
    }
}
