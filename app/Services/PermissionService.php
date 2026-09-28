<?php

namespace App\Services;

use App\Models\Tenant\Project;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\User;

class PermissionService
{
    /**
     * Sprawdza uprawnienie workspace dla usera.
     * Owner i admin zawsze mają dostęp.
     */
    public function can(User $user, string $action): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permission = RolePermission::where('role', $user->workspace_role)
            ->where('action', $action)
            ->first();

        return $permission ? (bool) $permission->allowed : false;
    }

    /**
     * Sprawdza dostęp do projektu.
     */
    public function canAccessProject(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Sprawdza minimalną rolę projektową.
     */
    public function hasProjectRole(User $user, Project $project, string $minRole): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $member = $project->members()->where('user_id', $user->id)->first();

        if (!$member) {
            return false;
        }

        $hierarchy = ['viewer' => 0, 'contributor' => 1, 'project_manager' => 2];
        $userLevel = $hierarchy[$member->pivot->project_role] ?? 0;
        $requiredLevel = $hierarchy[$minRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Pobiera wszystkie uprawnienia dla roli.
     */
    public function getPermissionsForRole(string $role): array
    {
        return RolePermission::where('role', $role)
            ->pluck('allowed', 'action')
            ->toArray();
    }

    /**
     * Aktualizuje uprawnienia roli.
     */
    public function updateRolePermissions(string $role, array $permissions): void
    {
        foreach ($permissions as $action => $allowed) {
            RolePermission::updateOrCreate(
                ['role' => $role, 'action' => $action],
                ['allowed' => (bool) $allowed]
            );
        }
    }
}
