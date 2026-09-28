<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('tenant')->check()) {
            $user = auth('tenant')->user();
            $cacheKey = 'last_seen_' . $user->id;

            // Aktualizuj tylko raz na 5 minut
            if (!Cache::has($cacheKey)) {
                $user->timestamps = false;
                $user->update(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, 300);
            }
        }

        return $next($request);
    }
}
