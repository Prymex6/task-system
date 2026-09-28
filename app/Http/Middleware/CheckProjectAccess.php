<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprawdza czy zalogowany użytkownik ma dostęp do projektu w route.
 *
 * Owner/Admin mają zawsze dostęp.
 * Manager/Member/Guest tylko jeśli są project_member.
 *
 * Użycie: ->middleware('project.access')
 * Wymaga parametru route {project}
 */
class CheckProjectAccess
{
    public function handle(Request $request, Closure $next, string $minRole = 'viewer'): Response
    {
        $user = auth('tenant')->user();
        $project = $request->route('project');

        if (!$project instanceof Project) {
            $project = Project::findOrFail($request->route('project'));
        }

        if (!$user->canAccessProject($project)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Brak dostępu do tego projektu.'], 403);
            }
            abort(403, __('messages.project_access_denied'));
        }

        // Sprawdź minimalną rolę projektową (jeśli wymagana)
        if ($minRole !== 'viewer' && !$user->isAdmin()) {
            $projectRole = $user->projectRole($project);
            $roleHierarchy = ['viewer' => 0, 'contributor' => 1, 'project_manager' => 2];
            $userLevel = $roleHierarchy[$projectRole] ?? 0;
            $requiredLevel = $roleHierarchy[$minRole] ?? 0;

            if ($userLevel < $requiredLevel) {
                abort(403, __('messages.project_role_insufficient'));
            }
        }

        return $next($request);
    }
}
