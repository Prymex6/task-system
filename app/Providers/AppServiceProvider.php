<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register landlord (central DB) migrations so `php artisan migrate` finds them.
        // Tenant migrations are handled separately by `php artisan tenants:migrate`
        // via tenancy.migration_parameters pointing to database/migrations/tenant/.
        $this->loadMigrationsFrom(database_path('migrations/landlord'));

        // --- Landing page fix ---
        // TenancyServiceProvider registers tenant routes (including GET /) via app->booted(),
        // which fires AFTER web.php. Laravel stores routes keyed by domain+uri, so the tenant
        // GET / route replaces the central 'landing' route in the route collection.
        //
        // TenancyServiceProvider also calls makeTenancyMiddlewareHighestPriority(), which puts
        // PreventAccessFromCentralDomains at a HIGHER priority than InitializeTenancyByDomain.
        // So on a central domain request to GET /, PreventAccessFromCentralDomains runs FIRST
        // and aborts(404) before InitializeTenancyByDomain even runs.
        //
        // Fix: override PreventAccessFromCentralDomains::$abortRequest so that GET / on a
        // central domain (localhost) invokes LandingController instead of aborting 404.
        PreventAccessFromCentralDomains::$abortRequest = function ($request, $next) {
            if ($request->is('/')) {
                return redirect(config('app.hub_url', 'http://system.localhost'));
            }
            abort(404);
        };

        // Keep onFail as a safety net for the case PreventAccessFromCentralDomains doesn't fire.
        InitializeTenancyByDomain::$onFail = function ($e, $request, $next) {
            if ($request->is('/') && in_array($request->getHost(), config('tenancy.central_domains'))) {
                return redirect(config('app.hub_url', 'http://system.localhost'));
            }
            abort(404);
        };

        // Register event listeners

    }
}
