<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('tenant')->user();

        if (!$user || !in_array($user->role, $roles)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Brak uprawnień.'], 403);
            }

            return redirect()->route('tenant.staff.redirect')
                ->with('error', __('messages.section_forbidden'));
        }

        return $next($request);
    }
}
