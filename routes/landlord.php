<?php

use App\Http\Controllers\Landlord\AuthController;
use App\Http\Controllers\Landlord\ContactController;
use App\Http\Controllers\Landlord\DashboardController;
use App\Http\Controllers\Landlord\PlanController;
use App\Http\Controllers\Landlord\StatisticsController;
use App\Http\Controllers\Landlord\SupportController;
use App\Http\Controllers\Landlord\TenantController;
use App\Http\Controllers\Landlord\WorkspaceLeadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord routes (the platform's own panel)
|--------------------------------------------------------------------------
|
| These run on the central domain against the landlord database. Nothing here
| touches a tenant's data directly: a workspace is managed as a record, and
| its own screens live behind its own domain.
|
*/

Route::prefix('admin')->name('landlord.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth.super_admin'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');

        Route::resource('plans', PlanController::class)->except(['show']);

        // Workspaces. The literal paths come before the {tenant} wildcard, or
        // Laravel matches the wildcard first and /tenants/create 404s.
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/', [TenantController::class, 'index'])->name('index');
            Route::get('/create', [TenantController::class, 'create'])->name('create');
            Route::post('/', [TenantController::class, 'store'])->name('store');
            Route::get('/{tenant}', [TenantController::class, 'show'])->name('show');
            Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('edit');
            Route::put('/{tenant}', [TenantController::class, 'update'])->name('update');
            Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy');
            Route::post('/{tenant}/suspend', [TenantController::class, 'suspend'])->name('suspend');
            Route::post('/{tenant}/activate', [TenantController::class, 'activate'])->name('activate');
            Route::post('/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('impersonate');
        });

        // Prospects for the platform itself, not a tenant's own CRM leads.
        Route::prefix('workspace-leads')->name('workspace-leads.')->group(function () {
            Route::get('/', [WorkspaceLeadController::class, 'index'])->name('index');
            Route::post('/{workspaceLead}/contacted', [WorkspaceLeadController::class, 'toggleContacted'])
                ->name('toggle-contacted');
        });

        // Support the platform gives its tenants.
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [SupportController::class, 'index'])->name('index');
            Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->name('reply');
            Route::patch('/{ticket}/status', [SupportController::class, 'updateStatus'])->name('update-status');
        });

        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::post('/{inquiry}/read', [ContactController::class, 'markRead'])->name('read');
            Route::delete('/{inquiry}', [ContactController::class, 'destroy'])->name('destroy');
        });
    });
});
