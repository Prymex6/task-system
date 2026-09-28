<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route on the signed-in user's workspace role.
 *
 * The owner passes without being listed. Spelling them out on every route
 * invites the one omission that locks a workspace out of its own settings,
 * and there is no case where the owner should be refused.
 *
 * Use: ->middleware('workspace.role:admin,manager')
 */
class CheckWorkspaceRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('tenant')->user();

        if (!$user) {
            abort(403, __('messages.section_forbidden'));
        }

        if ($user->workspace_role !== 'owner' && !in_array($user->workspace_role, $roles, true)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => __('messages.section_forbidden')], 403);
            }

            abort(403, __('messages.section_forbidden'));
        }

        return $next($request);
    }
}
