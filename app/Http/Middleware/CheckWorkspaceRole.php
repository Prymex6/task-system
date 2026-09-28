<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprawdza czy zalogowany użytkownik ma wymaganą rolę workspace.
 *
 * Użycie w routes: ->middleware('workspace.role:admin,manager')
 */
class CheckWorkspaceRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('tenant')->user();

        if (!$user || !in_array($user->workspace_role, $roles)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Brak uprawnień.'], 403);
            }

            abort(403, __('messages.section_forbidden'));
        }

        return $next($request);
    }
}
