<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('customer')->check()) {
            return redirect()->route('tenant.client.login');
        }

        return $next($request);
    }
}
