<?php

namespace App\Services;

use App\Models\Tenant\Project;
use App\Models\Tenant\User;

class ProjectService
{
    public static function create(array $data, User $creator): Project
    {
        $project = Project::create(array_merge($data, ['created_by' => $creator->id]));

        $project->members()->attach($creator->id, ['project_role' => 'project_manager', 'added_by' => $creator->id]);

        if (!empty($data['members'])) {
            foreach ($data['members'] as $member) {
                if ($member['id'] !== $creator->id) {
                    $project->members()->attach($member['id'], [
                        'project_role' => $member['role'] ?? 'contributor',
                        'added_by' => $creator->id,
                    ]);
                }
            }
        }

        AuditService::log('project.created', $project, [], $project->toArray());

        return $project;
    }

    public static function update(Project $project, array $data): Project
    {
        $old = $project->toArray();
        $project->update($data);
        AuditService::log('project.updated', $project, $old, $project->fresh()->toArray());

        return $project;
    }

    public static function archive(Project $project): void
    {
        $project->update(['is_archived' => true]);
        AuditService::log('project.archived', $project);
    }

    public static function unarchive(Project $project): void
    {
        $project->update(['is_archived' => false]);
        AuditService::log('project.unarchived', $project);
    }

    public static function delete(Project $project): void
    {
        AuditService::log('project.deleted', $project);
        $project->delete();
    }

    public static function addMember(Project $project, int $userId, string $role, int $addedBy): void
    {
        if (!$project->members()->where('user_id', $userId)->exists()) {
            $project->members()->attach($userId, ['project_role' => $role, 'added_by' => $addedBy]);
        } else {
            $project->members()->updateExistingPivot($userId, ['project_role' => $role]);
        }
    }

    public static function removeMember(Project $project, int $userId): void
    {
        $project->members()->detach($userId);
    }

    public static function canAccess(Project $project, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    public static function duplicate(Project $project, User $by): Project
    {
        $new = $project->replicate(['is_archived', 'created_at', 'updated_at']);
        $new->name = $project->name . ' (kopia)';
        $new->created_by = $by->id;
        $new->save();

        $new->members()->attach($by->id, ['project_role' => 'project_manager', 'added_by' => $by->id]);

        foreach ($project->tasks as $task) {
            $clone = $task->replicate(['completed_at', 'created_at', 'updated_at']);
            $clone->project_id = $new->id;
            $clone->save();
        }

        AuditService::log('project.duplicated', $new);

        return $new;
    }
}
