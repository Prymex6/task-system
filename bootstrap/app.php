<?php

use App\Http\Middleware\CheckProjectAccess;
use App\Http\Middleware\CheckWorkspaceRole;
use App\Http\Middleware\EnsureCustomerAuth;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantAuth;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackLastSeen;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/landlord.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ForceHttps::class);

        // Redirect unauthenticated requests to the correct login route
        Authenticate::redirectUsing(function ($request) {
            if ($request->routeIs('tenant.manager.*') || str_starts_with($request->path(), 'manager') || str_starts_with($request->path(), 'dashboard')) {
                return route('tenant.login');
            }

            return route('tenant.client.login');
        });

        $middleware->web(append: [
            // SetLocale was registered as an alias and never applied to
            // anything, so the language a workspace picked in its settings
            // was read from the database and then thrown away on every
            // request. It belongs on the web stack, ahead of Inertia, which
            // passes the active locale to the front end.
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        // Exclude payment webhooks and API cart endpoints from CSRF verification
        $middleware->validateCsrfTokens(except: [
            '*/payment/webhook',
            '*/payment/webhook/*',
        ]);

        $middleware->alias([
            'auth.super_admin' => EnsureSuperAdmin::class,
            'auth.tenant' => EnsureTenantAuth::class,
            'auth.customer' => EnsureCustomerAuth::class,
            'workspace.role' => CheckWorkspaceRole::class,
            'project.access' => CheckProjectAccess::class,
            'track.last.seen' => TrackLastSeen::class,
            'set.locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Give a throttled request a translated message, for Inertia and JSON alike
        $exceptions->render(function (
            ThrottleRequestsException $e,
            Request $request
        ) {
            $message = 'Zbyt wiele prób. Poczekaj chwilę i spróbuj ponownie.';
            if ($request->inertia()) {
                throw ValidationException::withMessages([
                    'throttle' => $message,
                ]);
            }

            return response()->json(['message' => $message], 429);
        });
    })->create();
