<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;

/**
 * Puts the request into the workspace's language.
 *
 * The languages the interface actually exists in are fixed by what is in
 * lang/ and resources/js/locales, so they are listed here rather than read
 * from a setting: a workspace picking a third language would only get keys
 * back.
 */
class SetLocale
{
    public const SUPPORTED = ['pl', 'en'];

    public const FALLBACK = 'pl';

    public function handle(Request $request, Closure $next)
    {
        app()->setLocale($this->resolve());

        return $next($request);
    }

    private function resolve(): string
    {
        try {
            // Before a tenant is initialised there is no settings table to read.
            $locale = Setting::get('language', self::FALLBACK);
        } catch (\Throwable) {
            return self::FALLBACK;
        }

        return in_array($locale, self::SUPPORTED, true) ? $locale : self::FALLBACK;
    }
}
