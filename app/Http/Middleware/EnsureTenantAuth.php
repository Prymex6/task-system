<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('tenant')->check()) {
            return redirect()->route('tenant.login');
        }

        $user = auth('tenant')->user();

        if (!$user->is_active) {
            auth('tenant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login')
                ->withErrors(['email' => __('messages.account_deactivated')]);
        }

        return $next($request);
    }
}
